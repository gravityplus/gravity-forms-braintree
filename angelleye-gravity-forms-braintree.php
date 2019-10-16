<?php
/**
 * Plugin Name: Gravity Forms Braintree Payments
 * Plugin URI: https://angelleye.com/products/gravity-forms-braintree-payments
 * Description: Allow your customers to purchase goods and services through Gravity Forms via Braintree Payments.
 * Author: Angell EYE
 * Version: 2.2.0
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

require_once dirname(__FILE__) . '/includes/angelleye-gravity-braintree-activator.php';

class AngelleyeGravityFormsBraintree{

    protected static $instance = null;
    public static $plugin_base_file;

    public static function getInstance()
    {
        self::$plugin_base_file = plugin_basename(__FILE__);
        if(self::$instance==null)
            self::$instance = new AngelleyeGravityFormsBraintree();

        return self::$instance;
    }

    public function __construct()
    {
        register_activation_hook( __FILE__, array(AngelleyeGravityBraintreeActivator::class,"InstallDb") );
        register_deactivation_hook( __FILE__, array(AngelleyeGravityBraintreeActivator::class,"DeactivatePlugin") );
        register_uninstall_hook( __FILE__, array(AngelleyeGravityBraintreeActivator::class,'Uninstall'));

        add_action( 'update_option_active_sitewide_plugins', array(AngelleyeGravityBraintreeActivator::class,'otherPluginDeactivated'), 10, 2 );
        add_action( 'update_option_active_plugins', array(AngelleyeGravityBraintreeActivator::class,'otherPluginDeactivated'), 10, 2 );

        $this->init();
    }

    public function init()
    {
        $path = trailingslashit( dirname( __FILE__ ) );

        // Ensure Gravity Forms (payment addon framework) is installed and good to go
        if( is_callable( array( 'GFForms', 'include_payment_addon_framework' ) ) ) {

            // Bootstrap payment addon framework
            GFForms::include_payment_addon_framework();

            // Require Braintree Payments core
            require_once $path . 'lib/Braintree.php';

            // Require plugin entry point
            require_once $path . 'lib/class.plugify-gform-braintree.php';
            require_once $path . 'lib/angelleye-gravity-forms-payment-logger.php';
            require_once $path . 'includes/angelleye-gravity-braintree-field-mapping.php';

            /**
             * Required functions
             */
            if (!function_exists('angelleye_queue_update')) {
                require_once( 'includes/angelleye-functions.php' );
            }

            // Fire off entry point
            new Plugify_GForm_Braintree();
            new AngelleyeGravityBraintreeFieldMapping();
            AngellEYE_GForm_Braintree_Payment_Logger::instance();

        }
    }

    public static function isBraintreeFeedActive()
    {
        global $wpdb;
        $addon_feed_table_name = $wpdb->prefix . 'gf_addon_feed';
        $is_active = $wpdb->get_var("select is_active from ".$addon_feed_table_name." where addon_slug='gravity-forms-braintree' and is_active=1");

        return $is_active=='1';
    }
}

AngelleyeGravityFormsBraintree::getInstance();