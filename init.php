<?php
/*
Plugin Name: Gravity Forms + Braintree
Plugin URI: http://plugify.io/plugin/gravity-forms-braintree
Description: Allows you to accept credit card payments using Gravity Forms and Braintree Payments
Author: Plugify
Version: 0.9
Author URI: http://plugify.io
*/

// Ensure WordPress has been bootstrapped
if( !defined( 'ABSPATH' ) ) {
	exit;
}

$path = trailingslashit( dirname( __FILE__ ) );

// Ensure Gravity Forms (payment addon framework) is installed and good to go
if( is_callable( array( 'GFForms', 'include_payment_addon_framework' ) ) ) {

	GFForms::include_payment_addon_framework();

	// Require Braintree Payments core
	require_once $path . 'lib/Braintree.php';

	// Require plugin entry point
	require_once $path . 'lib/class.plugify-gform-braintree.php';
	require_once $path . 'lib/class.plugify-gfaddonfeedstable.php';

	// Fire off entry point
	new Plugify_GForm_Braintree();

}

?>
