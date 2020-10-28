<?php
/**
 * Mercado Floral E-Commerce Settings.
 *
 * @since   1.0.0
 * @package PM_Casa_E_Commerce
 */



/**
 * Mercado Floral E-Commerce Settings class.
 *
 * @since 1.0.0
 */
class MFC_Settings_C {
	/**
	 * Parent plugin class.
	 *
	 * @var    PM_Casa_E_Commerce
	 * @since  1.0.0
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $key = 'mfcommerce_settings';

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'mfcommerce_settings_metabox';

	/**
	 * Options Page title.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $title = '';

	/**
	 * Options Page hook.
	 *
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param  PM_Casa_E_Commerce $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Set our title.
		$this->title = esc_attr__( 'MFCommerce - Settings', 'mfcommerce' );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {

		// Hook in our actions to the admin.
		
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'rest_api_init', array($this, 'rest_api_init') );
		
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
			'menu_title'      	 => esc_html__( 'MFCommerce', 'mfcommerce' ), // Falls back to 'title' (above).
			// 'parent_slug'     => 'themes.php', // Make options page a submenu item of the themes menu.
			// 'capability'      => 'manage_options', // Cap required to view options-page.
			// 'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			// 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'cmb2' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

		$_pages = get_pages();
		$pages = array();
		for ($i=0; $i < count($_pages); $i++) { 
			$pages[$_pages[$i]->ID] = $_pages[$i]->post_title;
		}		

		$cmb->add_field( array(
			'name'    => __( 'Tracking Page', 'mfcommerce' ),
			'id'      => 'tracking_page',
			'type'    => 'select',
			'options' => $pages
		) );

		$cmb->add_field( array(
			'name'    => __( 'Testing mode', 'mfcommerce' ),
			'id'      => 'testing',
			'type'    => 'checkbox'
		) );

		$cmb->add_field( array(
			'name'    => __( 'Is Production', 'mfcommerce' ),
			'id'      => 'paypal_production',
			'type'    => 'checkbox'
		) );

		/*$cmb->add_field( array(
			'name'    => __( 'Checkout Page', 'mfcommerce' ),
			'id'      => 'checkout_page',
			'type'    => 'select',
			'options' => $pages
		) );*/

	}

	public function rest_api_init(){

		register_rest_route('coupons', '/discounted', array(
	        'methods'  => 'POST',
	        'args' => array(
	            'items' 				=> MFC_Utils::api_validation_rule(true),
	        	'coupon' 				=> MFC_Utils::api_validation_rule(true)
	        ),
	        'callback' => array($this, 'controller_coupons_discounted'),
	        'permission_callback' => '__return_true'
	    ));

	    register_rest_route('checkout', '/card-information', array(
	        'methods'  => 'GET',
	        'args' => array(
	        	'card_number' 			=> MFC_Utils::api_validation_rule(true),
	        	'payment_method' 		=> MFC_Utils::api_validation_rule(true)
	        ),
	        'callback' => array($this, 'controller_checkout_card_information'),
	        'permission_callback' => '__return_true'
	    ));

	    register_rest_route('checkout', '/payment', array(
	        'methods'  => 'POST',
	        'args' => array(
	        	'items' 				=> MFC_Utils::api_validation_rule(true),
	        	'contact' 				=> MFC_Utils::api_validation_rule(true),
	        	'shipping' 				=> MFC_Utils::api_validation_rule(true),
	        	'data' 					=> MFC_Utils::api_validation_rule(true)
	        ),
	        'callback' => array($this, 'controller_checkout_payment'),
	        'permission_callback' => '__return_true'
	    ));

	    if($this->get_value('testing')){

	    	register_rest_route('test', '/coupon', array(
		        'methods'  => 'GET',
		        'args' => array(),
		        'callback' => function( WP_REST_Request $request ){
		        	$request->set_param( 'items', array( array('id' => 5, 'quantity' => 3 ) ) );
					$request->set_param( 'coupon', 'TEST' );
					return $this->controller_coupons_discounted($request);
		        },
	        	'permission_callback' => '__return_true'
		    ));

		    register_rest_route('test', '/card-information', array(
		        'methods'  => 'GET',
		        'args' => array(),
		        'callback' => function( WP_REST_Request $request ){
		        	$request->set_param( 'card_number', '4931365000476724' );
					$request->set_param( 'payment_method', 'fiserv' );
					return $this->controller_checkout_card_information($request);
		        },
	        	'permission_callback' => '__return_true'
		    ));

		    register_rest_route('test', '/checkout', array(
		        'methods'  => 'GET',
		        'args' => array(),
		        'callback' => function( WP_REST_Request $request ){
		        	$request->set_param( 'items', array( array('id' => 5, 'quantity' => 3 ) ) );
					$request->set_param( 'contact', array(
						'name' => 'Chuchito Pérez',
						'email' => 'deluzmax@gmail.com'
					));
					$request->set_param( 'shipping', array(
						'name' => 'Chuchito',
						'last_name' => 'Pérez',
						'street' => 'Calle A',
						'ext_number' => '1',
						'int_number' => '',
						'phone' => '555555555',
						'postal_code' => '50000',
						'state' => 'CDMX',
						'city' => 'CDMX',
						'municipality' => 'Miguel Hidalgo',
						'neighborhood' => 'Narvarte',
						'directions' => 'Entre Amores y Xola'
					));
					$request->set_param( 'data', array(
						'coupon' => 'TEST',
						'payment_method' => 'fiserv',
						'payment_method_card' => 'VISA',
						'transaction_amount' => '1500',
						'installments' => '0',
						'token' => '',
						'card_number' => '4931365000476724',
						'card_securityCode' => '123',
						'card_month' => '10',
						'card_year' => '24'
					));
					return $this->controller_checkout_payment($request);
		        },
	        	'permission_callback' => '__return_true'
		    ));
		}
	}

	/*----------------------------------------------------------------------------------------------------
		REST API Controllers
	----------------------------------------------------------------------------------------------------*/
	public function controller_coupons_discounted( WP_REST_Request $request ){
		$items = new MFC_Order_Items($request->get_param('items'));
	    return array( 'discounted' => $items->get_discounted($request->get_param('coupon')) );
	}

	public function controller_checkout_card_information( WP_REST_Request $request ){
		$card_information = apply_filters("mfc_get_card_information_{$request->get_param('payment_method')}",false,$request->get_param('card_number'));
		return $card_information ?: MFC_Utils::api_error_unprocessable_entity( __('Invalid Card','mfc-firstdatagateway') );	
	}

	public function controller_checkout_payment( WP_REST_Request $request ){
		return $status = $this->checkout(
			$request->get_param('items'),
			$request->get_param('contact'),
			$request->get_param('shipping'),
			$request->get_param('data')
		);
		if($status['status'] == 'rejected'){
			return MFC_Utils::api_error_unprocessable_entity( $status['message'], $status );
		}
		return $status;
	}

	/*----------------------------------------------------------------------------------------------------
		Process
	----------------------------------------------------------------------------------------------------*/
	private function checkout($items, $contact, $shipping, $data){
		$payment = new MFC_Payment();
		$payment->items = $items;
		$payment->contact = $contact;
		$payment->shipping = $shipping;
		$payment->data = $data;
		$payment->calculate();

    	if((float) $payment->data_transaction_amount != $payment->details['total']){
    		return array(
				'status' => 'rejected',
				'code' => 'different_transaction_amount',
				'message' => __('Different transaction amount','mfcommerce')
			);
    	}

    	do_action("mfc_payment_before_checkout",$payment);

    	$status = apply_filters("mfc_payment_do_{$payment->data_payment_method}", array(
			'status' => 'rejected',
			'code' => 'data_payment_method_disabled',
			'message' => __('Payment method disabled', 'mfcommerce')
		), $payment->details, $payment);

		do_action("mfc_payment_after_checkout", $payment, $status);

		if(isset($status['status'])){
			if($status['status'] == 'approved'){
				$params = $payment->to_array();
				$params['transaction_amount'] = $payment->details['total'];
				$params['state'] = $payment->shipping_state;
				$params['coupon'] = $payment->data_coupon;
				$order = $this->plugin->orders->register_order( $params, $payment->items );
				$status['order_uniqid'] = $order->uniqid;
			}
			do_action("mfc_payment_status_{$status['status']}", $payment, $status);
		} else {
			return array(
				'status' => 'rejected',
				'code' => 'payment_error',
				'message' => __('Payment error','mfcommerce')
			);	
		}
		return $status;
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