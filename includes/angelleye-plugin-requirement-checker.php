<?php

/**
 * This class checks the defined requirements for any plugin
 */

defined('ABSPATH') or die('Direct access not allowed');

if(!class_exists('Angelleye_Plugin_Requirement_Checker')){
	class Angelleye_Plugin_Requirement_Checker {
		private $required_plugins = [];
		private $required_classes = [];
		private $required_extensions = [];
		private $deactivate_plugins = [];
		private $errors_list = [];
		private $plugin_name, $plugin_version, $plugin_check_option_name, $base_plugin_file;

		public function __construct( $plugin_name, $plugin_version, $base_plugin_file ) {
			$this->plugin_name = $plugin_name;
			$this->plugin_version = $plugin_version;
			$this->base_plugin_file = $base_plugin_file;
			$this->plugin_check_option_name = sanitize_title($this->plugin_name.'-'.$this->plugin_version);

			//add_action( 'update_option_active_sitewide_plugins', [$this, 'checkPluginDeactivation'], 10, 2 );
			//add_action( 'update_option_active_plugins', [$this, 'checkPluginDeactivation'], 10, 2 );
		}

		public function setDeactivatePlugins( $plugin_basenames = [] ) {
			$this->deactivate_plugins = array_merge($this->deactivate_plugins, $plugin_basenames);
		}

		/**
		 * Accepts a key value array, where key defines the plugin name and value defines the min version required
		 * @param array $classes
		 */
		public function setRequiredPlugins( $plugin_names = [] ) {
			$this->required_plugins = array_merge($this->required_plugins, $plugin_names);
		}

		/**
		 * Accepts a key value array, where key defines the class name and value defines the error message
		 * @param array $classes
		 */
		public function setRequiredClasses( $classes = [] ) {
			$this->required_classes = array_merge($this->required_classes, $classes);
		}

		/**
		 * Accepts a version string to check
		 * @param $version_required
		 */
		public function setPHP( $version_required ) {
			if (version_compare(PHP_VERSION, $version_required, '<')) {
				$this->errors_list['VERSION_PHP'] = __('<b>PHP version >= '.$version_required.'</b> is required to run '. $this->plugin_name);
			}
		}

		public function setRequiredExtensions( $extensions ) {
			$this->required_extensions = array_merge($this->required_extensions, $extensions);
		}

		public function check( $force_check = false ) {
			$check = get_option($this->plugin_check_option_name);
			if($check=='passed' && !$force_check)
				return true;

			if(count($this->required_classes)){
				foreach ($this->required_classes as $class_name => $class_msg){
					if(!class_exists($class_name))
						$this->errors_list['CLASS_'.$class_name] = $class_msg;
				}
			}

			if(count($this->required_plugins)){
				$all_plugins = get_plugins();
				$active_plugins = get_option('active_plugins');
				foreach ($this->required_plugins as $single_plugin => $min_version_required){
					if(in_array($single_plugin, $active_plugins)){
						if(version_compare($all_plugins[$single_plugin]['Version'], $min_version_required, '<')){
							$this->errors_list[] = 'You have <b>'.$all_plugins[$single_plugin]['Name'].' -  Version: '.$all_plugins[$single_plugin]['Version'].'</b> active, we require min <b>'.$all_plugins[$single_plugin]['Name'].' - Version: '.$min_version_required.'</b> to run the '.$this->plugin_name;
						}
					}else if(isset($all_plugins[$single_plugin])) {
						$this->errors_list[] = 'Please activate <b>'.$all_plugins[$single_plugin]['Name'].'</b> plugin to run the '.$this->plugin_name;
					} else{
						$this->errors_list[] = 'Please install <b>'.$all_plugins[$single_plugin]['Name'].'</b> plugin.';
					}
				}
				/*echo '<pre>';
				print_r($all_plugins);
				print_r($active_plugins);die;*/

			}

			if(count($this->required_extensions)) {
				$ext_error = [];
				foreach ( $this->required_extensions AS $ext ) {
					if ( ! extension_loaded( $ext ) ) {
						$ext_error[] = $ext;
					}
				}
				if(count($ext_error)){
					$this->errors_list[] = implode(', ', $ext_error).' extensions are required for the '.$this->plugin_name;
				}
			}
			
			if(count($this->errors_list)){
				add_action('admin_notices', [$this, 'showAdminNotice']);
				delete_option($this->plugin_check_option_name);
				return $this->errors_list;
			}else {
				update_option($this->plugin_check_option_name, 'passed');
				return true;
			}
		}

		public function showAdminNotice(  ) {

			echo '<div class="notice notice-error" id=" is-dismissible">
             <p><b>'.(in_array($this->base_plugin_file, $this->deactivate_plugins)?
					$this->plugin_name.' plugin has been deactivated due to following errors.':
					$this->plugin_name.' plugin is inactive due to following errors.').'</b></p>
             <p>'.implode('<br/>', $this->errors_list).'</p>
         </div>';
			if(count($this->deactivate_plugins)){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				foreach ($this->deactivate_plugins as $single_plugin_basename){
					if ( is_plugin_active( $single_plugin_basename ) ) {
						deactivate_plugins( $single_plugin_basename );
					}
				}
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}

		public function checkPluginDeactivation( $new_value, $old_value ) {
			if($this->check(true)!== true){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				foreach ($this->deactivate_plugins as $single_plugin_basename)
					deactivate_plugins($single_plugin_basename, true);
			}
		}
	}
}