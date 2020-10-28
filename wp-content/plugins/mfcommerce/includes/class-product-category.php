<?php
/**
 * Pcategory.
 *
 * @since   1.0.0
 * @package MFCommerce
 */



/**
 * Mercado Floral E-Commerce Pcategory.
 *
 * @since 1.0.0
 *
 * @see   https://github.com/WebDevStudios/Taxonomy_Core
 */
class MFC_product_category extends Taxonomy_Core {
	/**
	 * Parent plugin class.
	 *
	 * @var    MFCommerce
	 * @since  1.0.0
	 */
	protected $plugin = null;

	public static $slug = 'product-category';

	/**
	 * Constructor.
	 *
	 * Register Taxonomy.
	 *
	 * See documentation in Taxonomy_Core, and in wp-includes/taxonomy.php.
	 *
	 * @since  1.0.0
	 *
	 * @param  MFCommerce $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		parent::__construct(
			// Should be an array with Singular, Plural, and Registered name.
			array(
				__( 'Category', 'mfcommerce' ),
				__( 'Categories', 'mfcommerce' ),
				self::$slug,
			),
			// Register taxonomy arguments.
			array(
				'hierarchical' => true,
			),
			// Post types to attach to.
			array(
				'product',
			)
		);
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'cmb2_admin_init', array( $this, 'cmb2_admin_init' ) );
		add_filter( 'cmb2_valid_img_types', array( $this, 'cmb2_valid_img_types' ) );
		add_filter( "manage_edit-product-category_columns", array( $this, 'custom_column_header'), 10);
		//add_filter( "manage_edit-product-category_sortable_columns", array( $this, 'custom_column_header'), 10);
		add_filter( "manage_product-category_custom_column", array( $this, 'custom_column_content'), 10, 3);
	}

	public function custom_column_header( $columns ){
		array_splice($columns, 3, 0, array('order' => __( 'Order', 'mfcommerce' )));
		return $columns;
	}

	public function custom_column_content( $value, $column_name, $tax_id ){
		if($column_name == 'order'){
			return get_term_meta($tax_id,'order',true);
		}
		return $value;
	}

	public function cmb2_valid_img_types( $valid_types ){
		$valid_types[] = 'svg';
		return $valid_types;
	}

	public function cmb2_admin_init() {

		$cmb = new_cmb2_box( array( 
	 		'id'               => self::$slug . '_metabox', 
	 		'title'            => esc_html__( 'Attributes', 'mfcommerce' ),
	 		'object_types'     => array( 'term' ),
	 		'taxonomies' 	   => array( self::$slug ),
	 		'new_term_section' => false
	 	) );

	 	$cmb->add_field( array(
			'name'    => __( 'Order', 'mfcommerce' ),
			'id'      => 'order',
			'type'    => 'text_small'
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Compact', 'mfcommerce' ),
			'desc'    => __( 'List the items of this category in compact mode by size.', 'mfcommerce' ),
			'id'      => 'compact',
			'type'    => 'checkbox'
		) ); 

	 	$cmb->add_field( array(
			'name'    => __( 'Singular', 'mfcommerce' ),
			'id'      => 'singular',
			'type'    => 'text'
		) ); 

	 	$cmb->add_field( array(
			'name'    => __( 'Image', 'mfcommerce' ),
			'id'      => 'image',
			'type'    => 'file',
			'query_args' => array( 'type' => 'image' )
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Group', 'mfcommerce' ),
			'id'      => 'group',
			'type'    => 'select',
			'options' => array( 'Dormitorio' => 'Dormitorio', 'Baño' => 'Baño' )
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Type Description', 'mfcommerce' ),
			'id'      => 'product_description',
			'type'    => 'wysiwyg'
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Package Image', 'mfcommerce' ),
			'id'      => 'package_image',
			'type'    => 'file',
			'query_args' => array( 'type' => 'image' )
		) );		 

		$cmb->add_field( array(
			'name'    => __( 'Wash Description', 'mfcommerce' ),
			'id'      => 'wash_description',
			'type'    => 'wysiwyg'
		) ); 

		$cmb->add_field( array(
			'name'    => __( 'Wash Icons', 'mfcommerce' ),
			'id'      => 'wash_icons',
			'type'    => 'file_list',
			'query_args' => array( 'type' => 'image' )
		) );
	}

	private $categories_option;
	public function get_categories_option(){
		if(!isset($this->categories_option)){
			$terms = get_terms(array(
			    'taxonomy' => self::$slug,
			    'hide_empty' => false,
			));

			$options = array();
			if(!empty($terms) && !is_wp_error($terms)){
				for ($i=0; $i < count($terms); $i++) { 
					$options[$terms[$i]->term_id] = $terms[$i]->name;
				}
			}
			$this->categories_option = $options;
		}
		return $this->categories_option;
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
			return cmb2_get_option( self::$slug . '_metabox', $key, $default );
		}

		// Fallback to get_option if CMB2 is not loaded yet.
		$opts = get_option( self::$slug . '_metabox', $default );

		$val = $default;

		if ( 'all' == $key ) {
			$val = $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
			$val = $opts[ $key ];
		}

		return $val;
	}
}
