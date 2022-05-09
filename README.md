# snillrik-evercate
 Plugin for WooCommerce to interact with Evercate API. Very simple to use but pretty smart way of selling Evercate courses in Woo.

## install
Install as any other plugin. 
Add the credentials you get from Evercate under Evercate+ settings in admin.
(You can make a test call using email or id to see if you are connected to Evercate, check console in webtools)

## Adding Kurs products
When creating a new product, you will find Evercate-tab that can be used to make the product also post the new user to the corresponding kurs.
The user group is more or less always the same when connected this way, so it's a read only var.
The kurs-tagg is selected by clicking it below the input field (all courses should show up if connected correctly)

## WooCommerce purchase
When purchaes of product, (with Evercate activated), is done (ie payment_complete) the cource is added to the Evercate-user, (and a new user is added if it does not exist).
