=== Gravity Forms Braintree Add-On ===
Contributors: Plugify, hello@lukerollans.me
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hello%40plugify%2eio&lc=GB&item_name=Plugin%20Development%20Donation&currency_code=USD
Tags: credit card,braintree,gravity form,payment
Requires at least: 3.8
Tested up to: 3.9
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow your customers to purchase goods and services through Gravity Forms via Braintree Payments

== Description ==

Braintree Payments is a payment gateway provider owned by eBAY Inc, which allows you to proces credit card payments without the need for a bank merchant account and full PCI-compliance. No sensitive data such as credit card numbers are stored on your server, Braintree takes care of everything.

 > Requires at least WordPress 3.8 and Gravity Forms 1.8

There are just a few simple steps to begin leveraging your Braintree Payments account:

1. Install Gravity Forms Braintree Add-On
2. Go to the Form Settings page for the form you wish to create a Braintree feed on
3. You will be prompted to configure your Braintree settings. Click the link provided to do so.
4. Once you have configured your Braintree settings, return to the Form Settings page and follow the prompts.

= Features =

* Seamlessly integrates your Gravity Forms credit card forms with Braintree Payments
* Supports both production and sandbox environments, enabling you to test payments before going live
* Form entries will only be created when payment is successful
* Quick and easy setup

If you have found this plugin useful, consider taking a moment to rate it, or perhaps even a small donation.

== Installation ==

1. Upload the `gravity-forms-braintree` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the Form you wish to setup with a Braintree feed.
4. Under Form Settings, choose the Braintree option.

== Frequently asked questions ==

= What type of Braintree payments can be accepted? =
For this early version, only one off payments can be accepted. Subscriptions will be available in version 1.1

= Can I use conditional logic? EG, I only want to register a user if the Braintree payment was successful =
In version 1.0, no. This is planned for version 1.2, coming very soon

= Does this plugin support Braintree subscriptions? =
Not currently, no. This will be released very shortly in version 1.1

= Available filters and actions =
No filters are currently available for this pre-release version

== Screenshots ==
1. Easily configure your Braintree settings, allowing for quick and efficient setup.
2. Quickly and easily configure payment feeds under Form Settings of any Gravity Form
3. List of active feeds on the current form

== Changelog ==

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

== Upgrade notice ==

IMPORTANT! Version 1.0 is a complete overhaul from the previous version. Your existing feeds will not work. Please make sure you check all your feeds and ensure they function correctly.
