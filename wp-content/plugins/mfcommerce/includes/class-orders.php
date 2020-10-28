<?php
/**
 * Mercado Floral E-Commerce Orders.
 *
 * @since   1.0.0
 * @package PM_Casa_E_Commerce
 */


class MFC_Orders_Item {

	private $data;

	private $plugin = null;

	public function __construct( $result, $plugin ) {
		$this->plugin = $plugin;
		if(!empty($result)){
			$this->data = (array) $result; 
			$this->data['data'] = json_decode($result['data'],true);
		}
	}

	public function __get( $key ){
		if(empty($this->data)){
			return '';
		}
		switch($key){
			case 'data_button':
				$url = admin_url('admin-ajax.php?action=order_detail&id='.$this->id);
				return '<a href="'. $url .'" title="Details - '. $this->order_id .'" class="button thickbox"><span class="dashicons dashicons-info" style="vertical-align: sub;"></span></a> <a href="'. $url .'&cart" title="Products - '. $this->order_id .'" class="button thickbox"><span class="dashicons dashicons-cart" style="vertical-align: sub;"></span></a>';
			break;
			case 'total_format':
				return '$' . number_format($this->total,2,'.',',');
			break;
			case 'products_detail':
				$text = '';
				for($i = 0; $i < $this->get_total_products(); $i++){
					$text .= $this->get_product_text_by_index($i) . ' | ';
				}
				return $text;
			break;
		}
		return  empty($this->data[$key]) ? '' : $this->data[$key];
	}

	public function get_total_products(){
		return count($this->data['data']['products']);
	}

	public function get_product_by_index( $index ){
		return !empty($this->data['data']['products']) ? $this->data['data']['products'][$index] : null ;
	}

	public function get_product_text_by_index( $index ){
		$product = $this->get_product_by_index( $index );
		return $product ? '(' . $product["count"] .') '. $product["sku"] .' - '. $product['title'] . ' ' . $product['description'] : ''; 
	}
}


/**
 * Mercado Floral E-Commerce Orders class.
 *
 * @since 1.0.0
 */
class MFC_Orders {
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
	protected static $key = 'mfcommerce_order';

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'mfcommerce_order_metabox';

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
		$this->title = esc_attr__( 'MFCommerce - Orders', 'mfcommerce' );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {
		// Hook in our actions to the admin.		
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_thickbox();
		add_action( 'wp_ajax_order_detail', array( $this, 'ajax_order_detail') );
		
	}

	public function admin_init(){
		if(!empty($_GET['page']) && $_GET['page'] == self::$key){
			$redirect = '';
			if(isset($_GET['action'])){
				switch($_GET['action']){
					case 'export':
						header('Content-Type: application/csv');
						header('Content-Disposition: attachment; filename='. self::$key . '_' . current_time('Y-m-d-H-i-s') . '.csv');
						header('Pragma: no-cache');
						$this->print_csv();
						exit;
					break;
				}
			}
			if(!empty($redirect)){
				wp_redirect($redirect);
				exit();
			}
		}
	}

	private function print_csv(){
		$post_per_page = 20;
		extract($this->get_params());
		$paged = empty($_GET['paged']) ? 1 : $_GET['paged'];
		$total = $this->get_order_count($payment_method, $search);
		if( $paged > ceil($total / $post_per_page) ){
			$paged = max(1,ceil($total / $post_per_page));
		}
		$headers = $this->get_headers();
		$headers[count($headers) - 1] = array(
			'id' => 'data',
			'field' => 'products_detail',
			'label' => __('Products Detail','mfcommerce'),
			'column' => 'small',
			'sortable' => false
		);
		MFC_Utils::print_csv($this->get_order($payment_method, $order_by, $order, $search, $paged, $post_per_page ), $headers);
	}

