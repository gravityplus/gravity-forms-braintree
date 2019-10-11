<?php

defined('ABSPATH') or die('Direct access not allowed');
class AngelleyeGravityBraintreeActivator
{
    public static function InstallDb(){
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            wp_die('This plugin requires gravity form to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
        }
        self::defaultPluginSetting();
    }

    public static function DeactivatePlugin(){

    }

    public static function Uninstall(){

    }

    public static function defaultPluginSetting(){

    }

    public static function otherPluginDeactivated($plugin)
    {
        if(!is_plugin_active( 'gravityforms/gravityforms.php' ) || !class_exists('GFForms')){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            deactivate_plugins(AngelleyeGravityFormsBraintree::$plugin_base_file, true);
        }
    }
}