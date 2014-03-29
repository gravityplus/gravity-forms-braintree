<?php

// Plugify_GForm_Braintree class

final class Plugify_GForm_Braintree extends GFFeedAddOn {

	protected $_version = '0.1';
	protected $_min_gravityforms_version = '1.7.9999';
	protected $_slug = 'gravity-forms-braintree';
	protected $_path = 'gravity-forms-braintree/init.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://plugify.io/plugins/gravity-forms-braintree';
	protected $_title = 'Braintree Payments';
	protected $_short_title = 'Braintree';

	public function _construct () {

		Braintree_Configuration::environment( 'sandbox' );
		Braintree_Configuration::merchantId( 'p6yj396vmycsdydq' );
		Braintree_Configuration:publicKey( 't3245jnyhtcsphsw' );
		Braintree_Configuration::privateKey( '609b4e2b41a61f087c9b0758e1e70a86' );

		parent::_construct();

	}

	public function init () {

		parent::init();

	}

	public function plugin_page () {

		echo '<p>:D</p>';

	}

	public function form_settings_fields () {


	}

	public function plugin_settings_fields () {



	}

}

?>
