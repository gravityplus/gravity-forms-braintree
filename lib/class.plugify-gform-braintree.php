<?php

// Plugify_GForm_Braintree class

final class Plugify_GForm_Braintree extends GFPaymentAddOn {

    protected $_version = '3.1.0';

    protected $_min_gravityforms_version = '1.8.7.16';
    protected $_slug = 'gravity-forms-braintree';
    protected $_path = 'gravity-forms-braintree/lib/class.plugify-gform-braintree.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Braintree';
    protected $_short_title = 'Braintree';
    protected $_requires_credit_card = true;
    protected $_supports_callbacks = false;
    protected $_enable_rg_autoupgrade = true;

    protected $selected_payment_method = 'creditcard';

    /**
     * Class constructor. Send __construct call to parent
     * @since 1.0
     * @return void
     */
    public function __construct () {

        add_action('wp_ajax_angelleye_gform_braintree_adismiss_notice', array($this, 'angelleye_gform_braintree_adismiss_notice'), 10);
        add_action('admin_notices', array($this, 'angelleye_gform_braintree_display_push_notification'), 10);

        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_css'), 10);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_js'), 10);
	    add_filter( 'gform_noconflict_scripts', [$this, 'include_angelleye_braintree_script_noconflict'] );
	    add_filter( 'gform_noconflict_styles', [$this, 'include_angelleye_braintree_style_noconflict'] );
        // Build parent
        parent::__construct();
    }

	/**
	 * Override credit card field check, so that we can return true when someone has ach form
	 * If any user will have any form then same payment gateway class will be used
	 * @param array $form
	 *
	 * @return bool
	 */
	public function has_credit_card_field( $form ) {
		if(isset($form['fields'])) {
			foreach ($form['fields'] as $single_field) {
				if ($single_field->type == 'creditcard' || $single_field->type=='braintree_ach') {
					return true;
				}
			}
		}
		return $this->get_credit_card_field( $form ) !== false;
	}

	/**
	 * Override default message for Gravity Form Braintree Feeds
	 * @return string
	 */
	public function requires_credit_card_message() {
		$url = add_query_arg( array( 'view' => null, 'subview' => null ) );

		return sprintf( esc_html__( "You must add a Credit Card/ACH Payment field to your form before creating a feed. Let's go %sadd one%s!", 'gravityforms' ), "<a href='" . esc_url( $url ) . "'>", '</a>' );
	}

    /**
     * Override init_frontend to assign front end based filters and actions required for operation
     *
     * @since 1.0
     * @return void
     */
    public function init_frontend () {

        // init_frontend on GFPaymentAddOn
        parent::init_frontend();

    }

	/**
	 * Init the Braintree configuration and return gateway for transactions, etc.
	 * @return bool|\Braintree\Gateway
	 * @throws \Braintree\Exception\Configuration
	 */
	public function getBraintreeGateway() {
		$settings = $this->get_plugin_settings();
		if(!$settings)
			return false;

		// Configure Braintree environment
		$braintree_config = new \Braintree\Configuration([
			'environment' => strtolower( $settings['environment'] ) ,
			'merchantId' => $settings['merchant-id'],
			'publicKey' => $settings['public-key'],
			'privateKey' => $settings['private-key']
		]);

		$braintree_config->timeout(60);

		$gateway = new Braintree\Gateway($braintree_config);
		return $gateway;
    }

	/**
	 * ACH Payment authorization
	 * @param $feed
	 * @param $submission_data
	 * @param $form
	 * @param $entry
	 *
	 * @return array|bool
	 * @throws \Braintree\Exception\Configuration
	 */
	public function ach_authorize( $feed, $submission_data, $form, $entry ) {
		$this->log_debug( "Braintree_ACH_Authorize::START" );
    	// Prepare authorization response payload
		$authorization = array(
			'is_authorized' => false,
			'error_message' => apply_filters( 'gform_braintree_credit_card_failure_message', __( 'We are unable to authorize the bank account, Please try again.', 'gravity-forms-braintree' ) ),
			'transaction_id' => '',
			'captured_payment' => array(
				'is_success' => false,
				'error_message' => '',
				'transaction_id' => '',
				'amount' => $submission_data['payment_amount']
			)
		);

		$ach_device_corelation = rgpost('ach_device_corelation');
		$ach_token = rgpost('ach_token');
		$payment_amount = number_format($submission_data['payment_amount'],2,'.','');

		$gateway = $this->getBraintreeGateway();
		if( $gateway !== false ) {
			$settings = $this->get_plugin_settings();
			$response = getAngelleyeBraintreePaymentFields($form);
			$braintree_ach_field = $response['braintree_ach'];
			/*$account_number = rgpost( 'input_' . $braintree_ach_field->id . '_1' );
			$account_type = rgpost( 'input_' . $braintree_ach_field->id . '_2' );
			$routing_number = rgpost( 'input_' . $braintree_ach_field->id . '_3' );*/
			$account_holder_name = rgpost( 'input_' . $braintree_ach_field->id . '_4' );

			$account_holder_name = explode(' ', $account_holder_name);
			/**
			 * Create customer in Braintree
			 */
			$customer_request = [
				'firstName' => @$account_holder_name[0],
				'lastName'  => end($account_holder_name),
			];
			$this->log_debug( "Braintree_ACH_Customer::create REQUEST => " . print_r( $customer_request, 1 ) );
			$customer_result = $gateway->customer()->create( $customer_request );
			$this->log_debug( "Braintree_ACH_Customer::create RESPONSE => " . print_r( $customer_result, 1 ) );

			if ( $customer_result->success ) {
				$payment_method_request = [
					'customerId'         => $customer_result->customer->id,
					'paymentMethodNonce' => $ach_token,
					'options'            => [
						'usBankAccountVerificationMethod' => Braintree\Result\UsBankAccountVerification::NETWORK_CHECK
					]
				];

				$this->log_debug( "Braintree_ACH_PaymentRequest::create REQUEST => " . print_r( $payment_method_request, 1 ) );
				$payment_method_response = $gateway->paymentMethod()->create( $payment_method_request );
				$this->log_debug( "Braintree_ACH_PaymentRequest::create RESPONSE => " . print_r( $payment_method_response, 1 ) );

				if(isset($payment_method_response->paymentMethod->token)) {

					$sale_request = [
						'amount'             => $payment_amount,
						'paymentMethodToken' => $payment_method_response->paymentMethod->token,
						'deviceData'         => $ach_device_corelation,
						'options'            => [
							'submitForSettlement' => true
						]
					];

					$this->log_debug( "Braintree_ACH_Transaction::sale REQUEST => " . print_r( $sale_request, 1 ) );
					$sale_response = $gateway->transaction()->sale($sale_request);
					$this->log_debug( "Braintree_ACH_Transaction::sale RESPONSE => " . print_r( $sale_response, 1 ) );

					if ( $sale_response->success ) {
						do_action('angelleye_gravity_forms_response_data', $sale_response, $submission_data, '16', (strtolower($settings['environment']) == 'sandbox') ? true : false , false, 'braintree_ach');
						$authorization['is_authorized'] = true;
						$authorization['error_message'] = '';
						$authorization['transaction_id'] = $sale_response->transaction->id;

						$authorization['captured_payment'] = array(
							'is_success' => true,
							'transaction_id' => $sale_response->transaction->id,
							'amount' => $sale_response->transaction->amount,
							'error_message' => '',
							'payment_method' => 'Braintree ACH'
						);

						$this->log_debug( "Braintree_ACH::SUCCESS");
					} else {
						if( isset( $sale_response->transaction->processorResponseText ) ) {
							$authorization['error_message'] = sprintf( 'Your bank did not authorized the transaction: %s.', $sale_response->transaction->processorResponseText);
						}else {
							$authorization['error_message'] = sprintf( 'Your bank declined the transaction, please try again or contact bank.');
						}
						$this->log_debug( "Braintree_ACH::FAILED_ERROR");
					}
				} else {
					$authorization['error_message'] = __('We are unable to authorize bank account, This may have happened due to expired token, please try again.', 'gravity-forms-braintree');
				}
			} else {
				$authorization['error_message'] = __('Unable to proceed with the transaction due to invalid name.', 'gravity-forms-braintree');
			}

			return $authorization;
		}

		$this->log_debug( "Braintree_ACH::FAILED");
		return false;

	}

	/**
	 * Gets the payment validation result.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::validation()
	 *
	 * @param array $validation_result    Contains the form validation results.
	 * @param array $authorization_result Contains the form authorization results.
	 *
	 * @return array The validation result for the credit card field.
	 */
	public function get_validation_result( $validation_result, $authorization_result ) {

		$credit_card_page = 0;
		if($this->selected_payment_method=='braintree_ach'){
			foreach ( $validation_result['form']['fields'] as &$field ) {
				if ( $field->type == 'braintree_ach' ) {
					$field->failed_validation  = true;
					$field->validation_message = $authorization_result['error_message'];
					$credit_card_page          = $field->pageNumber;
					break;
				}
			}
		}else {
			foreach ( $validation_result['form']['fields'] as &$field ) {
				if ( $field->type == 'creditcard' ) {
					$field->failed_validation  = true;
					$field->validation_message = $authorization_result['error_message'];
					$credit_card_page          = $field->pageNumber;
					break;
				}
			}
		}
		$validation_result['credit_card_page'] = $credit_card_page;
		$validation_result['is_valid']         = false;

		return $validation_result;

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
    public function authorize( $feed, $submission_data, $form, $entry ) {

	    $selected_payment_method = 'braintree_ach';
	    $response = getAngelleyeBraintreePaymentFields($form);
	    if($response['braintree_ach_cc_toggle']!==false){
		    $selected_payment_method = rgpost( 'input_' . $response['braintree_ach_cc_toggle']->id . '_1' );
	    }else {
	    	//This means there was no toggle button, Need to identify based on the fields
		    if($response['creditcard']!==false)
			    $selected_payment_method = 'creditcard';
	    }

	    $this->selected_payment_method = $selected_payment_method;
	    if($selected_payment_method=='braintree_ach'){
	    	return $this->ach_authorize($feed, $submission_data, $form, $entry);
	    }


        // Prepare authorization response payload
        $authorization = array(
            'is_authorized' => false,
            'error_message' => apply_filters( 'gform_braintree_credit_card_failure_message', __( 'Your card could not be billed. Please ensure the details you entered are correct and try again.', 'gravity-forms-braintree' ) ),
            'transaction_id' => '',
            'captured_payment' => array(
                'is_success' => false,
                'error_message' => '',
                'transaction_id' => '',
                'amount' => $submission_data['payment_amount']
            )
        );

        // Perform capture in this function. For this version, we won't authorize and then capture later
        // at least, not in this version
        if( $settings = $this->get_plugin_settings() ) {

            // Sanitize card number, removing dashes and spaces
            $card_number = str_replace( array( '-', ' ' ), '', $submission_data['card_number'] );

            // Prepare Braintree payload
            $args = array(
                'amount' => $submission_data['payment_amount'],
                'creditCard' => array(
                    'number' => $card_number,
                    'expirationDate' => sprintf( '%s/%s', $submission_data['card_expiration_date'][0], $submission_data['card_expiration_date'][1]),
                    'cardholderName' => $submission_data['card_name'],
                    'cvv' => $submission_data['card_security_code']
                )
            );

            $args = apply_filters('angelleye_braintree_parameter', $args, $submission_data, $form, $entry);

            try {

	            $gateway = $this->getBraintreeGateway();

                // Set to auto settlemt if applicable
                if( $settings['settlement'] == 'Yes' ) {
                    $args['options']['submitForSettlement'] = 'true';
                }

                // Send transaction to Braintree
	            $result = $gateway->transaction()->sale($args);

                $this->log_debug( "Braintree_Transaction::sale RESPONSE => " . print_r( $result, 1 ) );
                // Update response to reflect successful payment
                if( $result->success ) {
                    do_action('angelleye_gravity_forms_response_data', $result, $submission_data, '16', (strtolower($settings['environment']) == 'sandbox') ? true : false , false, 'braintree');
                    $authorization['is_authorized'] = true;
                    $authorization['error_message'] = '';
                    $authorization['transaction_id'] = $result->transaction->id;

                    $authorization['captured_payment'] = array(
                        'is_success' => true,
                        'transaction_id' => $result->transaction->id,
                        'amount' => $result->transaction->amount,
                        'error_message' => '',
                        'payment_method' => 'Credit Card'
                    );

                }
                else {

                    // Append gateway response text to error message if it exists. If it doesn't exist, a more hardcore
                    // failure has occured and it won't do the user any good to see it other than a general error message
                    if( isset( $result->transaction->processorResponseText ) ) {
                        $authorization['error_message'] .= sprintf( '. Your bank said: %s.', $result->transaction->processorResponseText);
                    }

                }

            }
            catch( Exception $e ) {
                // Do nothing with exception object, just fallback to generic failure
            }

            return $authorization;

        }

        return false;

    }

    /**
     * Create and display feed settings fields.
     *
     * @since 1.0
     * @return void
     */
    public function feed_settings_fields () {

        // Get defaults from GFPaymentAddOn
        $settings = parent::feed_settings_fields();

        // Remove billing information
        $settings = $this->remove_field( 'billingInformation', $settings );

        // Remove options
        $settings = $this->remove_field( 'options', $settings );

        // Remove the subscription option from transaction type dropdown
        $transaction_type = $this->get_field( 'transactionType', $settings );

        foreach( $transaction_type['choices'] as $index => $choice ) {
            if( $choice['value'] == 'subscription' ) {
                unset( $transaction_type['choices'][$index] );
            }
        }

        $settings = $this->replace_field( 'transactionType', $transaction_type, $settings );

        // Return sanitized settings
        return $settings;

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
                        'class' => 'medium'
                    ),
                    array(
                        'name' => 'public-key',
                        'tooltip' => 'Your Braintree Account Public Key',
                        'label' => 'Public Key',
                        'type' => 'text',
                        'class' => 'medium'
                    ),
                    array(
                        'name' => 'private-key',
                        'tooltip' => 'Your Braintree Account Private Key',
                        'label' => 'Private Key',
                        'type' => 'text',
                        'class' => 'medium'
                    )
                )
            ),
	        array(
		        'title' => 'Braintree ACH Settings',
		        'fields' => array(
			        array(
				        'name' => 'tokenization-key',
				        'tooltip' => 'Your Braintree Tokenization Key',
				        'label' => 'Tokenization Key',
				        'type' => 'text',
				        'class' => 'medium'
			        ),
			        array(
				        'name' => 'business-name',
				        'tooltip' => 'For all ACH transactions, you are required to collect a mandate or “proof of authorization” from the customer to prove that you have their explicit permission to debit their bank account. We will put your business name in authorization text',
				        'label' => 'Business name',
				        'type' => 'text',
				        'class' => 'medium'
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

        if( empty( $settings ) ) {
            return false;
        }

        foreach( $settings as $setting ) {
            if( '' == $setting ) {
                return false;
            }
        }

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

        if( $this->settings_are_valid( $settings ) ) {
            return $settings;
        }
        else {
            return false;
        }

    }

    public function angelleye_gform_braintree_display_push_notification() {
        global $current_user;
        $user_id = $current_user->ID;
        if (false === ( $response = get_transient('angelleye_gravity_braintree_push_notification_result') )) {
            $response = $this->angelleye_get_push_notifications();
            if(is_object($response)) {
                set_transient('angelleye_gravity_braintree_push_notification_result', $response, 12 * HOUR_IN_SECONDS);
            }
        }
        if (is_object($response)) {
            foreach ($response->data as $key => $response_data) {
                if (!get_user_meta($user_id, $response_data->id)) {
                    $this->angelleye_display_push_notification($response_data);
                }
            }
        }
    }

    public function angelleye_get_push_notifications() {
        $args = array(
            'plugin_name' => 'angelleye-gravity-forms-braintree',
        );
        $api_url = PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL . '?Wordpress_Plugin_Notification_Sender';
        $api_url .= '&action=angelleye_get_plugin_notification';
        $request = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('user-agent' => 'AngellEYE'),
            'body' => $args,
            'cookies' => array(),
            'sslverify' => false
        ));
        if (is_wp_error($request) or wp_remote_retrieve_response_code($request) != 200) {
            return false;
        }
        if ($request != '') {
            $response = json_decode(wp_remote_retrieve_body($request));
        } else {
            $response = false;
        }
        return $response;
    }

    public function angelleye_display_push_notification($response_data) {
        echo '<div class="notice notice-success angelleye-notice" style="display:none;" id="'.$response_data->id.'">'
            . '<div class="angelleye-notice-logo-push"><span> <img src="' . $response_data->ans_company_logo . '"> </span></div>'
            . '<div class="angelleye-notice-message">'
            . '<h3>' . $response_data->ans_message_title . '</h3>'
            . '<div class="angelleye-notice-message-inner">'
            . '<p>' . $response_data->ans_message_description . '</p>'
            . '<div class="angelleye-notice-action"><a target="_blank" href="' . $response_data->ans_button_url . '" class="button button-primary">' . $response_data->ans_button_label . '</a></div>'
            . '</div>'
            . '</div>'
            . '<div class="angelleye-notice-cta">'
            . '<button class="angelleye-notice-dismiss angelleye-dismiss-welcome" data-msg="' . $response_data->id . '">Dismiss</button>'
            . '</div>'
            . '</div>';
    }

    public function angelleye_gform_braintree_adismiss_notice() {
        global $current_user;
        $user_id = $current_user->ID;
        if (!empty($_POST['action']) && $_POST['action'] == 'angelleye_gform_braintree_adismiss_notice') {
            add_user_meta($user_id, wc_clean($_POST['data']), 'true', true);
            wp_send_json_success();
        }
    }

	public function include_angelleye_braintree_style_noconflict( $styles ) {
		$styles[] = 'gravity-forms-braintree-admin-css';
		return $styles;
    }

	public function include_angelleye_braintree_script_noconflict( $scripts ) {
    	$scripts[] = 'gravity-forms-braintree-admin';
		return $scripts;
    }

    public function enqueue_scripts_js() {
    	if(GFForms::is_gravity_page()) {
		    wp_enqueue_script( 'gravity-forms-braintree-admin', GRAVITY_FORMS_BRAINTREE_ASSET_URL . 'assets/js/gravity-forms-braintree-admin.js', array( 'jquery' ), $this->_version, false );
	    }
    }

    public function enqueue_styles_css() {
	    if(GFForms::is_gravity_page()) {
		    wp_enqueue_style( 'gravity-forms-braintree-admin-css', GRAVITY_FORMS_BRAINTREE_ASSET_URL . 'assets/css/gravity-forms-braintree-admin.css', array(), $this->_version, 'all' );
	    }
    }

	/**
	 * Load the Braintree JS and Custom JS in frontend
	 * @return array
	 */
	public function scripts() {
		$translation_array = [];
		$settings  = $this->get_plugin_settings();
		if($settings!==false){
			$translation_array['ach_bt_token'] = @$settings['tokenization-key'];
			$translation_array['ach_business_name'] = @$settings['business-name'];
		}

		$scripts = array(
			array(
				'handle'    => 'angelleye-gravity-form-braintree-client',
				'src'       => 'https://js.braintreegateway.com/web/3.61.0/js/client.min.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery' ),
				'in_footer' => false,
				'callback'  => array( $this, 'localize_scripts' ),
				'enqueue'   => array(
					array( 'field_types' => array( 'braintree_ach' ) )
				)
			),
			array(
				'handle'    => 'angelleye-gravity-form-braintree-data-collector',
				'src'       => 'https://js.braintreegateway.com/web/3.61.0/js/data-collector.min.js',
				'version'   => $this->_version,
				'deps'      => array( ),
				'in_footer' => false,
				'callback'  => array( $this, 'localize_scripts' ),
				'enqueue'   => array(
					array( 'field_types' => array( 'braintree_ach' ) )
				)
			),
			array(
				'handle'    => 'angelleye-gravity-form-braintree-usbankaccount',
				'src'       => 'https://js.braintreegateway.com/web/3.61.0/js/us-bank-account.min.js',
				'version'   => $this->_version,
				'deps'      => array( ),
				'in_footer' => false,
				'callback'  => array( $this, 'localize_scripts' ),
				'enqueue'   => array(
					array( 'field_types' => array( 'braintree_ach' ) )
				)
			),
			array(
				'handle'    => 'angelleye_gravity_form_braintree_ach_handler',
				'src'       => GRAVITY_FORMS_BRAINTREE_ASSET_URL . 'assets/js/angelleye-braintree-ach-cc.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery', 'angelleye-gravity-form-braintree-client', 'angelleye-gravity-form-braintree-data-collector',
					'angelleye-gravity-form-braintree-usbankaccount'),
				'in_footer' => false,
				'callback'  => array( $this, 'localize_scripts' ),
				'strings'   => $translation_array,
				'enqueue'   => array(
//					array(
//						'admin_page' => array( 'form_settings' ),
//						'tab'        => 'simpleaddon'
//					)
					array( 'field_types' => array( 'braintree_ach' ) )
				)
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}
}