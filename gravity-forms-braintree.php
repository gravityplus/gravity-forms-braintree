<?php
/*
Plugin Name: Gravity Forms Braintree Add-On
Plugin URI: http://plugify.io/
Description: Allow your customers to purchase goods and services through Gravity Forms via Braintree Payments
Author: Plugify
Version: 1.1.2
Author URI: http://plugify.io
*/

// Ensure WordPress has been bootstrapped
if( !defined( 'ABSPATH' ) ) {
	exit;
}

$path = trailingslashit( dirname( __FILE__ ) );

// Ensure Gravity Forms (payment addon framework) is installed and good to go
if( is_callable( array( 'GFForms', 'include_payment_addon_framework' ) ) ) {

	// Bootstrap payment addon framework
	GFForms::include_payment_addon_framework();

	// Require Braintree Payments core
	require_once $path . 'lib/Braintree.php';

	// Require plugin entry point
	require_once $path . 'lib/class.plugify-gform-braintree.php';

	// Fire off entry point
	new Plugify_GForm_Braintree();

}

?>
