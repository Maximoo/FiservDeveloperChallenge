<?php
/**
 * Fiserv FirstData Gateway.
 *
 * @since   1.0.0
 * @package MFC_FirstDataGateway
 */

/**
 * Mercado Floral E-Commerce Fiserv FirstData Gateway class.
 *
 * @since 1.0.0
 */
class MFC_Gateway {
	
	/**
	 * Parent plugin class.
	 *
	 * @var    MFC_FirstDataGateway
	 * @since  1.0.0
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $key = 'mfc_firstdata_settings';

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'mfc_firstdata_settings_metabox';

	/**
	 * Options Page title.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $title = '';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param  MFC_FirstDataGateway $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Set our title.
		$this->title = esc_attr__( 'MFCommerce - Fiserv FirstData', 'mfc-firstdatagateway' );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {
		// Hook in our actions to the admin.	
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );

		add_filter( 'mfc_get_card_information_fiserv', array( $this, 'get_card_information' ), 10, 2 );
		add_filter( 'mfc_payment_do_fiserv', array( $this, 'payment_do' ), 10, 3 );
	}

	/**
	 * Add custom fields to the options page.
	 *
	 * @since  1.0.0
	 */
	public function add_options_page_metabox() {

		// Add our CMB2 metabox.
		$cmb = new_cmb2_box( array(
			'id'           => self::$metabox_id,
			'title'        => $this->title,
			'object_types' => array( 'options-page' ),

			/*
			 * The following parameters are specific to the options-page box
			 * Several of these parameters are passed along to add_menu_page()/add_submenu_page().
			 */

			'option_key'   		 => self::$key, // The option key and admin menu page slug.
			// 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
			'menu_title'      	 => esc_html__( 'Fiserv FirstData', 'mfc-firstdatagateway' ), // Falls back to 'title' (above).
			'parent_slug'    	 => 'mfcommerce_settings', // Make options page a submenu item of the themes menu.
			// 'capability'      => 'manage_options', // Cap required to view options-page.
			// 'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			// 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'cmb2' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

		$cmb->add_field( array(
			'name'    => __( 'Consumer Key', 'mfc-firstdatagateway' ),
			'id'      => 'consumer_key',
			'type'    => 'text'
		) );

		$cmb->add_field( array(
			'name'    => __( 'Consumer Secret', 'mfc-firstdatagateway' ),
			'id'      => 'consumer_secret',
			'type'    => 'text'
		) );

		$cmb->add_field( array(
			'name'    => __( 'Developer ID', 'mfc-firstdatagateway' ),
			'id'      => 'developer_id',
			'type'    => 'text'
		) );

		$cmb->add_field( array(
			'name'    => __( 'API URL', 'mfc-firstdatagateway' ),
			'id'      => 'api_url',
			'type'    => 'text'
		) );

	}

	/*----------------------------------------------------------------------------------------------------
		FISERV Methods
	----------------------------------------------------------------------------------------------------*/
	public function get_card_information( $info, $card_number ){
		$response = $this->call_api('card-information',[
			"paymentCard" => [
				"number" => $card_number
			]
		], 'POST');
		return  !empty($response['cardDetails']) ? $response['cardDetails'][0] : $info;
	}

	public function payment_do( $status, $caluclated, $payment){
		$transaction = $this->direct_payment(
			$caluclated['total'],
			$payment->data_card_number,
			$payment->data_card_securityCode,
			$payment->data_card_month,
			$payment->data_card_year
		);
		if(!empty($transaction['transactionStatus'])){
			if($transaction['transactionStatus'] == 'APPROVED'){
				return array(
					'status' => 'approved',
					'code' => '',
					'message' => '',
					'transaction_id' => $transaction['ipgTransactionId'],
					'order_id' => $transaction['orderId'],
				);
			} elseif( isset($transaction['processor']) ) {
				return array(
					'status' => 'rejected',
					'code' => $transaction['processor']['responseCode'],
					'message' => __( 'Payment Declined', 'mfc-firstdatagateway' )
				);
			}
		}
		if(!empty($transaction['error'])){
			return array(
				'status' => 'rejected',
				'code' => $transaction['error']['code'],
				'message' => $transaction['error']['message']
			);
		}
		return $status;
	}

	private function direct_payment( $total, $card_number, $card_securityCode, $card_month, $card_year ){
		return $this->call_api('payments',[
			"requestType" => "PaymentCardSaleTransaction",
			"transactionAmount" => [
				"total" => $total,
				"currency" => "MXN"
			],
			"paymentMethod" => [
				"paymentCard" => [
					"number" => $card_number,
					"securityCode" => $card_securityCode,
					"expiryDate" => [
						"month" => $card_month,
						"year" => $card_year
					]
				]
			]
		], 'POST');
	}

	/*----------------------------------------------------------------------------------------------------
		Utils
	----------------------------------------------------------------------------------------------------*/
	private function call_api( $endpoint, $parameters = array(), $method = 'GET', $is_file = false ){
		$api_key = $this->get_value('consumer_key');
		$api_secret = $this->get_value('consumer_secret');
		$api_url = $this->get_value('api_url');
	    $uuid = $this->create_uuid();
	    $timestamp = round(microtime(true) * 1000);
	    $msg_signature = $this->createMessageSignature($api_key, $api_secret, $uuid, $timestamp, json_encode($parameters));
	  
		return $this->plugin->call_api(
			$api_url . $endpoint,
			$parameters,
			array(
				'Api-Key' => $api_key,
		        'Client-Request-Id' => $uuid,
		        'Timestamp' => $timestamp,
		        'Message-Signature' => $msg_signature
			),
			$method,
			$is_file
		);
	}

	private function createMessageSignature($api_key, $api_secret, $client_request_id, $timestamp, $data){
        return base64_encode(hash_hmac('sha256', $api_key . $client_request_id . $timestamp . $data, $api_secret));
    }

	private function create_uuid(){
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

	/**
	 * Wrapper function around cmb2_get_option.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $key     Options array key
	 * @param  mixed  $default Optional default value
	 * @return mixed           Option value
	 */
	public static function get_value( $key = '', $default = false ) {
		if ( function_exists( 'cmb2_get_option' ) ) {

			// Use cmb2_get_option as it passes through some key filters.
			return cmb2_get_option( self::$key, $key, $default );
		}

		// Fallback to get_option if CMB2 is not loaded yet.
		$opts = get_option( self::$key, $default );

		$val = $default;

		if ( 'all' == $key ) {
			$val = $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
			$val = $opts[ $key ];
		}

		return $val;
	}
}
