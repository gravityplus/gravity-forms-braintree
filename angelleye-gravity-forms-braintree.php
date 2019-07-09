<?php
/**
 * Plugin Name: Gravity Forms Braintree Payments
 * Plugin URI: https://angelleye.com/products/gravity-forms-braintree-payments
 * Description: Allow your customers to purchase goods and services through Gravity Forms via Braintree Payments.
 * Author: Angell EYE
 * Version: 2.1.2
 * Author URI: https://angelleye.com
 * Text Domain: angelleye-gravity-forms-braintree

 *************
 * Attribution
 *************
 * This plugin is a derivative work of the code from Plugify,
 * which is licensed with GPLv2.
 */

// Ensure WordPress has been bootstrapped
if( !defined( 'ABSPATH' ) ) {
	exit;
}

if (!defined('AEU_ZIP_URL')) {
    define('AEU_ZIP_URL', 'https://updates.angelleye.com/ae-updater/angelleye-updater/angelleye-updater.zip');
}

if (!defined('GRAVITY_FORMS_BRAINTREE_ASSET_URL')) {
    define('GRAVITY_FORMS_BRAINTREE_ASSET_URL', plugin_dir_url(__FILE__));
}

if (!defined('PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL')) {
    define('PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL', 'https://www.angelleye.com/');
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

    /**
     * Required functions
     */
    if (!function_exists('angelleye_queue_update')) {
        require_once( 'includes/angelleye-functions.php' );
    }
    
	// Fire off entry point
	new Plugify_GForm_Braintree();

}
