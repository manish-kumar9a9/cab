		==Product Listing REST API==

==Installation and Configuration==

1. Download the package
2. Copy and paste under Magento root directory, folder structure should be like this
3. magento_root/app/code/Ipragmatech/Ipproducts.
4. Go to the Magento root directory from your terminal
5. Run the command : sudo php bin/magento setup:upgrade
6. Delete the di, generation and cache from var/
7. Run the command: sudo php bin/magento setup:di:compile
8. Run the command: sudo php bin/magento cache:clean
9. Give the read and execute permission var/di, var/generation, var/cache
10. You are done, make the rest api call.

== API url and parameter ==

Swagger UI document  url : http://magentoapi.ipragmatech.com/dist/index.html


1. To get customer token
Url : {base url}/rest/default/V1/integration/customer/token
Type: post
Parameter:
  		"username": "roni_cost@example.com",
 		"password": "roni_cost@example.com"
Response: customer token

Step1: Go here
Step2: Click on “Products” label
Step3: Click on “/rest/default/V1/integration/customer/token”
Step4: Enter the username and password as shown above
Step5: Click on “Try it out” button


2. To get Product List
Url: {base url}/rest/V1/ipproducts/products
Type: get
Parameter:
searchCriteria[pageSize]: 10
searchCriteria[currentPage]: 1
searchCriteria[filterGroups][0][filters][0][field] : Product attribute name for which want to use filter such as name, category_id etc.
searchCriteria[filterGroups][0][filters][0][value] : value for the above field like %Kit% etc.
searchCriteria[filterGroups][0][filters][0][conditionType] : Condition type for the filter for example: eq, in, like etc.

Response: List of product.

Step1: Go here
Step2: Click on “Products” label
Step3: Click on “/rest/V1/ipproducts/products”
Step4: Enter the required parameter
	Note: Authorization parameter is optional.
Step5: Click on “Try it out” button

3.  To get product list according to specific store
  We have implemented REST Schema Endpoint Format to list out the product according to store.
  Note: REST Schema Endpoint Format[http://devdocs.magento.com/guides/v2.0/rest/rest_endpoints.html]
  Url: {base url}/rest/fashion/V1/ipproducts/products
  storecode : fashion
  Type: get
  Parameter:
  searchCriteria[pageSize]: 10
  searchCriteria[currentPage]: 1
  searchCriteria[filterGroups][0][filters][0][field] : Product attribute name for which want to use filter such as name, category_id etc.
  searchCriteria[filterGroups][0][filters][0][value] : value for the above field like %Kit% etc.
  searchCriteria[filterGroups][0][filters][0][conditionType] : Condition type for the filter for example: eq, in, like etc.

  Response: List of product for according to store.

  Step1: Go here
  Step2: Click on “Products” label
  Step3: Click on “/rest/fashion/V1/ipproducts/products”
  Step4: Enter the required parameter
  	Note: Authorization parameter is optional.
  Step5: Click on “Try it out” button
