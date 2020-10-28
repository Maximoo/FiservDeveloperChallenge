<?php
/**
 * Plugin Name: Fiserv FirstData Gateway
 * Plugin URI:  https://mercadofloral.com
 * Description: Fiserv FirstData Gateway for Mercado Floral E-Commerce
 * Version:     1.0.0
 * Author:      Ricardo Máximo
 * Author URI:  https://mercadofloral.com
 * Donate link: https://mercadofloral.com
 * License:     Private
 * Text Domain: mfc_firstdatagateway
 * Domain Path: /languages
 *
 * @link    https://mercadofloral.com
 *
 * @package MFC_FirstDataGateway
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

/**
 * Autoloads files with classes when needed.
 *
 * @since  1.0.0
 * @param  string $class_name Name of the class being requested.
 * @return void
 */
function MFC_autoload_classes( $class_name ) {

	// If our class doesn't have our prefix, don't load it.
	if ( 0 !== strpos( $class_name, 'MFC_' ) ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'MFC_' ) ) ) );

	// Include our file.
	MFC_FirstDataGateway::include_file( 'includes/class-' . $filename );
}
spl_autoload_register( 'MFC_autoload_classes' );

//require 'vendor/autoload.php';

/**
 * Main initiation class.
 *
 * @since  1.0.0
 */
final class MFC_FirstDataGateway {

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
	 * @var    MFC_FirstDataGateway
	 * @since  1.0.0
	 */
	protected static $single_instance = null;


	/**
	 * Instance of MFC_Gateway
	 *
	 * @since1.0.0
	 * @var MFC_Gateway
	 */
	protected $gateway;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   1.0.0
	 * @return  MFC_FirstDataGateway A single instance of this class.
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
		$this->gateway = new MFC_Gateway( $this );
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


		
		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
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
		load_plugin_textdomain( 'mfc-firstdatagateway', false, dirname( $this->basename ) . '/languages/' );

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
			case 'gateway':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $filename Name of the file to be included.
	 * @return boolean          Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once $file;
		}
		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path.
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}

	public function call_api( $api_url, $parameters = array(), $headers = array(), $method = 'GET', $is_file = false ){
		$curl = curl_init();
		switch ($method){
	        case "POST":
	        case "PUT":
	        	if($method == 'POST'){
	        		curl_setopt($curl, CURLOPT_POST, true);	
	        	} else {
	        		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
	        	}
	            if (!empty($parameters)){
	            	if($is_file){
	            		foreach ($parameters as $key => $value) {
	            			$parameters[$key] = curl_file_create($value,mime_content_type($value),basename($value));
	            		}
	            		curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
	            	} else {
	            		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters,JSON_HEX_QUOT));
	            	}
	            }
	            break;
	        default:
	            if (!empty($parameters)){
	                $api_url .= "?" . http_build_query($parameters);
	            }
	    }

	    if($is_file){
	    	$headers['Content-Type'] = 'multipart/form-data';
	    } else {
	    	if(!isset($headers['Accept'])){
	    		$headers['Accept'] = 'application/json';
	    	}
	    	if(!isset($headers['Content-type'])){
	    		$headers['Content-type'] = 'application/json';
	    	}
	    }
	    $httpheaders = array();
	    foreach ($headers as $header => $value) {
	    	if(is_int($header)){
	    		curl_setopt($curl, $header, $value);	
	    	} elseif(is_string($header)){
	    		$httpheaders[] = $header.': '.$value;
	    	}
	    }
	    if(!empty($httpheaders)){
	    	curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheaders);
	    }
	    curl_setopt($curl, CURLOPT_URL, $api_url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    //curl_setopt($curl, CURLINFO_HEADER_OUT, true);
	    $result = curl_exec($curl);
	    //var_dump(curl_getinfo($curl));
	    curl_close($curl);
	    $json = json_decode($result,true);
	    return json_last_error() == JSON_ERROR_NONE ? $json : $result;
	}
}

/**
 * Grab the MFC_FirstDataGateway object and return it.
 * Wrapper for MFC_FirstDataGateway::get_instance().
 *
 * @since  1.0.0
 * @return MFC_FirstDataGateway  Singleton instance of plugin class.
 */
function mfc_firstdatagateway() {
	return MFC_FirstDataGateway::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( mfc_firstdatagateway(), 'hooks' ) );

//add_action( 'plugins_loaded', array( mfc_firstdatagateway(), '_migrate' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( mfc_firstdatagateway(), '_activate' ) );
register_deactivation_hook( __FILE__, array( mfc_firstdatagateway(), '_deactivate' ) );
