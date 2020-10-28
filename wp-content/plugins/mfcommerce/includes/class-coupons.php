<?php
/**
 * Mercado Floral E-Commerce Coupons.
 *
 * @since   1.0.0
 * @package PM_Casa_E_Commerce
 */

class MFC_Coupon {

	private $data;

	private $plugin = null;

	public function __construct( $result, $plugin ) {
		$this->plugin = $plugin;
		if(!empty($result)){
			$this->data = (array) $result;
			$this->data['id'] = !empty($this->data['id']) ? (int) $this->data['id'] : 0;
			$this->data['discount'] = !empty($this->data['discount']) ? (float) $this->data['discount'] : 0;
			$this->data['redentions'] = !empty($this->data['redentions']) ? (int) $this->data['redentions'] : 0;
			$this->data['redentions_limit'] = !empty($this->data['redentions_limit']) ? (int) $this->data['redentions_limit'] : -1;
		}
	}

	public function __get( $key ){
		if(empty($this->data)){
			return '';
		}
		switch($key){
			case 'categories_ids':
				return explode(',', $this->data['categories']);
			break;
			case 'categories_list':
				if(!empty($this->data['categories'])){
					$categories = $this->plugin->product_category->get_categories_option();
					return implode(', ', array_values( array_intersect_key($categories, array_flip($this->categories_ids)) ));
				}
				return 'Todas las Categorías';
			break;
			case 'discount_text':
				return $this->data['discount'] * 100 . '%';
			break;
			case 'start_time':
				return strtotime($this->data['start']);
			break;
			case 'end_time':
				return strtotime($this->data['end']);
			break;
			case 'is_active':
				return $this->data['status'] == 'active';
			break;
			case 'is_expired':
				$current = current_time('timestamp');
				return $current > $this->end_time || $current < $this->start_time;
			break;
			case 'is_valid':
				return $this->is_active && !$this->is_expired;
			break;
		}
		return  empty($this->data[$key]) ? '' : $this->data[$key];
	}
}

/**
 * Mercado Floral E-Commerce Coupons class.
 *
 * @since 1.0.0
 */
class MFC_Coupons {
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
	protected static $key = 'mfcommerce_coupons';

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'mfcommerce_coupons_metabox';

	protected static $metabox_add_edit_id = 'mfcommerce_coupons_metabox_add';

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

