# snillrik-evercate
 Plugin for WooCommerce to interact with Evercate API. Very simple to use but pretty smart way of selling Evercate courses in Woo. [Evercate](https://evercate.com/) 

## install
Install as any other plugin. 
Add the credentials you get from Evercate under Evercate+ settings in admin.
(You can make a test call using email or id to see if you are connected to Evercate, check console in webtools)

## Evercate settings / tags
In Evercate administration add a "tag" under Users / Hadle tags add a tag for each course or package etc. i.e "My package of 3 course" or "My course".

Under each course select the tag under Users / Handle user rules.

Thare are lot of ways to use tags in Evercate, so there might be other ways to explore. ;)

## Adding Kurs products
When creating a new WooCommerce-product, you will find Evercate-tab with settings for connecting the purchase to the corresponding kurs.
The "user group" is more or less always the same when connected this way, so it's a read only var.
The kurs-tagg is selected by clicking it in the input field (all courses should show up if connected correctly)

## WooCommerce purchase
When purchaes of product, is done (ie payment_complete) the cource is added to the user in Evercate, (and a new user is added if it does not exist).

## Basic flow
### Admin
Settings under Evercate + in admin menu.

### Product
Adds product like any other WooCommerce product.
Click the Evercate-tab and "Activate Evercate"
Click the cource button you want to add
Save product

### Customer
Shops cource adds to basket, checksout.

### Order
When order is payed (woocommerce_payment_complete or woocommerce_order_status_completed, so either if payement is completed via payment solution, or when status is changed to completed) info is posted to Evercate to create user as stated above.

### Evercate
User is added to cource and can go on and learning cool and interesting stuff.

## Disclaimer 
Only tested on one site, but should work on and WordPress / WooCommerce installation. 