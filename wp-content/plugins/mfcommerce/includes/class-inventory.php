<?php
/**
 * Mercado Floral E-Commerce Inventory.
 *
 * @since   1.0.0
 * @package 
 */

class MFC_InventoryItem {

	private $post;
	private $plugin = null;

	public function __construct( $post, $plugin ) {
		$this->plugin = $plugin;
		$this->post = $post;
	}

	public function __get( $key ){
		switch($key){
			case 'id':
				return $this->post->ID;
			break;
			case 'stock':
			case 'sku':
			case 'price':
				return get_post_meta($this->post->ID, $key, true);
			break;
			case 'price_format':
				return '$' . number_format($this->price,2,'.',',');
			break;
		}
		return  empty($this->post->{$key}) ? '' : $this->post->{$key};
	}
}

/**
 * Mercado Floral E-Commerce Inventory class.
 *
 * @since 1.0.0
 */
class MFC_Inventory {
	/**
	 * Parent plugin class.
	 *
	 * @var    
	 * @since  1.0.0
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $key = 'mfcommerce_inventory';

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'mfcommerce_inventory_metabox';

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
	 * @param   $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Set our title.
		$this->title = esc_attr__( 'MFCommerce - Inventory', 'mfcommerce' );
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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );
		add_action( 'wp_ajax_update_inventory', array( $this, 'update_inventory') );
		
	}

	public function admin_enqueue_scripts() {
		if(!empty($_GET['page']) && $_GET['page'] == self::$key){
			wp_enqueue_script( 'pmc-coupons-setup', $this->plugin->url . 'assets/js/table_inventory_scripts.js', array('jquery'), '1.0.0', true );
			wp_localize_script( 'pmc-coupons-setup', 'update_inventory_vars', array(
		        'url'    => admin_url( 'admin-ajax.php' ),
		        'nonce'  => wp_create_nonce( 'update_inventory-nonce' ),
		        'action' => 'update_inventory'
		    ) );
		}
	}

	public function admin_init(){
		if(!empty($_GET['page']) && $_GET['page'] == self::$key){
			$redirect = '';
			if(isset($_GET['action'])){
				switch($_GET['action']){
					case 'edit':
						$redirect = admin_url( '/post.php?post=' . $_GET['id'] . '&action=edit' );
					break;
					case 'show':
						$redirect = get_permalink($_GET['id']);
					break;
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
		extract($this->get_params());
		$wp_query = $this->get_inventory_query($order, $order_by, $search, -1, -1);
		MFC_Utils::print_csv($this->get_inventory( $wp_query), $this->get_headers());
	}

	public function update_inventory(){
		$nonce = sanitize_text_field( $_POST['nonce'] );

		$message = 'Error';
		$success = false;
 
	    if ( current_user_can('inventory_edit') && wp_verify_nonce( $nonce, 'update_inventory-nonce' ) ) {
	        
	        $ids = explode(',', $_POST['ids']);
	    	$stock = (int) $_POST['stock'];

	    	for ($i=0; $i < count($ids); $i++) { 
	    		update_post_meta($ids[$i],'stock',$stock);
	    	}

	    	$message = 'Actualizado correctamente.';
			$success = true;
	    }

	    echo json_encode(array('message' => $message, 'success' => $success));
	 
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

			'option_key'   => self::$key, // The option key and admin menu page slug.
			// 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
			'menu_title'      => esc_html__( 'Inventory', 'mfcommerce' ), // Falls back to 'title' (above).
			'parent_slug'     => 'mfcommerce_settings', // Make options page a submenu item of the themes menu.
			'capability'      => 'inventory_edit', // Cap required to view options-page.
			//'position'        => 2, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			'display_cb'      => array( $this, 'inventory_display_cb' ), // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'cmb2' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

	}

	public function inventory_display_cb( $cmb_options ) {
		$this->print_list();
	}

	private function get_headers(){
		return array(
			array(
				'id' => 'post_title',
				'label' => __('Product','mfcommerce'),
				'column' => 'primary',
				'sortable' => true
			),
			array(
				'id' => 'sku',
				'label' => __('SKU','mfcommerce'),
				'column' => 'small',
				'sortable' => false
			),
			array(
				'id' => 'price',
				'field' => 'price_format',
				'label' => __('Price','mfcommerce'),
				'column' => 'small',
				'sortable' => false
			),
			array(
				'id' => 'stock',
				'label' => __('Stock','mfcommerce'),
				'column' => 'small',
				'sortable' => true
			)
		);
	}

	private function get_params(){
		return array(
			'order' => empty($_GET['order']) ? 'ASC' : $_GET['order'],
			'order_by' => empty($_GET['orderby']) ? 'updated_at' : $_GET['orderby'],
			'search' => empty($_GET['s']) ? '' : $_GET['s']
		);
	}

	private function print_list(){

		$post_per_page = 20;

		extract($this->get_params());
		$paged = empty($_GET['paged']) ? 1 : $_GET['paged'];

    	$wp_query = $this->get_inventory_query($order, $order_by, $search, $paged, $post_per_page);

		$total = (int) $wp_query->found_posts;

		if( $paged > ceil($total / $post_per_page) ){
			$paged = max(1,ceil($total / $post_per_page));
		}

		MFC_Utils::print_table(self::$key, $this->title, $this->get_inventory($wp_query), $this->get_headers(),
		array(), 
		array(
			array(
				'id' => 'update',
				'label' => 'Actualizar Inventario',
				'bulk' => true,
				'primary' => true
			),
			array(
				'id' => 'edit',
				'label' => 'Edit',
				'bulk' => false
			),
			array(
				'id' => 'show',
				'label' => 'Ver',
				'bulk' => false
			)
		), 
		array(), $total, $post_per_page, true, false, true);
		?>
		<div id="list-modal" style="position: fixed; width: 100%; height: 100%; left:0; top:0; background-color: #fff; opacity: 0.5; display: none; z-index: 9999999;"></div>
		<?php
	}
	private function get_inventory_query($order, $order_by, $search, $paged, $post_per_page){
		$args = array( 
			'posts_per_page' 	=> $post_per_page, 
			'paged' 			=> $paged,
			'orderby'			=> $order_by,
	        'order'				=> $order,
			'post_type' 		=> 'product',
			'suppress_filters' 	=> true
		);

		if($order_by == 'stock'){
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = $order_by;
		}

		if(!empty($search)){
			$args['s'] = $search;
			$args['avoid_preget'] = true;
		}

    	return new WP_Query( $args );
	}

	private function get_inventory( $wp_query ){
		$plugin = $this->plugin;
		return array_map(function($val) use ($plugin){ return new MFC_InventoryItem($val, $plugin); }, $wp_query->posts);
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
