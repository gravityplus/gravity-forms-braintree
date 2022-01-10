<?php

// Plugify_GForm_Braintree class

final class Plugify_GForm_Braintree extends GFPaymentAddOn {

    protected $_version = '4.0';

    protected $_min_gravityforms_version = '1.8.7.16';
    protected $_slug = 'gravity-forms-braintree';
    protected $_path = 'gravity-forms-braintree/lib/class.plugify-gform-braintree.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Braintree';
    protected $_short_title = 'Braintree';
    protected $_requires_credit_card = true;
    protected $_supports_callbacks = true;
    protected $_enable_rg_autoupgrade = true;
    protected $is_payment_gateway = true;
    protected $current_feed = true;

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
				if ($single_field->type == 'creditcard' || $single_field->type=='braintree_ach' || $single_field->type=='braintree_credit_card') {
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
			try {
				$settings            = $this->get_plugin_settings();
				$response            = getAngelleyeBraintreePaymentFields( $form );
				$braintree_ach_field = $response['braintree_ach'];
				/*$account_number = rgpost( 'input_' . $braintree_ach_field->id . '_1' );
				$account_type = rgpost( 'input_' . $braintree_ach_field->id . '_2' );
				$routing_number = rgpost( 'input_' . $braintree_ach_field->id . '_3' );*/
				$account_holder_name = rgpost( 'input_' . $braintree_ach_field->id . '_4' );

				$account_holder_name = explode( ' ', $account_holder_name );
				/**
				 * Create customer in Braintree
				 */
				$customer_request = [
					'firstName' => @$account_holder_name[0],
					'lastName'  => end( $account_holder_name ),
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

					if ( isset( $payment_method_response->paymentMethod->token ) ) {

						$sale_request = [
							'amount'             => $payment_amount,
							'paymentMethodToken' => $payment_method_response->paymentMethod->token,
							'deviceData'         => $ach_device_corelation,
							'options'            => [
								'submitForSettlement' => true
							]
						];

						$sale_request = apply_filters('angelleye_braintree_parameter', $sale_request, $submission_data, $form, $entry);

						$this->log_debug( "Braintree_ACH_Transaction::sale REQUEST => " . print_r( $sale_request, 1 ) );
						$sale_response = $gateway->transaction()->sale( $sale_request );
						$this->log_debug( "Braintree_ACH_Transaction::sale RESPONSE => " . print_r( $sale_response, 1 ) );

						if ( $sale_response->success ) {
							do_action( 'angelleye_gravity_forms_response_data', $sale_response, $submission_data, '16', ( strtolower( $settings['environment'] ) == 'sandbox' ) ? true : false, false, 'braintree_ach' );
							$authorization['is_authorized']  = true;
							$authorization['error_message']  = '';
							$authorization['transaction_id'] = $sale_response->transaction->id;

							$authorization['captured_payment'] = array(
								'is_success'     => true,
								'transaction_id' => $sale_response->transaction->id,
								'amount'         => $sale_response->transaction->amount,
								'error_message'  => '',
								'payment_method' => 'Braintree ACH'
							);

							$this->log_debug( "Braintree_ACH::SUCCESS" );
						} else {
							if ( isset( $sale_response->transaction->processorResponseText ) ) {
								$authorization['error_message'] = sprintf( 'Your bank did not authorized the transaction: %s.', $sale_response->transaction->processorResponseText );
							} else {
								$authorization['error_message'] = sprintf( 'Your bank declined the transaction, please try again or contact bank.' );
							}
							$this->log_debug( "Braintree_ACH::FAILED_ERROR" );
						}
					} else {
						$authorization['error_message'] = __( 'We are unable to authorize bank account, This may have happened due to expired token, please try again.', 'gravity-forms-braintree' );
					}
				} else {
					$authorization['error_message'] = __( 'Unable to proceed with the transaction due to invalid name.', 'gravity-forms-braintree' );
				}
			}catch (Exception $exception){
				$this->log_debug( "Braintree_ACH::EXCEPTION: ".$exception->getTraceAsString() );
				$exception['error_message'] = __('An internal error occurred, Please try later. ERROR: '.$exception->getMessage());
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
	 * Braintree credit card Payment authorization
	 * @param $feed
	 * @param $submission_data
	 * @param $form
	 * @param $entry
	 *
	 * @return array|bool
	 * @throws \Braintree\Exception\Configuration
	 */
	public function braintree_cc_authorize( $feed, $submission_data, $form, $entry ) {

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

		if(empty($_POST['payment_method_nonce'])) {
			return $authorization;
		}

		try {

			$settings = $this->get_plugin_settings();
			$gateway = $this->getBraintreeGateway();

			$args = array(
				'amount' => $submission_data['payment_amount'],
				'paymentMethodNonce' => $_POST['payment_method_nonce']
			);

			$args = apply_filters('angelleye_braintree_parameter', $args, $submission_data, $form, $entry);

			if( $settings['settlement'] == 'Yes' ) {
				$args['options']['submitForSettlement'] = 'true';
			}

			$result = $gateway->transaction()->sale($args);

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

			} else {
				if( isset( $result->transaction->processorResponseText ) ) {
					$authorization['error_message'] .= sprintf( '. Your bank said: %s.', $result->transaction->processorResponseText);
				}
			}

		} catch( Exception $e ) {
			// Do nothing with exception object, just fallback to generic failure
		}

		return $authorization;
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

	    $this->selected_payment_method = getAngelleyeBraintreePaymentMethod($form);

	    if($this->selected_payment_method=='braintree_ach'){
	    	return $this->ach_authorize($feed, $submission_data, $form, $entry);
	    }

	    if( $this->selected_payment_method == 'braintree_credit_card' ) {
	    	return $this->braintree_cc_authorize($feed, $submission_data, $form, $entry);
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

	public function process_capture( $authorization, $feed, $submission_data, $form, $entry ) {

		do_action( 'gform_braintree_post_capture', rgar( $authorization, 'is_authorized' ), rgars( $authorization, 'captured_payment/amount' ), $entry, $form, $this->_args_for_deprecated_hooks['config'], $this->_args_for_deprecated_hooks['aim_response'] );

		return parent::process_capture( $authorization, $feed, $submission_data, $form, $entry );
	}

	/**
	 * Braintree Override this method to add integration code to the payment processor in order to create a subscription.
	 *
	 * This method is executed during the form validation process and allows the form submission process to fail with a
	 * validation error if there is anything wrong when creating the subscription.
	 *
	 * @param array $feed               Current configured payment feed.
	 * @param array $submission_data    Contains form field data submitted by the user as well as payment information
	 *                                  (i.e. payment amount, setup fee, line items, etc...).
	 * @param array $form               Current form array containing all form settings.
	 * @param array $entry              Current entry array containing entry information (i.e data submitted by users).
	 *                                  NOTE: the entry hasn't been saved to the database at this point, so this $entry
	 *                                  object does not have the 'ID' property and is only a memory representation of the entry.
	 *
	 * @return array {
	 *      Return an $subscription array
	 *     @type bool   $is_success      If the subscription is successful.
	 *     @type string $error_message   The error message, if applicable.
	 *     @type string $subscription_id The subscription ID.
	 *     @type int    $amount          The subscription amount.
	 *     @type array  $captured_payment {
	 *         If payment is captured, an additional array is created.
	 *         @type bool   $is_success     If the payment capture is successful.
	 *         @type string $error_message  The error message, if any.
	 *         @type string $transaction_id The transaction ID of the captured payment.
	 *         @type int    $amount         The amount of the captured payment, if successful.
	 *    }
	 *
	 * To implement an initial/setup fee for gateways that don't support setup fees as part of subscriptions, manually
	 * capture the funds for the setup fee as a separate transaction and send that payment information in the
	 * following 'captured_payment' array:
	 *
	 *      'captured_payment' => [
	 *          'name'           => 'Setup Fee',
	 *          'is_success'     => true|false,
	 *          'error_message'  => 'error message',
	 *          'transaction_id' => 'xxx',
	 *          'amount'         => XX
	 *      ]
	 * }
	 * @throws \Braintree\Exception\Configuration
	 */
    public function subscribe( $feed, $submission_data, $form, $entry ) {

	    $authorization = array(
		    'is_authorized' => false,
		    'is_success' => false,
		    'error_message' => apply_filters( 'gform_braintree_credit_card_failure_message', __( 'Your card could not be billed. Please ensure the details you entered are correct and try again.', 'angelleye-gravity-forms-braintree' ) ),
	    );

	    if(empty($_POST['payment_method_nonce'])) {
	    	return $authorization;
	    }

	    $settings = $this->get_plugin_settings();
	    $gateway = $this->getBraintreeGateway();

	    $args = array(
		    'amount' => $submission_data['payment_amount'],
		    'creditCard' => array(
			    'number' => !empty($submission_data['card_number']) ? str_replace( array( '-', ' ' ), '', $submission_data['card_number'] ) : '',
			    'expirationDate' => sprintf( '%s/%s', $submission_data['card_expiration_date'][0], $submission_data['card_expiration_date'][1]),
			    'cardholderName' => $submission_data['card_name'],
			    'cvv' => $submission_data['card_security_code']
		    )
	    );

	    $args = apply_filters('angelleye_braintree_parameter', $args, $submission_data, $form, $entry);

	    $customerArgs = !empty($args['customer']) ? $args['customer'] : array();

	    $customer_id = $this->get_customer_id($customerArgs);

	    $paymentMethod = $gateway->paymentMethod()->create([
		    'customerId' => $customer_id,
		    'paymentMethodNonce' => $_POST['payment_method_nonce']
	    ]);

	    $fee_amount = !empty($submission_data['setup_fee']) ? $submission_data['setup_fee'] : 0;
	    $setup_fee_result       = true;

	    if ( ! empty( $fee_amount ) && $fee_amount > 0 ) {

		    $feeArgs = array(
			    'amount' => $fee_amount,
			    'paymentMethodToken' => $paymentMethod->paymentMethod->token,
		    );

		    if( $settings['settlement'] == 'Yes' ) {
			    $feeArgs['options']['submitForSettlement'] = 'true';
		    }

		    $feeArgs = apply_filters('angelleye_braintree_parameter', $feeArgs, $submission_data, $form, $entry);

		    $feeResult = $gateway->transaction()->sale($feeArgs);

		    if( $feeResult->success ) {
			    $authorization['captured_payment'] = array(
				    'is_success' => true,
				    'transaction_id' => $feeResult->transaction->id,
				    'amount' => $feeResult->transaction->amount,
				    'error_message' => '',
				    'payment_method' => 'Credit Card'
			    );
		    } else {
			    $setup_fee_result = false;
			    if( isset( $result->transaction->processorResponseText ) ) {
				    $authorization['error_message'] .= sprintf( '. Your bank said: %s.', $result->transaction->processorResponseText);
			    }
		    }
	    }

	    if( $setup_fee_result ) {

		    try {
			    $subscriptionArgs = array(
				    'paymentMethodToken' => $paymentMethod->paymentMethod->token,
				    'planId' => !empty($feed['meta']['subscriptionPlan']) ? $feed['meta']['subscriptionPlan'] : '',
				    'price' => $submission_data['payment_amount'],
			    );

			    if ( $feed['meta']['recurringTimes'] == 0 ) {
				    $subscriptionArgs['neverExpires'] = true;
			    } else {
				    $subscriptionArgs['numberOfBillingCycles'] = $feed['meta']['recurringTimes'];
			    }

			    if ( !empty($feed['meta']['trial_enabled']) ) {
				    $subscriptionArgs['trialDuration']     = '';
				    $subscriptionArgs['trialDurationUnit'] = '';
				    $subscriptionArgs['trialPeriod']       = true;
			    } else {
				    $subscriptionArgs['firstBillingDate'] = '';
			    }

			    $subscriptionArgs = apply_filters( 'angelleye_gravity_braintree_subscription_args', $subscriptionArgs );
			    $subscription = $gateway->subscription()->create($subscriptionArgs);

			    if($subscription->success) {

				    $authorization['is_authorized'] = true;
				    $authorization['is_success'] = true;
				    $authorization['error_message'] = '';
				    $authorization['paymentMethodToken'] = $subscription->subscription->paymentMethodToken;
				    $authorization['subscription_id'] = $subscription->subscription->id;
				    $authorization['amount'] = $subscription->subscription->price;
				    $authorization['subscription_trial_amount'] = $subscription->subscription->price;
				    $authorization['subscription_start_date'] = $subscription->subscription->firstBillingDate->date;
			    }

		    } catch ( Exception $e ) {

		    }
	    }

	    return $authorization;
	}

	/**
	 * Braintree override this method to add integration code to the payment processor in order to cancel a subscription.
	 *
	 * This method is executed when a subscription is canceled from the braintree Payment Gateway.
	 *
	 * @param array $entry  Current entry array containing entry information (i.e data submitted by users).
	 * @param array $feed   Current configured payment feed.
	 *
	 * @return bool Returns true if the subscription was cancelled successfully and false otherwise.
	 *
	 * @throws \Braintree\Exception\Configuration
	 */
	public function cancel( $entry, $feed ) {

		$gateway = $this->getBraintreeGateway();

		$result = $gateway->subscription()->cancel($entry['transaction_id']);

		if($result->success) {
			return true;
		}

    	return false;
	}

	public function is_payment_gateway( $entry_id ) {

		if ( $this->is_payment_gateway ) {
			return true;
		}

		$gateway = gform_get_meta( $entry_id, 'payment_gateway' );

		return in_array( $gateway, array( 'Braintree', $this->_slug ) );
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

        //foreach( $transaction_type['choices'] as $index => $choice ) {
            //if( $choice['value'] == 'subscription' ) {
                //unset( $transaction_type['choices'][$index] );
            //}
        //}
		
		$transactionType = '';
		foreach( $settings as $index => $setting ) {
			if( !empty($setting['dependency']['field']) && $setting['dependency']['field'] == 'transactionType' ) {
				$transactionType = !empty($setting['dependency']['values'][0]) ? $setting['dependency']['values'][0] :'';
			}
		}

		if( (!empty($_POST['_gaddon_setting_transactionType']) && $_POST['_gaddon_setting_transactionType'] == 'subscription') || ( empty($_POST['_gaddon_setting_transactionType']) && !empty($transactionType) && $transactionType == 'subscription') ) {
			$form_page_link = add_query_arg([
				'id' => !empty($_REQUEST['id']) ? $_REQUEST['id'] : '',
			], menu_page_url('gf_edit_forms', false));
			$transaction_type['description'] = sprintf(__('When building your subscription form, make sure to use the %sBraintree CC%s field instead of the basic Credit Card field.','angelleye-gravity-forms-braintree'), '<a href="'.$form_page_link.'">','</a>');
		}

        $settings = $this->replace_field( 'transactionType', $transaction_type, $settings );

	    $settings = parent::remove_field( 'trial', $settings );

	    $createBraintreePlanUrl = $this->merchant_url('plans/new');
	    $api_settings_field = array(
		    array(
			    'name'    => 'braintree_trial',
			    'label'   => esc_html__( 'Trial', 'angelleye-gravity-forms-braintree' ),
			    'type'    => 'braintree_trial',
			    'hidden'  => '',
			    'tooltip' => ''
		    ),
		    array(
			    'name'     => 'subscriptionPlan',
			    'label'    => esc_html__( 'Plan', 'angelleye-gravity-forms-braintree' ),
			    'type'     => 'select',
			    'choices'  => $this->get_plans(),
			    'required' => true,
			    'tooltip'  => sprintf(__('Plugin will fetch and display the subscription plans. Create the %splan%s in your Braintree account.','angelleye-gravity-forms-braintree'),'<a href="'.$createBraintreePlanUrl.'" target="_blank">','</a>'),
		    ),
	    );

	    $settings = $this->add_field_after( 'setupFee', $api_settings_field, $settings );

        // Return sanitized settings
        return $settings;

    }

    public function merchant_url( $tab = 'plans') {

	    $settings = $this->get_plugin_settings();

	    $braintreeUrl = '#';
	    if( !empty($settings['merchant-id']) ) {
		    $environment    = ! empty( $settings['environment'] ) ? strtolower( $settings['environment'] ) : 'sandbox';
		    $environmentUrl = ( $environment == 'sandbox' ) ? 'sandbox.' : '';

		    $braintree_config = new \Braintree\Configuration( [
			    'environment' => strtolower( $settings['environment'] ),
			    'merchantId'  => $settings['merchant-id'],
			    'publicKey'   => $settings['public-key'],
			    'privateKey'  => $settings['private-key']
		    ] );

		    $merchantPath = $braintree_config->merchantPath();
		    $braintreeUrl = "https://{$environmentUrl}braintreegateway.com{$merchantPath}/{$tab}";
	    }

	    return $braintreeUrl;
    }

	/**
	 * This function is callback for braintree_trial setting field.
	 *
	 * @param array $field Settings fields
	 * @param bool $echo Display or return
	 *
	 * @return string $html
	 *
	 * @throws \Braintree\Exception\Configuration
	 */
    public function settings_braintree_trial( $field, $echo = true ) {

	    $braintreePlans = $this->merchant_url();
    	$html = sprintf(__( 'Select your product trial form %sBraintree Plans%s', 'angelleye-gravity-forms-braintree' ),'<a href="'.$braintreePlans.'" target="_blank">', '</a>');

    	if ( $echo ) {
		    echo $html;
	    }

	    return $html;
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
                        'class' => 'medium',
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
        } else {
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

	/**
	 * Override default billing cycles intervals for subscription plan.
	 *
	 * @return array $billing_cycles
	 */
	public function supported_billing_intervals() {
		$billing_cycles = array(
			'month' => array( 'label' => esc_html__( 'month(s)', 'angelleye-gravity-forms-braintree' ), 'min' => 1, 'max' => 24 )
		);

		return $billing_cycles;
	}

	/**
	 * Get all Braintree plans using Braintree payment Gateway settings.
	 *
	 * @return array $plans
	 *
	 * @throws \Braintree\Exception\Configuration
	 */
	public function get_plans() {

		$gateway = $this->getBraintreeGateway();
		$plan_lists = $gateway->plan()->all();

		$plans = array( array(
			'label' => __('Select a plan','angelleye-gravity-forms-braintree'),
			'value' => '',
		));

		if( !empty($plan_lists)) {
			foreach ($plan_lists as $plan ) {
				$plans[] = array(
					'label' => $plan->name,
					'value' => $plan->id,
				);
			}
		}

		return $plans;
	}

	/**
	 * Get customer id using customer email address.
	 *
	 * If customer email address already exists in braintree customer lists then provide customer id,
	 * Otherwise create a new customer using customer details and then provide a customer id.
	 *
	 * @param array $args
	 *
	 * @return int|string $customer_id Customer id.
	 *
	 * @throws \Braintree\Exception\Configuration
	 */
	public function get_customer_id( $args ) {

		//check if customer detail is empty or not array then return.
		if(empty($args) || !is_array($args)) {
			return '';
		}

		$email = !empty($args['email']) ? $args['email'] : '';

		$gateway = $this->getBraintreeGateway();

		//search customer using email address
		$collections = $gateway->customer()->search([
			Braintree\CustomerSearch::email()->is($email)
		]);

		$customer_id = 0;
		foreach ($collections as $key => $collection) {
			if( !empty($collection->id) ) {
				$customer_id = $collection->id;
			}
		}

		//check $customer_id is empty then create a new customer.
		if(empty($customer_id)) {
			$customer    = $gateway->customer()->create( $args );
			if(!empty($customer->customer->id)) {
				$customer_id = $customer->customer->id;
			}
		}

		return  $customer_id;
	}
}