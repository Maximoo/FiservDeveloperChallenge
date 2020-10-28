<?php
/**
 * Product.
 *
 * @since   1.0.0
 * @package MFCommerce
 */


/**
 * Mercado Floral E-Commerce Product post type class.
 *
 * @since 1.0.0
 *
 * @see   https://github.com/WebDevStudios/CPT_Core
 */
class MFC_Product_CPT extends CPT_Core {
	/**
	 * Parent plugin class.
	 *
	 * @var MFCommerce
	 * @since  1.0.0
	 */
	protected $plugin = null;

	public static $slug = 'product';

	/*public static $labels = array(
		"weight" => array(
			"LGRA" => "Ligera",
			"FRME" => "Firme",
			"LGRO" => "Ligero",
			"COMP" => "Completo"
		)
		"color" => array(
			"BC" => "Blanco",
			'GR' => 'Gris',
			'AZ' => 'Azul',
			'BG' => 'Beige',
			'TP' => 'Topo',
			'RJ' => 'Rojo'
		)
	);

	public static function get_label( $key, $label ){
		if(!empty(self::$labels[$key]) && !empty(self::$labels[$key][$label])){
			return self::$labels[$key][$label];
		}
		return $label;
	}

	public static function get_labels(){
		return self::$labels;
	}*/

	/**
	 * Constructor.
	 *
	 * Register Custom Post Types.
	 *
	 * See documentation in CPT_Core, and in wp-includes/post.php.
	 *
	 * @since  1.0.0
	 *
	 * @param  MFCommerce $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Register this cpt.
		// First parameter should be an array with Singular, Plural, and Registered name.
		parent::__construct(
			array(
				esc_html__( 'Product', 'mfcommerce' ),
				esc_html__( 'Products', 'mfcommerce' ),
				self::$slug,
			),
			array(
				'supports' => array(
					'title',
					'excerpt',
					'editor',
					'thumbnail',
				),
				'menu_icon' => 'dashicons-cart', // https://developer.wordpress.org/resource/dashicons/
				'public'    => true,
				'has_archive' => __( 'products', 'mfcommerce' )
			)
		);
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {

		add_action( 'cmb2_admin_init', array( $this, 'cmb2_admin_init' ) );
		//add_action( 'save_post_' . self::$slug , array( $this, 'save_post' ) );
		//add_filter( 'wp_insert_post_data' , array( $this, 'wp_insert_post_data' ), 10, 2 );
	}

	/*public function wp_insert_post_data($data, $postarr){
		if( $data['post_type'] == self::$slug ){
			if(!empty($postarr['sku'])){
				$data['post_name'] = sanitize_title($postarr['post_title'] . ' ' . $postarr['sku']);
			}
		}
		return $data;
	}

	public function save_post($post_id){
		if ( ! wp_is_post_revision( $post_id ) ) {
	        remove_action( 'save_post_' . self::$slug, array( $this, 'save_post' ) );
	        $sku = get_post_meta($post_id,'sku',true);
	        wp_update_post( array(
	            'ID' => $post_id,
	            'post_name' => sanitize_title(get_the_title($post_id) . ' ' . $sku)
	        ));
	        add_action( 'save_post_' . self::$slug, array( $this, 'save_post' ) );
	    }
	}*/

	public function cmb2_admin_init() {

		$cmb = new_cmb2_box( array( 
	 		'id'               => 'mfc_product_attrubites', 
	 		'title'            => esc_html__( 'Attributes', 'mfcommerce' ),
	 		'object_types'     => array( self::$slug ),
	 	) );

	 	$cmb->add_field( array(
			'name'    => __( 'SKU', 'mfcommerce' ),
			'id'      => 'sku',
			'type'    => 'text',
			'column' => array(
				'position' => 4
			)
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Stock', 'mfcommerce' ),
			'id'      => 'stock',
			'type'    => 'text',
			'column' => array(
				'position' => 2
			)
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Price', 'mfcommerce' ),
			'id'      => 'price',
			'type'    => 'text_small',
			'column' => array(
				'position' => 3
			)
		) ); 

		/*foreach (self::$labels as $config => $labels) {
			$cmb->add_field( array(
				'name'             => __( $config, 'mfcommerce' ),
				'id'               => $config,
				'type'             => 'select',
				'show_option_none' => true,
				'options'          => $labels,
			) );
		}*/

		$group_field_id = $cmb->add_field( array(
			'name' 		  => __( 'Product Images', 'mfcommerce' ),
			'id'          => 'images',
			'type'        => 'group',
			'options'     => array(
				'group_title'       => __( 'Image {#}', 'mfcommerce' ),
				'add_button'        => __( 'Add Another Image', 'mfcommerce' ),
				'remove_button'     => __( 'Remove Image', 'mfcommerce' ),
				'sortable'          => true,
			)
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name' => __( 'Product Image Square', 'mfcommerce' ),
			'id'   => 'square',
			'type' => 'file',
			'preview_size' 	   => array( 100, 100 ),
			'query_args' 	   => array( 'type' => 'image' )
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name' => __( 'Product Image Full', 'mfcommerce' ),
			'id'   => 'full',
			'type' => 'file',
			'preview_size' 	   => array( 100, 100 ),
			'query_args' 	   => array( 'type' => 'image' )
		) );
	 	
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $columns Array of registered column names/labels.
	 * @return array          Modified array.
	 */
	public function columns( $columns ) {
		$new_column = array();
		return array_merge( $new_column, $columns );
	}

	/**
	 * Handles admin column display. Hooked in via CPT_Core.
	 *
	 * @since  1.0.0
	 *
	 * @param array   $column   Column currently being rendered.
	 * @param integer $post_id  ID of post to display column for.
	 */
	public function columns_display( $column, $post_id ) {
		switch ( $column ) {
		}
	}
}
