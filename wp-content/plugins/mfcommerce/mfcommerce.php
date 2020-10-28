<?php
/**
 * Plugin Name: Mercado Floral E-Commerce
 * Plugin URI:  https://mercadofloral.com
 * Description: E-Commerce for Mercado Floral
 * Version:     1.0.0
 * Author:      Ricardo Máximo
 * Author URI:  https://mercadofloral.com
 * Donate link: https://mercadofloral.com
 * License:     Private
 * Text Domain: mfcommerce
 * Domain Path: /languages
 *
 * @link    https://mercadofloral.com
 *
 * @package MFCommerce
 * @version 1.0.0
 *
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

/**
 * Copyright (c) 2020 Ricardo Máximo (email : info@mercadofloral.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


// Use composer autoload.
require 'vendor/autoload.php';

/**
 * Main initiation class.
 *
 * @since  1.0.0
 */
final class MFCommerce {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	const VERSION = '1.0.0';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    MFCommerce
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Instance of MFC_Product_CPT
	 *
	 * @since1.0.0
	 * @var MFC_Product_CPT
	 */
	protected $product;

	/**
	 * Instance of MFC_type
	 *
	 * @since1.0.0
	 * @var MFC_type
	 */
	protected $product_category;

	/**
	 * Instance of MFC_Settings_C
	 *
	 * @since1.0.0
	 * @var MFC_Settings_C
	 */
	protected $settings;

	/**
	 * Instance of MFC_Coupons
	 *
	 * @since1.0.0
	 * @var MFC_Coupons
	 */
	protected $coupons;

	/**
	 * Instance of MFC_Orders
	 *
	 * @since1.0.0
	 * @var MFC_Orders
	 */
	protected $orders;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   1.0.0
	 * @return  MFCommerce A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  1.0.0
	 */
	public function plugin_classes() {

		$this->product = new MFC_Product_CPT( $this );
		$this->product_category = new MFC_product_category( $this );
		$this->settings = new MFC_Settings_C( $this );
		$this->orders = new MFC_Orders( $this );
		$this->coupons = new MFC_Coupons( $this );
		$this->inventory = new MFC_Inventory( $this );
		
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Activate the plugin.
	 *
	 * @since  1.0.0
	 */
	public function _activate() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		$this->_migrate();

		if( get_role('orders_manager') ){
		      remove_role( 'orders_manager' );
		}
		add_role( 'orders_manager', __('Encargado de Ventas','mfcommerce'), array( 'orders_read' => true, 'read' => true ) );

		if( get_role('inventory_manager') ){
		      remove_role( 'inventory_manager' );
		}
		add_role( 'inventory_manager', __('Encargado de Inventario','mfcommerce'), array( 'inventory_edit' => true, 'read' => true ) );

		if( get_role('orders_inventory_manager') ){
		      remove_role( 'orders_inventory_manager' );
		}
		add_role( 'orders_inventory_manager', __('Encargado de Ventas e Inventario','mfcommerce'), array( 'orders_read' => true, 'inventory_edit' => true, 'read' => true ) );

		$role = get_role( 'administrator' );
		$role->add_cap( 'orders_read' );
		$role->add_cap( 'inventory_edit' );

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	private function get_coupons_table_sql($wpdb, $charset_collate){
		$table_name = $wpdb->prefix . "mfc_coupons";
		return "CREATE TABLE $table_name ( 
			id bigint(20) NOT NULL AUTO_INCREMENT, 	
			coupon varchar(20) NOT NULL,
			description varchar(120) NULL,
			discount decimal(2,2) NULL,
			categories varchar(560) NULL,
			redentions int DEFAULT 0 NOT NULL,
			redentions_limit int DEFAULT -1 NOT NULL,
			start datetime NOT NULL,
			end datetime NOT NULL,
			status enum('active','inactive') DEFAULT 'active' NOT NULL,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			INDEX index_coupon (coupon)
		) $charset_collate;";
	}

	private function get_orders_table_sql($wpdb, $charset_collate){
		$table_name = $wpdb->prefix . "mfc_orders";
		return "CREATE TABLE $table_name ( 
			id bigint(20) NOT NULL AUTO_INCREMENT,
			uniqid varchar(13) NOT NULL,
			transaction_amount decimal(13,2) NULL,
			shipping text NOT NULL,
			contact text NOT NULL,
			data text NOT NULL,
			details text NOT NULL,
			state varchar(13) NOT NULL,
			coupon varchar(20) NULL,
			status enum('pending','failed','holding','canceled','processing','completed','refunded') DEFAULT 'processing' NOT NULL,
			created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX index_created_at (created_at),
			INDEX index_uniqid (uniqid),
			INDEX index_owner_id (owner_id)
		) $charset_collate;";
	}

	private function get_order_items_table_sql($wpdb, $charset_collate){
		$table_name = $wpdb->prefix . "mfc_order_items";
		return "CREATE TABLE $table_name ( 
			order_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
			quantity int(11) DEFAULT 1 NOT NULL,
			PRIMARY KEY (order_id, product_id)
		) $charset_collate;";
	}

	public function _migrate(){
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );	
		dbDelta( $this->get_coupons_table_sql($wpdb, $charset_collate) );
		dbDelta( $this->get_orders_table_sql($wpdb, $charset_collate) );
		dbDelta( $this->get_order_items_table_sql($wpdb, $charset_collate) );
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  1.0.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

	/**
	 * Init hooks
	 *
	 * @since  1.0.0
	 */
	public function init() {

		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}
		// Load translated strings for plugin.
		load_plugin_textdomain( 'mfcommerce', false, dirname( $this->basename ) . '/languages/' );

		//$this->_migrate();

		// Initialize plugin classes.
		$this->plugin_classes();
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements() {

		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  1.0.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {

		// Do checks for required classes / functions or similar.
		// Add detailed messages to $this->activation_errors array.
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since  1.0.0
	 */
	public function requirements_not_met_notice() {

		// Compile default message.
		$default_message = sprintf( __( 'Mercado Floral E-Commerce is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'mfcommerce' ), admin_url( 'plugins.php' ) );

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
			$details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'product':
			case 'product_category':
			case 'settings':
			case 'coupons':
			case 'inventory':
			case 'orders':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}
}

/**
 * Grab the MFCommerce object and return it.
 * Wrapper for MFCommerce::get_instance().
 *
 * @since  1.0.0
 * @return MFCommerce  Singleton instance of plugin class.
 */
function mfcommerce() {
	return MFCommerce::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( mfcommerce(), 'hooks' ) );

//add_action( 'plugins_loaded', array( mfcommerce(), '_migrate' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( mfcommerce(), '_activate' ) );
register_deactivation_hook( __FILE__, array( mfcommerce(), '_deactivate' ) );
