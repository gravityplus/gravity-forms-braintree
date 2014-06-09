<?php

// Plugify_GForm_Braintree class

final class Plugify_GForm_Braintree extends GFFeedAddOn {

	protected $_version = '0.9';
	protected $_min_gravityforms_version = '1.7.9999';
	protected $_slug = 'gravity-forms-braintree';
	protected $_path = 'gravity-forms-braintree/init.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://plugify.io/plugins/gravity-forms-braintree';
	protected $_title = 'Braintree Payments';
	protected $_short_title = 'Braintree';

	public function __construct () {

		// Build parent
		parent::__construct();

		// Register filters
		add_filter( 'gform_enable_credit_card_field', array( &$this, 'enable_credit_card' ), 10, 1 );

	}

	public function get_column_value_form ( $item ) {

		$form = GFAPI::get_form( $item['form_id'] );
		return __( $form['title'], 'gravity-forms-braintree' );

	}

	public function get_column_value_txntype ( $item ) {
		return __( 'Single payment', 'gravity-forms-braintree' );
	}

	public function feed_settings_fields() {

		global $wpdb;

		if( $forms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rg_form WHERE `is_active` = 1", OBJECT ) ) {

			$form_choices = array();

			$form_choices[] = array(
				'label' => 'Select a form',
				'value' => ''
			);

			foreach( $forms as $form )
			$form_choices[] = array(
				'label' => $form->title,
				'value' => $form->id
			);

			$fields = array();

		}

    return array(

      array(
        'fields' => array(
					array(
						'label' => 'Name',
						'type' => 'text',
						'name' => 'name',
						'value' => '',
						'class' => 'small',
						'required' => 1
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
								'required' => 1,
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
							),
							array(
								'name' => 'phone',
								'label' => 'Phone (optional)',
								'required' => 0
							),
							array(
								'name' => 'cc_number',
								'label' => 'Credit Card Number',
								'required' => 1
							),
							array(
								'name' => 'cc_expiry',
								'label' => 'Credit Card Expiry',
								'required' => 1
							),
							array(
								'name' => 'cc_security_code',
								'label' => 'Security Code (eg CVV)',
								'required' => 1
							),
							array(
								'name' => 'cc_cardholder',
								'label' => 'Cardholder Name',
								'required' => 1
							),
							array(
								'name' => 'amount',
								'label' => 'Payment Amount',
								'required' => 1
							)
          	)
          )
        )
      )

    );

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
				'title' => 'Payment Settings',
				'fields' => array(
					array(
						'name' => 'settlement',
						'tooltip' => 'Choosing \'Yes\' will tell Braintree to automatically submit your transactions for settlement upon receipt',
						'label' => 'Automatic Settlement Submission',
						'type' => 'radio',
						'choices' => array(
							array(
								'label' => 'Yes',
								'name' => 'yes'
							),
							array(
								'label' => 'No',
								'name' => 'no'
							)
						)
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

	public function settings_are_valid ( $settings ) {

		if( empty( $settings ) )
			return false;

		foreach( $settings as $setting )
			if( '' == $setting )
				return false;

		return true;

	}

	public function get_plugin_settings () {

		$settings = parent::get_plugin_settings();

		if( $this->settings_are_valid( $settings ) )
			return $settings;
		else
			return false;

	}

	protected function feed_list_columns () {

		return array(
			'name' => __( 'Name', 'gravity-forms-braintree' ),
			'txntype' => __( 'Transaction Type', 'gravity-forms-braintree' )
		);

	}

	public function get_action_links () {

		return array(
			'edit' => '<a title="' . __( 'Edit this feed', 'gravity-forms-braintree' ) . '" href="' . add_query_arg( array( 'fid' => "{id}" ) ) . '">' . __( 'Edit', 'gravity-forms-braintree' ) . '</a>',
			'delete' => '<a title="' . __( 'Delete this feed', 'gravity-forms-braintree' ) . '" class="submitdelete" href="javascript:void();" data-feed-id="' . "{id}" . '">' . __( 'Delete', 'gravity-forms-braintree' ) . '</a>',
		);

	}

	public function process_feed( $feed, $entry, $form ) {

		if( $feed['is_active'] == 0 )
			return false;

		// Proceed only if settings exist
		if( $settings = $this->get_plugin_settings() ) {

			// Build Braintree HTTP request parameters
			$args = array(

				'amount' => str_replace( ',', '', trim( $entry[ $feed['meta']['gf_braintree_mapped_fields_amount'] ], "$ \t\n\r\0\x0B" ) ),
				'orderId' => $entry['id'],
				'creditCard' => array(
					'number' => $_POST[ 'input_' . str_replace( '.', '_', $feed['meta']['gf_braintree_mapped_fields_cc_number'] ) ],
					'expirationDate' => implode( '/', $_POST[ 'input_' . str_replace( '.', '_', $feed['meta']['gf_braintree_mapped_fields_cc_expiry'] ) ] ),
					'cardholderName' => $_POST[ 'input_' . str_replace( '.', '_', $feed['meta']['gf_braintree_mapped_fields_cc_cardholder'] ) ],
					'cvv' => $_POST[ 'input_' . str_replace( '.', '_', $feed['meta']['gf_braintree_mapped_fields_cc_security_code'] ) ]
				),
				'customer' => array(
					'firstName' => $entry[ $feed['meta']['gf_braintree_mapped_fields_first_name'] ],
					'lastName' => $entry[ $feed['meta']['gf_braintree_mapped_fields_last_name'] ],
					'email' => $entry[ $feed['meta']['gf_braintree_mapped_fields_email'] ]
				)

			);

			// Include phone if present
			if( !empty( $feed['meta']['gf_braintree_mapped_fields_phone'] ) )
			$args['customer']['phone'] = $entry[ $feed['meta']['gf_braintree_mapped_fields_phone'] ];

			// Include company name if present
			if( !empty( $feed['meta']['gf_braintree_mapped_fields_company'] ) )
			$args['customer']['company'] = $entry[ $feed['meta']['gf_braintree_mapped_fields_company'] ];

			// Configure automatic settlement
			if( $settings['settlement'] == 'Yes' )
			$args['options']['submitForSettlement'] = 'true';

			// Configure Braintree environment
			Braintree_Configuration::environment( strtolower( $settings['environment'] ) );
			Braintree_Configuration::merchantId( $settings['merchant-id']);
			Braintree_Configuration::publicKey( $settings['public-key'] );
			Braintree_Configuration::privateKey( $settings['private-key'] );

			// Send query to Braintree and parse result
			$result = Braintree_Transaction::sale( $args );

			// Update entry meta with Braintree response
			if( $result->success ) {

				gform_update_meta( $entry['id'], 'payment_status', $result->transaction->_attributes['status'] );
				gform_update_meta( $entry['id'], 'transaction_id', $result->transaction->_attributes['id'] );
				gform_update_meta( $entry['id'], 'payment_amount', '$' . $result->transaction->_attributes['amount'] );
				gform_update_meta( $entry['id'], 'payment_method', 'Braintree (' . $result->transaction->_attributes['creditCard']['cardType'] . ')' );

			}
			else {
				gform_update_meta( $entry['id'], 'payment_status', 'failed' );
			}

		}

	}

	public function enable_credit_card () {
		return true;
	}

}

?>
