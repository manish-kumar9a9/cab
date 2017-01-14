<?php

/**
 * Copyright © 2016 Ipragmatech . All rights reserved.
 * Developer - Manish Kumar
 * Date - 27 july 2016
 *
 */
namespace Ipragmatech\Ipproducts\Model;

use Ipragmatech\Ipproducts\Api\ProductsInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\SortOrder;
/**
 * Defines the implementation class of the Products service contract.
 */
class Products implements ProductsInterface
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $metadataService;

    /*
    * @var \Magento\Store\Model\StoreManagerInterface
    */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $resourceModel;

    /**
     * @var \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * @var array contains all current page children products
     */
    protected $_allChildProducts = [];

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $_stockFilter;
    /**
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory ,
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Product $resourceModel
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param \Magento\CatalogInventory\Helper\Stock $stockFilter
     */
    public function __construct(
        \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ResourceModel\Product $resourceModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type
        \Configurable $catalogProductTypeConfigurable,
         \Magento\CatalogInventory\Helper\Stock $stockFilter
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceModel = $resourceModel;
        $this->storeManager = $storeManager;
        $this->metadataService = $metadataServiceInterface;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_stockFilter = $stockFilter;
    }

    /**
     * Get product List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return array contains list of products
     */
    public function getProducts(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) {
        try{
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $collection = $this->collectionFactory->create();
            $this->extensionAttributesJoinProcessor->process($collection);

            foreach ($this->metadataService->getList($this->searchCriteriaBuilder->create())->getItems() as $metadata) {
                $collection->addAttributeToSelect($metadata->getAttributeCode());
            }

            //add status attribute to filter
            $collection->joinAttribute('status', 'catalog_product/status',
                'entity_id', null, 'inner');

            //add visibility attribute to filter
            $collection->joinAttribute('visibility', 'catalog_product/visibility',
                'entity_id', null, 'inner');

            //add Store filter to get products according to the store specified
            $currentStore = $this->storeManager->getStore();
            $collection->addStoreFilter($currentStore);

            /**
             * get the product with no parent i.e simple and configurable Products
             * without parent
             *
             */
            $collection->getSelect()->joinLeft(
                'catalog_product_super_link',
                'e.entity_id = catalog_product_super_link.product_id'
            )->where ("catalog_product_super_link.product_id IS NULL");

            //add stock filter
            $this->_stockFilter->addInStockFilterToCollection($collection);



            //apply all search criterias to the products collection
            foreach ($searchCriteria->getFilterGroups() as $group) {
                $this->addFilterGroupToCollection($group, $collection);
            }

            //add sorting criteria
            foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
                $field = $sortOrder->getField();
                $collection->addOrder(
                    $field,
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }

            //Added current page and page size
            $collection->setCurPage($searchCriteria->getCurrentPage());
            $collection->setPageSize($searchCriteria->getPageSize());
            $collection->load();


            $allProducts = $collection->getItems();

            //Collecting all parent ids for the current page
            $parentProductIds = [];
            foreach ($allProducts as $product) {
                $type = $product->getTypeId();
                if ($type == "configurable") {
                    $parentProductIds[] = $product->getId();
                }
            }

            //getting all current page child products
            $this->_allChildProducts = $this->getAllCurrentChild
            ($parentProductIds);

            //grouping product according to type_id
            $collectionData = [];
            foreach ($allProducts as $product) {
                $type = $product->getTypeId();
                if ($type == "simple") {
                    $collectionData[] = $product;//->getData();
                } elseif ($type == "configurable") {
                    if(array_key_exists($product->getId(),$this->_allChildProducts)) {
                        $childItems = $this->_allChildProducts[$product->getId()];
                        $collectionData[] = $this->getData($product,
                            $childItems);
                    }else{
                        continue;
                    }
                } else {
                    $collectionData[] = $product;//->getData();
                }
            }

            //manipulating response result
            $searchResult = $this->searchResultsFactory->create();
            $searchResult->setSearchCriteria($searchCriteria);
            $searchResult->setItems($collectionData);
            $searchResult->setTotalCount($collection->getSize());

            return $searchResult;
        }catch (\Exception $e){
            return [
                'status'=>false,
                ];
        }

    }


    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    public function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        Collection $collection
    ) {

        $fields = [];
        $categoryFilter = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $conditionType = $filter->getConditionType() ? $filter->getConditionType() : 'eq';

            if ($filter->getField() == 'category_id') {
                $categoryFilter[$conditionType][] = $filter->getValue();
                continue;
            }
            $fields[] = [
                'attribute'    => $filter->getField(),
                $conditionType => $filter->getValue()
            ];
        }

        if ($categoryFilter) {
            $collection->addCategoriesFilter($categoryFilter);
        }

        if ($fields) {
            $collection->addFieldToFilter($fields);
        }
    }


    /**
     * Helper function to fetch selected attribute of configurable products
     * @param \Magento\Catalog\Product $product
     * @param  array $childProduct
     * @return array $productData
     */

    public function getData($product, $childProduct)
    {
        //extracting custom attributes
        $customAttributes = $product->getCustomAttributes();
        $customAttributeData = [];
        foreach ($customAttributes as $attribute){
            $data = [
               "attribute_code" => $attribute->getAttributeCode(),
               "value"=> $attribute->getValue()
            ];
            $customAttributeData[] = $data;
        }
        $productData = [
            "id"               => $product->getId(),
            "sku"              => $product->getSku(),
            "name"             => $product->getName(),
            "attribute_set_id" => $product->getAttributeSetId(),
            "price"            => $product->getPrice(),
            "status"           => $product->getStatus(),
            "visibility"       => $product->getVisibility(),
            "type_id"          => $product->getTypeId(),
            "image"            => $product->getImage(),
            "url_key"          => $product->getUrlKey(),
            "description"      => $product->getDescription(),
            "created_at"       => $product->getCreatedAt(),
            "updated_at"       => $product->getUpdatedAt(),
            "custom_attributes"=>$customAttributeData,
            "child_products"    => $childProduct
        ];

        return $productData;
    }


    /**
     * Get child product List
     *
     * @param $parentArray contains the parent product ids
     * @return array contains list of child products for the specified parents
     */
    public function getAllCurrentChild($parentArray){

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($collection);

        foreach ($this->metadataService->getList($this->searchCriteriaBuilder->create())->getItems() as $metadata) {
            $collection->addAttributeToSelect($metadata->getAttributeCode());
        }

        //add status attribute to filter
        $collection->joinAttribute('status', 'catalog_product/status',
            'entity_id', null, 'inner');

        //add visibility attribute to filter
        $collection->joinAttribute('visibility', 'catalog_product/visibility',
            'entity_id', null, 'inner');

        //add Store filter to get products according to the store specified
        $currentStore = $this->storeManager->getStore();
        $collection->addStoreFilter($currentStore);

        //add type_id filter
        $collection->addAttributeToFilter('type_id', "simple");

        /**
         * get the child product with respect to parent i.e simple
         * Products of configurable with parent
         */
        if(count($parentArray)>0) {
            $parentString = implode(',', $parentArray);
        }else{
            $parentString = '0';
        }
        $condition= 'catalog_product_super_link.parent_id IN ('.$parentString.')';
        $collection->getSelect()->joinLeft(
            'catalog_product_super_link',
            'e.entity_id = catalog_product_super_link.product_id',array
            ('parent_id')
        )->where ($condition);

        //add stock filter
        $this->_stockFilter->addInStockFilterToCollection($collection);


        $allProducts = $collection->getItems();
        $data = [];
        foreach ($allProducts as $product){
            $data[$product->getParentId()][] =
                $product->getData();
        }
        return $data;
    }
}