	protected $coupons = array();

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
		$this->title = esc_attr__( 'MFCommerce - Coupons', 'mfcommerce' );
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
		add_filter( 'mfc_payment_get_product_discount', array( $this, 'mfc_payment_get_product_discount'), 10, 3);
	}

	public function admin_enqueue_scripts() {
		if(!empty($_GET['page']) && $_GET['page'] == self::$key){
			wp_enqueue_script( 'pmc-coupons-setup', $this->plugin->url . 'assets/js/table_scripts.js', array('jquery'), '1.0.0', true );
		}
	}

	public function get_coupon( $coupon ){
		if(!isset($this->coupons[$coupon])){
			$this->coupons[$coupon] = $this->get_coupon_by_name($coupon);
		}
		if(!empty($this->coupons[$coupon]) && $this->coupons[$coupon]->is_valid){
			return $this->coupons[$coupon];
		}
		return null;
	}

	public function get_product_discount( $product, $coupon ){
		$coupon = $this->get_coupon($coupon);
		if(!empty($coupon)){
			return $coupon->discount;
		}
	}

	public function mfc_payment_get_product_discount( $discount, $product, $coupon ){
		return $this->get_product_discount($product, $coupon);
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

			'option_key'  	  	 => self::$key, // The option key and admin menu page slug.
			// 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
			'menu_title'     	 => esc_html__( 'Coupons', 'mfcommerce' ), // Falls back to 'title' (above).
			'parent_slug'    	 => 'mfcommerce_settings', // Make options page a submenu item of the themes menu.
			// 'capability'      => 'manage_options', // Cap required to view options-page.
			// 'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			'display_cb'      	 => array( $this, 'coupons_display_cb' ), // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'cmb2' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

		if(!empty($_GET['page']) && $_GET['page'] != self::$key){ return; }

		$is_edit = isset($_GET['action']) && $_GET['action'] == 'edit' && !empty($_GET['id']);

		if($is_edit){
			$coupon = $this->get_coupon_by_id($_GET['id']);
			if(empty($coupon)){
				echo $_GET['id'] . ' Not Found';
				return;
			}
		}

		$cmb = new_cmb2_box( array(
			'id'            => self::$metabox_add_edit_id,
			'title'         => __( ($is_edit ? 'Add':'Edit') . ' Coupon', 'mfcommerce' ),
			'show_names'    => true,
			'hookup' 		=> false,
			'save_fields' 	=> false,
			'save_button' 	=> __( ($is_edit ? 'Add':'Edit') . ' Coupon', 'mfcommerce' )
		) );

		$cmb->add_field( array(
			'id'   => 'action',
			'type' => 'hidden',
			'default' => $is_edit ? 'update' : 'add'
		) );

		if($is_edit){
			$cmb->add_field( array(
				'id'   => 'id',
				'type' => 'hidden',
				'default' => $_GET['id']
			) );
		}

		$cmb->add_field( array(
			'name' => __( 'Coupon', 'mfcommerce' ),
			'id'   => 'coupon',
			'type' => 'text',
			'attributes' => array(
				'autocomplete' 	=> 'off',
				'required'    	=> 'required'
			),
			'default' => $is_edit ? $coupon->coupon : false
		) );

		$cmb->add_field( array(
			'name' => __( 'Description', 'mfcommerce' ),
			'id'   => 'description',
			'type' => 'text',
			'attributes' => array(
				'autocomplete' 	=> 'off'
			),
			'default' => $is_edit ? $coupon->description : false
		) );

		$cmb->add_field( array(
			'name' => __( 'Discount', 'mfcommerce' ),
			'desc' => '% Porciento',
			'id'   => 'discount',
			'type' => 'text',
			'attributes' => array(
				'autocomplete' 	=> 'off'
			),
			'default' => $is_edit ? $coupon->discount * 100 : false
		) );

		//redentions_limit

		$cmb->add_field( array(
			'name' => __( 'Categories', 'mfcommerce' ),
			'id'   => 'categories',
			'type' => 'multicheck',
			'select_all_button' => false,
			'options' => $this->plugin->product_category->get_categories_option(),
			'default' => $is_edit ? explode(',', $coupon->categories) : false
		) );

		//

		$cmb->add_field( array(
			'name' => __( 'Start Date', 'mfcommerce' ),
			'id'   => 'start',
			'type' => 'text_datetime_timestamp',
			'date_format' => 'Y-m-d',
			'time_format' => 'h:i A',
			'attributes' => array(
				'autocomplete' 	=> 'off',
				'required'    	=> 'required'
			),
			'default' => $is_edit ? $coupon->start : false
		) );

		$cmb->add_field( array(
			'name' => __( 'End Date', 'mfcommerce' ),
			'id'   => 'end',
			'type' => 'text_datetime_timestamp',
			'date_format' => 'Y-m-d',
			'time_format' => 'h:i A',
			'attributes' => array(
				'autocomplete' 	=> 'off',
				'required'    	=> 'required'
			),
			'default' => $is_edit ? $coupon->end : false
		) );

		$cmb->add_field( array(
			'name' => __( 'Active', 'mfcommerce' ),
			'id'   => 'status',
			'type' => 'checkbox',
			'default' => $is_edit ? $coupon->status == 'active' : true
		) );

	}

	public function coupons_display_cb( $cmb_options ) {

		if(isset($_GET['action'])){
			switch($_GET['action']){
				case 'add':
				case 'edit':
					$this->print_add_edit_form( $_GET['action'] == 'edit' );
				return;
			}
		}
		$this->print_list();
	}

	private function print_add_edit_form( $is_edit = false ){
		$admin_url = admin_url('admin.php?page=' . self::$key);
		?>
		<div class="wrap">
			<a href="<?=$_SERVER["HTTP_REFERER"]?>" class="page-title-action" style="margin: 0 8px 0 0; display: inline-block;"><span class="dashicons dashicons-arrow-left-alt"></span></a>
			<h1 class="wp-heading-inline"><?=$this->title?> - <?=__($is_edit ? 'Edit' : 'Add','mfcommerce')?></h1>
			<hr class="wp-header-end">
			
			<?= cmb2_get_metabox_form( self::$metabox_add_edit_id ) ?>
		</div>
		<?php
	}

	private function get_headers(){
		return array(
			array(
				'id' => 'coupon',
				'label' => 'Cupón',
				'column' => 'primary',
				'sortable' => true
			),
			array(
				'id' => 'description',
				'label' => 'Descripción',
				'column' => 'large',
				'sortable' => false
			),
			array(
				'id' => 'discount',
				'field' => 'discount_text',
				'label' => 'Descuento',
				'column' => 'small',
				'sortable' => true
			),
			array(
				'id' => 'categories',
				'field' => 'categories_list',
				'label' => 'Categorías',
				'column' => 'small',
				'sortable' => false
			),
			/*array(
				'id' => 'redentions',
				'label' => 'Redenciones',
				'column' => 'small',
				'sortable' => true
			),*/
			array(
				'id' => 'start',
				'label' => 'Desde',
				'column' => 'date',
				'sortable' => true
			),
			array(
				'id' => 'end',
				'label' => 'Hasta',
				'column' => 'date',
				'sortable' => true
			)
		);
	}

	private function get_params(){
		return array(
			'status' => empty($_GET['tab']) ? 'active' : $_GET['tab'],
			'category' => empty($_GET['filter_category']) ? 'all' : $_GET['filter_category'],
			'order' => empty($_GET['order']) ? 'DESC' : $_GET['order'],
			'order_by' => empty($_GET['orderby']) ? 'updated_at' : $_GET['orderby'],
			'search' => empty($_GET['s']) ? '' : $_GET['s']
		);
	}

	private function print_list(){

		extract($this->get_params());
		$paged = empty($_GET['paged']) ? 1 : $_GET['paged'];
		$post_per_page = 20;

		$counter = array(
			'active' => $this->get_coupons_count('active', $category, $search),
			'inactive' => $this->get_coupons_count('inactive', $category, $search),
			'expired' => $this->get_coupons_count('expired', $category, $search),
			'coming' => $this->get_coupons_count('coming', $category, $search)
		);

		$total = $counter[$status];

		if( $paged > ceil($total / $post_per_page) ){
			$paged = max(1,ceil($total / $post_per_page));
		}

		MFC_Utils::print_table(self::$key, $this->title, $this->get_coupons( $status, $category, $order_by, $order, $search, $paged, $post_per_page ), $this->get_headers(),
		array(
			array(
				'id' => 'active',
				'label' => 'Activos',
				'count' => $counter['active']
			),
			array(
				'id' => 'expired',
				'label' => 'Expirados',
				'count' => $counter['expired']
			),
			array(
				'id' => 'coming',
				'label' => 'Próximos',
				'count' => $counter['coming']
			),
			array(
				'id' => 'inactive',
				'label' => 'Inactivos',
				'count' => $counter['inactive']
			)
		), 
		array(
			array(
				'id' => 'edit',
				'label' => 'Editar',
				'bulk' => false,
				'primary' => true
			),
			array(
				'id' => 'inactive',
				'label' => 'Desactivar',
				'tab' => array('active','expired'),
				'bulk' => true
			),
			array(
				'id' => 'active',
				'label' => 'Activar',
				'tab' => 'inactive',
				'bulk' => true
			),
			array(
				'id' => 'delete',
				'label' => 'Borrar Permanentemente',
				'bulk' => true,
				'confirm' => true
			)
		), 
		array(
			array(
				'id' => 'filter_category',
				'label' => 'Categoría',
				'options' => array('all' => 'Todas las Categorías') + $this->plugin->product_category->get_categories_option()
			)
		), $total, $post_per_page);
	}

	private function print_csv(){
		extract($this->get_params());
		MFC_Utils::print_csv($this->get_coupons( $status, $category, $order_by, $order, $search ), $this->get_headers());
	}

	private function cast_args( $_args ){
		$args = array_intersect_key($_args, array_flip(array('coupon', 'description', 'redentions_limit')));
		$d = DateTime::createFromFormat('Y-m-d h:i A', $_args['start']['date'] . ' ' . $_args['start']['time']);
		$args['start'] = $d->format('Y-m-d H:i:s');
		$d = DateTime::createFromFormat('Y-m-d h:i A', $_args['end']['date'] . ' ' . $_args['end']['time']);
		$args['end'] = $d->format('Y-m-d H:i:s');
		if(!empty($_args['categories'])){
			$args['categories'] = implode(',', $_args['categories']);
		}
		$args['status'] = !empty($_args['status']) ? 'active' : 'inactive';
		$args['discount'] = !empty($_args['discount']) ? ((float) $_args['discount']) / 100 : 0.00;
		return $args;
	}

	public function admin_init(){
		if(!empty($_GET['page']) && $_GET['page'] == self::$key){
			$redirect = '';
			if(isset($_POST['action'])){
				switch($_POST['action']){
					case 'add':
						$args = $this->cast_args($_POST);
						$this->add_coupon($args);
						$redirect = admin_url('admin.php?page=' . self::$key) . '&tab=' . $args['status'];
					break;
					case 'update':
						$args = $this->cast_args($_POST);
						$this->update_coupon($_POST['id'],$args);
						$redirect = admin_url('admin.php?page=' . self::$key) . '&tab=' . $args['status'];
					break;
				}
			}
			if(isset($_GET['action'])){
				switch($_GET['action']){
					case 'delete':
						foreach (explode(',',$_GET['id']) as $id) {
							$this->delete_coupon($id);
						}
						$redirect = $_SERVER["HTTP_REFERER"];
					break;
					case 'active':
					case 'inactive':
						foreach (explode(',',$_GET['id']) as $id) {
							$this->update_coupon($id,array('status' => $_GET['action']));
						}
						$redirect = $_SERVER["HTTP_REFERER"];
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

	private function add_coupon( $args ){
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . "mfc_coupons", $args );
		return $wpdb->insert_id;
	}

	private function update_coupon( $id, $args ){
		global $wpdb;
		$wpdb->update( $wpdb->prefix . "mfc_coupons", $args, array('id' => $id));
	}

	private function delete_coupon( $id ){
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . "mfc_coupons", array('id' => $id));
	}

	private function get_coupon_by_id( $id ){
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . "mfc_coupons WHERE id = %d";
		return new MFC_Coupon($wpdb->get_row( $wpdb->prepare( $query, $id ), 'ARRAY_A' ), $this->plugin);
	}

	private function get_coupon_by_name( $coupon ){
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . "mfc_coupons WHERE coupon = '%s'";
		return new MFC_Coupon($wpdb->get_row( $wpdb->prepare( $query, $coupon ), 'ARRAY_A' ), $this->plugin);
	}

	private function get_coupons( $status = 'active',  $category = 'all', $orderby = 'updated_at', $order = 'DESC', $search = '', $paged = false, $post_per_page = 10, $count = false ){
		global $wpdb;
		$query = "SELECT ". ($count ? 'COUNT(*)' : '*') ." FROM " . $wpdb->prefix . "mfc_coupons";
		$where = array();
		switch($status){
			case 'expired':
				$where[] = "status = 'active'";
				$where[] = "current_date > end";
			break;
			case 'coming':
				$where[] = "status = 'active'";
				$where[] = "current_date < start";
			break;
			case 'inactive':
				$where[] = "status = 'inactive'";
			break;
			case 'active':
			default:
				$where[] = "status = 'active'";
				$where[] = "current_date >= start AND current_date <= end";
			break;
		}
		if($category != 'all'){
			$where[] = "FIND_IN_SET('". $wpdb->_real_escape($category) . "',categories)";
		}
		if(!empty($search)){
			$where[] = "coupon LIKE '%". $wpdb->_real_escape($search) . "%'";
		}
		if(!empty($where)){
			$query .= " WHERE " . implode(' AND ', $where);
		}
		if(!$count){
			$query .= " ORDER BY ". $wpdb->_real_escape($orderby) . " ". $wpdb->_real_escape($order);
			if($paged !== false ){
				$query .= " LIMIT ". (($paged - 1) * $post_per_page) . "," . $wpdb->_real_escape($post_per_page);
			}
			$plugin = $this->plugin;
			return array_map(function($val) use ($plugin){ return new MFC_Coupon($val, $plugin); },$wpdb->get_results( $query, 'ARRAY_A' ));
		} else {
			return (int) $wpdb->get_var( $query );
		}
	}

	private function get_coupons_count( $status = 'active',  $category = 'all', $search = '' ){
		return $this->get_coupons($status, $category, '', '', $search, '', '', true );
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
