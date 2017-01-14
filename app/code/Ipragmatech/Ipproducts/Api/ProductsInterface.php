<?php

/**
 * Copyright © 2015 Ipragmatech . All rights reserved.
 * Author - Manish Kumar
 * Dated - 27 july 2016
 */
namespace Ipragmatech\Ipproducts\Api;

/**
 * Defines the service contract for Products.
 * Interface ProductsInterface
 * @api
 */
interface ProductsInterface
{

    /**
     * Get product list based on the search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    public function getProducts(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}