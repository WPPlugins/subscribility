=== Troly for Wordpress ===
Contributors: subscribility
Tags: troly,woocommerce,wine,wine clubs,craft beers
Requires at least: 3.7.0
Tested up to: 4.7.5
Stable Tag: 2.1.7
Php version: 5.4 and above
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Let your customers order wine from your website. Keep your products, customers, clubs and orders in sync between your website and Troly. 

## Description

This plugin lets you sell your wines right from your website. You will be able to import all your products from your Troly account in just a click. 
Process online payments, sign up new customers to your clubs and manage everything from one place. 

Key features:

- Keep all your products and customers in sync between Wordpress and Troly.
- Capture sales directly on your website and process them in Troly
- Process payments using the payment gateway configured in Troly
- Sign up new members to your wine club
- Bulk product import / export
- Bulk customers and members import / export
- Import memberships / clubs

## Installation
*You need to install WooCommerce before installing Troly for Wordpress.*

1. Enable the Wordpress add-on in your Troly account (see screenshot)
2. Download and enable the plugin (or download and upload manually to your plugins folder)
3. Link your website to your Troly account (see [screenshot](https://wordpress.org/plugins/subscribility/screenshots/))

If you want to display a form that lets visitors signup to your clubs, you need to add the shortcode `[wp99234_registration_form]` to a page. 

Once that is done, you can run an initial import of all your customers, products and memberships in your Troly account. You can also export your customers and products **from** your website **to** Troly. 

## Frequently Asked Questions
### What do I need to use the plugin? 
The plugin is only useful if you have a Troly account to manage your operations. You can sign up for one [here](https://app.troly.io/users/sign_up)

### How can customers pay? 
The plugin will use whichever payment gateway you have configured in your Troly account. 

### How are shipping prices calculated? 
When a customer places an order, the shipping fee will be calculated based on the size of their order and the delivery address. Those prices are calculated based on the settings you have entered in your Troly account. 

### Do I need an SSL certificate (https)
SSL certificates improve the security of your website and are highly recommended. This is particularly important if there is sensitive information being entered on your website, like credit card details. However, they are not required for the plugin to work. 

### Is Guest Checkout available? 
As of version 2.1.3 it is now available, to enable it you will need to go into Settings page of the Woocommerce plugin, swap over to the Checkout tab and check the Enable guest checkout checkbox then save.

### Where do product images come from? 
Any image you upload for a product in Troly will be used to display the product on your website. 
You can now also choose to upload images in Woocommerce, on a product's page. Please note all images must come from Troly OR from Woocommerce. It is not possible for some images to come from Troly, while others are uploaded via Woocommerce. 
To select where images are coming from, go to the Settings page of the plugin, and check or uncheck the "Use Woocommerce product images" checkbox. 

== Screenshots ==
1. This screenshot shows how to install the Wordpress add-on in Troly

## Changelog
###Version 2.1.7
- Improved import and export messages when pulling or pushing products

###Version 2.1.6
- Resolve an issue with membership prices not appearing
- Improved performance for importing products from Troly

###Version 2.1.5
- Rebranded the plugin to be under the [Troly](https://troly.io) name

###Version 2.1.4
- Added a filter ('wp99234_rego_form_membership_options') for reordering club memberships on the sign up/registration page.

###Version 2.1.3
- Added support for Woocommerce guest checkout

###Version 2.1.2
- Added support for Woocommerce shipping zones
- All notifications are now enabled by default for all new members

###Version 2.1.0
- IMPORTANT UPDATE: fixes an issue with the product quantities sent to Troly when an order is placed
- Fixes a redirection issue on the membership signup form

###Version 2.0
Our biggest overhaul of the plugin to date. 

- Improved interface for a better user experience
- Choose between using images uploaded in Troly, or directly in Woocommerce
- Override the default signup form template by copying into a folder called wp99234 in your child theme's root folder
- Only one phone number is necessary to sign up to your clubs, instead of two
- Translate product variations into a number of bottles (6 packs, 12 packs etc...)
- Several bugfixes and smaller improvements

###Version 1.2.31

- First official release

## Upgrade notice
### 2.0
Version 2 of the plugin uses version 1.1 of the Wordpress add-on in Troly. Support for version 1.0 of the Wordpress add-on is now deprecated. 


### 1.2.31 ###
This version makes updating the plugin much easier. It also fixes a number of issues with the member signup process. 

## Dependencies
+ Woocommerce Version 3.0.0 (tested up to 3.0.9)
* An active [Troly](https://troly.io/) account
