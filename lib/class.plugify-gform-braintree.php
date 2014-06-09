<?php

// Plugify_GForm_Braintree class

final class Plugify_GForm_Braintree extends GFPaymentAddOn {

	protected $_version = '1.0';

  protected $_min_gravityforms_version = '1.8.7.16';
  protected $_slug = 'gravity-forms-braintree';
  protected $_path = 'gravity-forms-braintree/lib/class.plugify-gform-braintree.php';
  protected $_full_path = __FILE__;
  protected $_title = 'Gravity Forms Braintree Add-On';
  protected $_short_title = 'Braintree';
  protected $_requires_credit_card = true;
  protected $_supports_callbacks = false;
  protected $_enable_rg_autoupgrade = true;

	public function __construct () {

		// Build parent
		parent::__construct();

	}

	public function init_frontend () {

		// Filters for front end use
		add_filter( 'gform_validation', array( &$this, 'validate_credit_card_response' ) );

	}

	public function authorize( $feed, $submission_data, $form, $entry ) {

		

	}

	public function validate_credit_card_response ( $validation ) {

		// Return unfiltered result if no Braintree feed is configured
		if( !$this->has_feed( $validation['form']['id'] ) ) {
			return $validation;
		}

		// Loop through fields in form until credit card field is found
		foreach( $validation['form']['fields'] as &$field ) {

			// Skip to next iteration if this is not the credit card field
	    if( GFFormsModel::get_input_type( $field ) != 'creditcard' ) {
				continue;
			}

			// There shouldn't be more than one cc field per form, so break once it has been found and processed
			break;

		}

		return $validation;

	}

	public function get_column_value_form ( $item ) {

		$form = GFAPI::get_form( $item['form_id'] );
		return __( $form['title'], 'gravity-forms-braintree' );

	}

	public function get_column_value_txntype ( $item ) {
		return __( 'Single payment', 'gravity-forms-braintree' );
	}

	public function feed_settings_fields() {

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

}

?>
