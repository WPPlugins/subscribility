WP SUBS API Documentation
-------------------------

All requests must have a valid Token header in a similar format that the SUBS API uses.

The timestamp field is used to generated the key and is validated against the request time. It is to remove network latency issues.  

X-Authorization:Token version=1.0, timestamp=123456789 consumer=9mA9Z7fn4Gd6A8YxaqcF, resource=Wordpress-10078, key=generatedtokenkey

The API requires WordPress to use pretty permalinks. without it the API cannot be routed correctly.

####
(SEB): if permalinks are required, can we check on install that this is enabled in the workdpress installation
(SEB): NOTE This document needs updating following the revamp completion. Many "setup" steps should be made more user-friendly
####

Initial Imports
---------------
Initial imports must be done from wp-admin, and are triggered by adding a GET param to the request, once logged in and in wp-admin as an administrator.

Import the products into the system
?do_wp99234_initial_product_import=1

Import the users into the system
?do_wp99234_initial_user_import=1

Triggers
--------
Trigger certain actions from wp-admin

Import the company membership types to WC.
?do_wp99234_import_membership_types=1 



Endpoints
---------

Update a product, WP will update the correct product based on the subs ID (expecting one product only at this stage.)
PUT /wp99234-api/1.0/products

Get All Products
GET /wp99234-api/1.0/products

Get a product corresponding with the Subs product ID
GET /wp99234-api/1.0/products/1234

Update or add a user, if the id field in the data matches one in WP or the email already exists, it will update that user, else it will create a new one.
PUT /wp99234-api/1.0/customers

Update an order
PUT /wp99234-api/1.0/orders/update

Get an order based on the Subs ID
GET /wp99234-api/1.0/orders/get/1234


Troly Dev Setup
-----------------------

Staging: http://stagingsubs.herokuapp.com
user: company@empireone.com.au
pwd: company1

Addons -> Wordpress

+Troly entirely driven by a JSON REST service: https://stagingsubs.herokuapp.com/products.json, orders.json, customers, etc. (clubs -> membership_types)



