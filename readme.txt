=== Gravity Forms Braintree Payments ===
Contributors: angelleye, Plugify, hello@lukerollans.me, gravityplus
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9CQZZGGMF78VY&source=url
Tags: gravity form, gravity forms, credit card, credit cards, payment, payments, braintree
Requires at least: 3.8
Tested up to: 5.2.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow your customers to purchase goods and services through Gravity Forms via Braintree Payments

== Description ==

Braintree Payments is a payment gateway provider owned by PayPal which allows you to process credit card payments without the need for a bank merchant account and full PCI-compliance. No sensitive data such as credit card numbers are stored on your server, Braintree takes care of everything.

 > Requires at least WordPress 3.8 and Gravity Forms 1.8

There are just a few simple steps to begin leveraging your Braintree Payments account:

1. Install Gravity Forms Braintree Payments.
2. Go to the Form Settings page for the form you wish to create a Braintree feed on.
3. You will be prompted to configure your Braintree settings. Click the link provided to do so.
4. Once you have configured your Braintree settings, return to the Form Settings page and follow the prompts.

= Features =

* Seamlessly integrates your Gravity Forms credit card forms with Braintree Payments.
* Supports both production and sandbox environments, enabling you to test payments before going live.
* Form entries will only be created when payment is successful.
* Quick and easy setup.

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of Gravity Forms Braintree Payments, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type Gravity Forms Braintree Payments and click Search Plugins. Once you've found our plugin (make sure it says "by Angell EYE") you can view details about it such as the the rating and description. Most importantly, of course, you can install it by simply clicking Install Now.

= Manual Installation =

1. Unzip the files and upload the folder into your plugins folder (/wp-content/plugins/) overwriting older versions if they exist
2. Activate the plugin in your WordPress admin area.

= Usage =

1. Navigate to the Form you wish to setup with a Braintree feed.
2. Under Form Settings, choose the Braintree option.

== Frequently asked questions ==

= What type of Braintree payments can be accepted? =
* For this early version, only one off payments can be accepted.

= Does this plugin support Braintree subscriptions? =
* Not yet.  This will be added based on future demand.

== Screenshots ==
1. Drop a credit card field collection directly into any Gravity Form.
2. Easily configure your Braintree settings, allowing for quick and efficient setup.
3. Quickly and easily configure payment feeds under Form Settings of any Gravity Form.
4. List of active feeds on the current form.

== Changelog ==

= 2.2.0 = 10.16.2019 =
* Feature - Adds Braintree field mapping capability. ([GFB-12](https://github.com/angelleye/gravity-forms-braintree/pull/14))
* Tweak - Adds a notice if you try to activate the Braintree Payments extension without Gravity Forms active.

= 2.1.3 - 07.23.2019 =
* Tweak - Update push notification system sync interval time. ([GFB-9](https://github.com/angelleye/gravity-forms-braintree/pull/11)) 

= 2.1.2 - 07.09.2019 =
* Tweak - Minor adjustments to API request.

= 2.1.1 - 05.31.2019 =
* Feature - Adds AE notification system. ([GFB-8](https://github.com/angelleye/gravity-forms-braintree/pull/10))
* Tweak - Adds text domain. ([GFB-7](https://github.com/angelleye/gravity-forms-braintree/pull/9))

= 2.1.0 =
* Feature - Adds AE Updater compatibility for future notices and automated updates. [GFB-4] ([GFB-5](https://github.com/angelleye/gravity-forms-braintree/pull/8))

= 2.0.0 =
* Fix - Updates Braintree Payments SDK and resolves failures with latest version of Gravity Forms. ([#1](https://github.com/angelleye/gravity-forms-braintree/issues/1))

= 1.1.2 =
* Internal maintenance release. Version 1.2 is coming soon and it's going to be big!

= 1.1.1 =
* Dashes and spaces are now removed from credit card number before sending to Braintree

= 1.1 =
* Fixed bug causing automatic settlement submission to fail

= 1.0 =
* Updated to latest Gravity Forms payment framework
* Added authorization validation. Form entries will no longer validate unless the payment has succeeded
* Payment information now displays on the entry page

= 0.8.1 =
* Stricter settings validation
* Fixed bug causing inactive feeds to process

= 0.8 =
* Most of plugin functionality

= 0.1 =
* Initial version of the plugin