	public function ajax_order_detail() {

		$is_cart = isset($_GET['cart']);

		$order = $this->get_order_by_id($_GET['id']);
		if(!empty($order)): ?>
		<style>
			.pmc-detail{padding:15px;}
			.pmc-detail__row{margin-bottom: 5px;padding-bottom: 5px;border-bottom: 1px dotted #ddd;}
			.pmc-detail__row::after{display: block;content: "";clear: both;}
			.pmc-detail__row h4{margin: 0 10px 0 0; float: left; text-transform: uppercase;}
			.pmc-detail__content del{color: red; font-weight: bold;}
			.pmc-detail__content ins{color: green; font-weight: bold;}
			#TB_ajaxContent{width:100% !important;padding: 0;}
			.post-php #TB_ajaxContent{height: calc(100% - 30px) !important;}
			@media (max-width: 911px) {
				#TB_window{width:100% !important;margin:0 !important;left:0 !important;top:0 !important;height: 100vh !important;}
				#TB_ajaxContent{height: calc(100% - 30px) !important;}
			}
		</style>
		<div class="pmc-detail">
			<?php if($is_cart): ?>
			<?php for($i = 0; $i < $order->get_total_products(); $i++): ?>
			<div class="pmc-detail__row">
				<div class="pmc-detail__content"><?= $order->get_product_text_by_index($i) ?></div>
			</div>
			<?php endfor;?>
			<?php else: ?>
			<?php foreach($order->data as $key => $value): if($key != 'resumen' && $key != 'details' && $key != 'products'): ?>
			<div class="pmc-detail__row">
				<h4><?=$key?></h4>
				<div class="pmc-detail__content"><?= in_array($key,array('resumen','guide_tracking')) ? $value : htmlentities($value)?></div>
			</div>
			<?php endif; endforeach; endif; ?>
		</div>
		<?php endif;
		wp_die();
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

			'option_key'   	  => self::$key, // The option key and admin menu page slug.
			// 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
			'menu_title'      => esc_html__( 'Orders', 'mfcommerce' ), // Falls back to 'title' (above).
			'parent_slug'     => 'mfcommerce_settings', // Make options page a submenu item of the themes menu.
			//'capability'      => 'order_read', // Cap required to view options-page.
			//'position'        => 2, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			'display_cb'      => array( $this, 'order_display_cb' ), // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'cmb2' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

		$cmb->add_field( array(
			'name'    => __( 'Tracking Page', 'mfcommerce' ),
			'id'      => 'tracking_page',
			'type'    => 'text'
		) );

	}

	public function order_display_cb( $cmb_options ) {
		if(isset($_GET['action'])){
			switch($_GET['action']){
				case 'add':
				case 'edit':
					//$this->print_add_edit_form( $_GET['action'] == 'edit' );
				return;
			}
		}
		$this->print_list();
	}

	private function get_headers(){
		return array(
			array(
				'id' => 'uniqid',
				'label' => __('Order ID','mfcommerce'),
				'column' => 'primary',
				'sortable' => true
			),
			array(
				'id' => 'status',
				'field' => 'status',
				'label' => __('Status','mfcommerce'),
				'column' => 'small',
				'sortable' => true
			),
			array(
				'id' => 'transaction_amount',
				'field' => 'transaction_amount',
				'label' => __('Total','mfcommerce'),
				'column' => 'small',
				'sortable' => true
			),
			array(
				'id' => 'owner_id',
				'field' => 'owner_id',
				'label' => __('Store','mfcommerce'),
				'column' => 'small',
				'sortable' => true
			),
			array(
				'id' => 'created_at',
				'field' => 'created_at_str',
				'label' => __('Date','mfcommerce'),
				'column' => 'small',
				'sortable' => true
			)/*,
			array(
				'id' => 'actions',
				'field' => 'actions',
				'label' => __('Actions','mfcommerce'),
				'column' => 'large',
				'sortable' => false
			)*/
		);
	}

	private function get_params(){
		return array(
			'status' => empty($_GET['filter_status']) ? 'all' : $_GET['filter_status'],
			'order' => empty($_GET['order']) ? 'DESC' : $_GET['order'],
			'order_by' => empty($_GET['orderby']) ? 'updated_at' : $_GET['orderby'],
			'search' => empty($_GET['s']) ? '' : $_GET['s'],
			'paged' => empty($_GET['paged']) ? 1 : $_GET['paged'],
			'post_per_page' => 20
		);
	}

	private function print_list(){
		extract($this->get_params());
		
		$total = self::get_orders_count(array('status'=>$status), $search, array('uniqid','contact'));

		if( $paged > ceil($total / $post_per_page) ){
			$paged = max(1,ceil($total / $post_per_page));
		}

		$orders = self::get_orders(array('status'=>$status), $order_by, $order, $search, array('uniqid','contact'), $paged, $post_per_page);

		MFC_Utils::print_table(self::$key, $this->title, $orders, $this->get_headers(),
		array(), 
		array(), 
		array(
			array(
				'id' => 'filter_status',
				'label' => __('Status', 'mfcommerce'),
				'options' => array(
					'all' => __('All Statuses','mfcommerce'), 
					'pending' => __('Pending','mfcommerce'),
					'failed' => __('Failed','mfcommerce'),
					'holding' => __('Holding','mfcommerce'),
					'canceled' => __('Canceled','mfcommerce'),
					'processing' => __('Processing','mfcommerce'),
					'completed' => __('Completed','mfcommerce'),
					'refunded' => __('Refunded','mfcommerce'),
				)
			)
		), $total, $post_per_page, true, false, true);
	}

	public static function get_order_by( $value, $field = 'id' ){
		$row = MFC_Model::get_row('mfc_order');
		if(!empty($row)){
			return new MFC_Order($row);
		}
		return false;
	}

	public static function register_order( $data, $items ){
		$order = new MFC_Order($data, $items);
		$order->save();
		return $order;
	}

	public static function get_orders( $fields = array(), $orderby = 'updated_at', $order = 'DESC', $search = '', $search_fields = array(), $paged = false, $post_per_page = 10 ){
		return array_map( function( $r ){ 
			return new MFC_Order($r); 
		}, MFC_Model::get_results('mfc_orders', $fields, $orderby, $order, $search, $search_fields, 'OR', $paged, $post_per_page));
	}

	private static function get_orders_count( $fields = array(), $search = '', $search_fields = array(), $search_fields_relation = 'OR' ){
		return MFC_Model::get_results_count('mfc_orders', $fields, $search, $search_fields, $search_fields_relation);
	}

	private static function get_count(){
		return MFC_Model::get_count('mfc_orders');
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
