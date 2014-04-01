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

		$table = $this->get_feed_table(null);

		$table->prepare_items();
		$table->display();

	}

	protected function feed_list_columns () {

		return array(
			'form' => __( 'Form', 'gravity-forms-braintree' ),
			'txntype' => __( 'Transaction Type', 'gravity-forms-braintree' )
		);

	}

	public function feed_list_no_item_message () {
		return sprintf(__("<p style=\"padding: 10px 5px 5px;\">You don't have any feeds configured. Let's go %screate one%s!</p>", "gravityforms"), "<a href='" . add_query_arg(array("fid" => 0)) . "'>", "</a>");
	}

	public function plugin_settings_fields () {

		return array(

      array(
        'title' => 'Account Settings',
        'fields' => array(
          array(
            'name' => 'merchant-id',
            'tooltip' => 'Your Braintree Merchant ID',
            'label' => 'Merchant ID',
            'type' => 'text',
            'class' => 'small'
          ),
					array(
						'name' => 'public-key',
						'tooltip' => 'Your Braintree Account Public Key',
						'label' => 'Public Key',
						'type' => 'text',
						'class' => 'small'
					),
					array(
						'name' => 'private-key',
						'tooltip' => 'Your Braintree Account Private Key',
						'label' => 'Private Key',
						'type' => 'text',
						'class' => 'small'
					)
        )
      ),
			array(
				'title' => 'Environment Settings',
				'fields' => array(
					array(
						'name' => 'environment',
						'tooltip' => 'Do you want to process test payments or real payments?',
						'label' => 'API Endpoint',
						'type' => 'radio',
						'choices' => array(
							array(
								'label' => 'Sandbox',
								'name' => 'sandbox'
							),
							array(
								'label' => 'Production',
								'name' => 'production'
							)
						)
					)
				)
			)

    );

	}

}

?>
