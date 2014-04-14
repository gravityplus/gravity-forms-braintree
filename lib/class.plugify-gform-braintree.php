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

	public function __construct () {

		// Build parent
		parent::__construct();

		// Register actions
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'wp_ajax_map_feed_fields', array( &$this, 'ajax_plugin_page' ) );
		add_action( 'wp_ajax_delete_feed', array( &$this, 'ajax_delete_feed' ) );

		// Register filters
		add_filter( 'gform_enable_credit_card_field', array( &$this, 'enable_credit_card' ), 10, 1 );

	}

	public function admin_init () {

		// Update query vars and redirect if appropriate
		if( isset( $_GET['fid'] ) && !isset( $_GET['id'] ) ) {

			$feed = $this->get_feed( $_GET['fid'] );
			wp_redirect( add_query_arg( array( 'id' => $feed['form_id'] ? $feed['form_id'] : '0' ) ) );
			exit;

		}

		// Ensure necessary scripts are enqueued
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core', '', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-effects-core', '', array( 'jquery-ui-core', 'jquery' ) );

	}

	public function plugin_page_title () {

		if( $this->is_feed_list_page() )
			return $this->_title . " <a class='add-new-h2' href='" . add_query_arg( array( 'fid' => 0 ) ) . "'>" . __( "Add New", "gravity-forms-braintree") . "</a>";
		else
			return parent::plugin_page_title();

	}

	public function feed_list_title () {
		return null;
	}

	public function plugin_page () {

		if( isset( $_GET['fid'] ) ) {

			$feed = $this->get_feed( $_GET['fid'] );
			$form = GFAPI::get_form( $feed['form_id'] );

			if( $_REQUEST['fid'] == 0 && $_REQUEST['id'] == 0 )
			echo '<style type="text/css">#gform-settings-save, #gaddon-setting-row-gf_braintree_mapped_fields { display: none; }</style>';

			$this->feed_edit_page( $form, $feed['id'] );

		}
		else {

			?>
			<style type="text/css">
			table.feeds th#is_active { width: 50px; }
			table.feeds th#id { width: 100px; }
			</style>
			<?php

			$this->feed_list_page();

		}

	}

	public function ajax_plugin_page () {

		ob_start();

		// For anyone reading this, the below is not ideal. Could not figure out how to do this natively with GFFeedAddOn due
		// to lack of documentation in Gravity Forms. We'll be updating this to something less hacky in the future!
		$url = admin_url( 'admin.php?page=' . sprintf( '%s&id=%s&fid=%s', $this->_slug, $_REQUEST['id'], $_REQUEST['fid'] ) );
		$response = wp_remote_post( $url, array( 'cookies' => $_COOKIE ) );

		if( $response['response']['code'] == '200' )
		echo $response['body'];

		$html = ob_get_contents();
		ob_end_clean();

		wp_send_json_success( array(
			'html' => $html
		) );

	}

	public function ajax_delete_feed () {

		global $wpdb;

		if( !isset( $_REQUEST['feed_id'] ) || !is_numeric( $_REQUEST['feed_id'] ) )
			wp_send_json_error();

		$count = $wpdb->delete( "{$wpdb->prefix}gf_addon_feed", array( 'id' => $_REQUEST['feed_id'] ) );

		if( $count > 0 )
			wp_send_json_success();
		else
			wp_send_json_error();

	}

	public function insert_feed ( $form_id, $is_active, $meta ) {

		global $wpdb;

		if( !$form_id )
		$form_id = $_POST['_gaddon_setting_form_id'];

		if( $feed_id = parent::insert_feed( $form_id, $is_active, $meta ) ) {

			$wpdb->update( "{$wpdb->prefix}gf_addon_feed", array( 'form_id' => $form_id ), array( 'id' => $feed_id ) );
			return true;

		}

		return false;

	}

	public function save_feed_settings ( $feed_id, $form_id, $settings ) {

		global $wpdb;

		$result = false;

		if( $result = parent::save_feed_settings( $feed_id, $form_id, $settings ) ) {
			$wpdb->update( "{$wpdb->prefix}gf_addon_feed", array( 'form_id' => $settings['form_id'] ), array( 'id' => $feed_id ) );
			$result = true;
		}

		return $result;

	}

	public function get_column_value_form( $item ) {

		$form = GFAPI::get_form( $item['form_id'] );
		return __( $form['title'], 'gravity-forms-braintree' );

	}

	public function get_column_value_txntype( $item ) {
		return __( 'Single payment', 'gravity-forms-braintree' );
	}

	public function scripts() {

    $scripts = array(
      array(
        'handle'  => 'gf_braintree_scripts',
        'src'     => plugins_url( 'assets/js/scripts.js', trailingslashit( dirname( __FILE__ ) ) ),
        'version' => $this->_version,
        'deps'    => array( 'jquery' ),
        'strings' => array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'ajax_loader_url' => plugins_url( 'assets/images/ajax-loader.gif', trailingslashit( dirname( __FILE__ ) ) ),
					'feed_id' => $_GET['fid']
				),
				"enqueue" => array(
	        array(
	        	'query' => 'page=gravity-forms-braintree'
	        )
        )
      ),
    );

	  $scripts = array_merge( parent::scripts(), $scripts );

		return $scripts;

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
            'label' => 'Gravity Form',
            'type' => 'select',
            'name' => 'form_id',
            'class' => 'small',
						'choices' => $form_choices
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

	protected function feed_list_columns () {

		return array(
			'id' => __( 'Feed ID', 'gravity-forms-braintree' ),
			'form' => __( 'Form', 'gravity-forms-braintree' ),
			'txntype' => __( 'Transaction Type', 'gravity-forms-braintree' )
		);

	}

	public function feed_list_no_item_message () {

		$settings = $this->get_plugin_settings();

		if( empty( $settings ) )
			return sprintf(__("<p style=\"padding: 10px 5px 5px;\">You have not yet configured your Braintree settings. Let's go %sdo that now%s!</p>", "gravityforms"), "<a href='" . admin_url( 'admin.php?page=gf_settings&subview=Braintree' ) . "'>", "</a>");
		else
			return sprintf(__("<p style=\"padding: 10px 5px 5px;\">You don't have any Braintree feeds configured. Let's go %screate one%s!</p>", "gravityforms"), "<a href='" . add_query_arg( array( 'fid' => 0, 'id' => 0 ) ) . "'>", "</a>");
	}

	public function get_action_links () {

		return array(
			'edit' => '<a title="' . __( 'Edit this feed', 'gravity-forms-braintree' ) . '" href="' . add_query_arg( array( 'fid' => "{id}" ) ) . '">' . __( 'Edit', 'gravity-forms-braintree' ) . '</a>',
			'delete' => '<a title="' . __( 'Delete this feed', 'gravity-forms-braintree' ) . '" class="submitdelete" href="javascript:void();" data-feed-id="' . "{id}" . '">' . __( 'Delete', 'gravity-forms-braintree' ) . '</a>',
		);

	}

	public function process_feed( $feed, $entry, $form ) {

		// Proceed only if settings exist
		if( $settings = $this->get_plugin_settings() ) {

			// Build Braintree HTTP request parameters
			$args = array(

				'amount' => trim( $entry[ $feed['meta']['gf_braintree_mapped_fields_amount'] ], "$ \t\n\r\0\x0B" ),
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
