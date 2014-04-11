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

	public function plugin_page () {

		if( isset( $_GET['fid'] ) ) {

			$feed = $this->get_feed( $_GET['fid'] );
			$form = GFAPI::get_form( $feed['form_id'] );

			$this->feed_edit_page( $form, $feed['id'] );

		}
		else
			$this->feed_list_page();

	}

	public function insert_feed ( $form_id, $is_active, $meta ) {

		global $wpdb;

		if( $feed_id = parent::insert_feed( $form_id, $is_active, $meta ) ) {

			$wpdb->update( "{$wpdb->prefix}gf_addon_feed", array( 'form_id' => $_POST['form_id'] ), array( 'id' => $feed_id ) );
			return $feed_id;

		}

	}

	public function save_feed_settings ( $feed_id, $form_id, $settings ) {

		global $wpdb;

		if( parent::save_feed_settings( $feed_id, $form_id, $settings ) )
		return $wpdb->update( "{$wpdb->prefix}gf_addon_feed", array( 'form_id' => $settings['form_id'] ), array( 'id' => $feed_id ) );

	}

	public function feed_settings_fields() {

		global $wpdb;

		if( $forms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rg_form WHERE `is_active` = 1", OBJECT ) ) {

			$choices = array();

			$choices[] = array(
				'label' => 'Select a form',
				'value' => ''
			);

			foreach( $forms as $form )
			$choices[] = array(
				'label' => $form->title,
				'value' => $form->id
			);

		}

    return array(

      array(
        'fields' => array(
          array(
            'label' => 'Gravity Form',
            'type' => 'select',
            'name' => 'form_id',
            'class' => 'small',
						'choices' => $choices
          ),
					array(
						'label' => '',
						'type' => 'hidden',
						'name' => 'transaction_type',
						'value' => 'Single Payment',
						'class' => 'small'
					),
          array(
            'name' => 'gf_braintree_mapped_fields',
            'label' => 'Map Fields',
            'type' => 'field_map',
            'field_map' => array(
							array(
								'name' => 'first_name',
								'label' => 'First Name',
								'required' => 1
							),
							array(
								'name' => 'last_name',
								'label' => 'Last Name',
								'required' => 1
							),
							array(
								'name' => 'company',
								'label' => 'Company (optional)',
								'required' => 0
							),
							array(
								'name' => 'email',
								'label' => 'Email',
								'required' => 1
							)
							,
							array(
								'name' => 'phone',
								'label' => 'Phone (optional)',
								'required' => 0
							)
          	)
          )
        )
      )

    );

  }

	public function get_column_value_form( $item ) {

		$form = GFAPI::get_form( $item['form_id'] );
		return __( $form['title'], 'gravity-forms-braintree' );

	}

	public function get_column_value_txntype( $item ) {
		return __( 'Single payment', 'gravity-forms-braintree' );
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

	protected function feed_list_columns () {

		return array(
			'form' => __( 'Form', 'gravity-forms-braintree' ),
			'txntype' => __( 'Transaction Type', 'gravity-forms-braintree' )
		);

	}

	public function feed_list_no_item_message () {
		return sprintf(__("<p style=\"padding: 10px 5px 5px;\">You don't have any Braintree feeds configured. Let's go %screate one%s!</p>", "gravityforms"), "<a href='" . add_query_arg(array("fid" => 0)) . "'>", "</a>");
	}

	public function process_feed( $feed, $entry, $form ) {

		Braintree_Configuration::environment( 'sandbox' );
		Braintree_Configuration::merchantId( 'p6yj396vmycsdydq' );
		Braintree_Configuration::publicKey( 't3245jnyhtcsphsw' );
		Braintree_Configuration::privateKey( '609b4e2b41a61f087c9b0758e1e70a86' );

		$result = Braintree_Customer::create(array(
			'firstName' => 'Mike',
			'lastName' => 'Jones',
			'company' => 'Jones Co.',
			'email' => 'mike.jones@example.com',
			'phone' => '281.330.8004',
			'fax' => '419.555.1235',
			'website' => 'http://example.com'
		));

	}

}

?>
