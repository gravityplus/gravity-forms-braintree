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

	/**
	* Class constructor. Send __construct call to parent
	* @since 1.0
	* @return void
	*/
	public function __construct () {

		// Build parent
		parent::__construct();

	}

	/**
	* Override init_frontend to assign front end based filters and actions required for operation
	*
	* @since 1.0
	* @return void
	*/
	public function init_frontend () {

		// Filters for front end use
		add_filter( 'gform_validation', array( &$this, 'validate_credit_card_response' ) );

		// init_frontend on GFPaymentAddOn
		parent::init_frontend();

	}

	/**
	* After form has been submitted, send CC details to Braintree and ensure the card is going to work
	* If not, void the validation result (processed elsewhere) and have the submit the form again
	*
	* @param $feed - Current configured payment feed
	* @param $submission_data - Contains form field data submitted by the user as well as payment information (i.e. payment amount, setup fee, line items, etc...)
	* @param $form - Current form array containing all form settings
	* @param $entry - Current entry array containing entry information (i.e data submitted by users). NOTE: the entry hasn't been saved to the database at this point, so this $entry object does not have the "ID" property and is only a memory representation of the entry.
	* @return array - Return an $authorization array in the following format:
	* [
	*  "is_authorized" => true|false,
	*  "error_message" => "Error message",
	*  "transaction_id" => "XXX",
	*
	*  //If the payment is captured in this method, return a "captured_payment" array with the following information about the payment
	*  "captured_payment" => ["is_success"=>true|false, "error_message" => "error message", "transaction_id" => "xxx", "amount" => 20]
	* ]
	* @since 1.0
	* @return void
	*/
	protected function authorize( $feed, $submission_data, $form, $entry ) {

		if( $settings = $this->get_plugin_settings() ) {

			return array(
				'is_authorized' => true,
				'error_message' => 'Error message placeholder',
				'transaction_id' => 'Erterte#$435353'
			);

		}

	}

	/**
	* When a single payment has been authorized, perform the capture
	* @param $authorization - Contains the result of the authorize() function
	* @param $feed - Current configured payment feed
	* @param $submission_data - Contains form field data submitted by the user as well as payment information (i.e. payment amount, setup fee, line items, etc...)
	* @param $form - Current form array containing all form settings
	* @param $entry - Current entry array containing entry information (i.e data submitted by users).
	* @return array - Return an array with the information about the captured payment in the following format:
	* [
	*	"is_success"=>true|false,
	*	"error_message" => "error message",
	*	"transaction_id" => "xxx",
	*	"amount" => 20,
	*	"payment_method" => "Visa"
	*  ]
	*/
	protected function capture( $authorization, $feed, $submission_data, $form, $entry ) {

		return array(
			'is_success' => true,
			'error_message' => 'error message goes here',
			'transaction_id' => 'Erterte#$435353',
			'amount' => 50,
			'payment_method' => 'Visa'
		);

	}

	/**
	* Read the response from Braintree and invalidate the CC field if necessary
	* Leverages filter 'gform_validation'
	* @param $validation Gravity Forms validation object
	* @since 1.0
	* @return void
	*/
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

	/**
	* Propulate Transaction Type columns
	* @param $item List table (feed) item
	* @since 1.0
	* @return void
	*/
	public function get_column_value_txntype ( $item ) {
		return __( 'Single payment', 'gravity-forms-braintree' );
	}

	/**
	* Create and display feed settings fields
	*
	* @since 1.0
	* @return void
	*/
	public function feed_settings_fields() {
		return parent::feed_settings_fields();
	}

	/**
	* Create and display plugin settings fields. These are settings for Braintree in particular, not a feed
	*
	* @since 1.0
	* @return void
	*/
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

	/**
	* Helper function to determine if all Braintree settings have been set.
	* Does not check if they are correct, only that they have been set, IE not null
	* @param @settings Plugin settings to check if valid
	* @since 1.0
	* @return void
	*/
	public function settings_are_valid ( $settings ) {

		if( empty( $settings ) )
			return false;

		foreach( $settings as $setting )
			if( '' == $setting )
				return false;

		return true;

	}

	/**
	* Get plugin settings
	*
	* @since 1.0
	* @return void
	*/
	public function get_plugin_settings () {

		$settings = parent::get_plugin_settings();

		if( $this->settings_are_valid( $settings ) )
			return $settings;
		else
			return false;

	}

	/**
	* Configure columns which are displayed in the feed list table
	*
	* @since 1.0
	* @return void
	*/
	protected function feed_list_columns () {

		return array(
			'name' => __( 'Name', 'gravity-forms-braintree' ),
			'txntype' => __( 'Transaction Type', 'gravity-forms-braintree' )
		);

	}

}

?>
