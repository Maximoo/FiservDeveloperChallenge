<?php
/**
 * Custom Import.
 *
 * @since   1.0.0
 * @package Import
 */



/**
 * Import class.
 *
 * @since 1.0.0
 */
class C_Import {
	/**
	 * Parent plugin class.
	 *
	 * @var    Import
	 * @since  1.0.0
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $key = 'cimport';

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'cimport_metabox';

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
	 * @param  Import $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Set our title.
		$this->title = esc_attr__( 'Import Initial Data', 'cimport' );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {

		// Hook in our actions to the admin.
		
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );
		add_action( 'wp_ajax_' . self::$key, array( $this, 'wp_ajax') );
		add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url_force_https' ), 9 , 2 );

	}

	public function wp_get_attachment_url_force_https( $url, $id ){
		if (strpos($url, 'local') !== false){
			return $url;
		}
		return str_replace( 'http://', 'https://', $url );
	}

	private function file_attachment( $file, $parent_post_id = 0 ){
	    $wordpress_upload_dir = wp_upload_dir();
	    $file_mime = mime_content_type( $file );
	    $file_ext = pathinfo($file, PATHINFO_EXTENSION);
	    $file_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
	    $file_path = $wordpress_upload_dir['path'] . '/' . $file_name;
	    if( copy( $file, $file_path ) ) {
	    	$attach_id = wp_insert_attachment( array(
	            'guid'           => $file_path, 
	            'post_mime_type' => $file_mime,
	            'post_title'     => $file_name,
	            'post_status'    => 'inherit',
	            'post_content' 	 => ''
	        ), $file_path, $parent_post_id );
	        require_once( ABSPATH . 'wp-admin/includes/image.php');
	        wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $file_path));
	        return $attach_id;
	    }
	    return new WP_Error('upload_error','Can\'t upload file: ' . $file);
	}

	private function import_image( $image ){
		echo "\n  - Importing image: " . $image;
		$posts = get_posts(array(
			'numberposts' => -1,
			'post_type' => 'attachment',
			'meta_key' => 'import_url',
			'meta_value' => $image
		));
		if(!empty($posts)){
			echo "\n  Image already imported"; 
			return $posts[0]->ID;
		} else {
			$file = $this->plugin->path . $image;
			$attach_id = $this->file_attachment($file);
			if(is_wp_error($attach_id)){
				echo "\n  Error: " . $attach_id->get_error_message();
			} else {
				update_post_meta($attach_id,'import_url',$image);
				return $attach_id;
			}
		}
		return false;
	}

	private function is_image( $text ){
		return preg_match("/^.*\.(jpg|jpeg|png|gif|svg)$/i", $text);
	}

	private function import_image_recursive( $arr_str ){
		if(is_array($arr_str)){
			if(array_keys($arr_str) !== range(0, count($arr_str) - 1)){
				foreach ($arr_str as $key => $value) {
					if(is_string($value)){
						if($this->is_image($value)){
							$image_id = $this->import_image($value);
							if(!empty($image_id)){
								$arr_str[$key . '_id'] = $image_id;
								$arr_str[$key] = wp_get_attachment_url($image_id);
							}
						}
					} else {
						$arr_str[$key] = $this->import_image_recursive($value);
					}
				}
			} else {
				for ($i=0; $i < count($arr_str); $i++) { 
					$arr_str[$i] = $this->import_image_recursive($arr_str[$i]);
				}
			}
		} elseif(is_string($arr_str) && $this->is_image($arr_str)){
			$image_id = $this->import_image($arr_str);
			if(!empty($image_id)){
				$arr_str = wp_get_attachment_url($image_id);
			}
		}
		return $arr_str;
	}

	private function import($index, $option = 'info'){
		$info = json_decode(file_get_contents($this->plugin->path ."data/". $option), true);
		if(!empty($info[$index])){
			$object = $this->normalize_object($info[$index]);
			switch($object['type']){
				case 'term':
					echo "\n- Creating Term: " . $object['title'];
					$term_id = false;
					$term = get_term_by('slug',$object['slug'],$object['taxonomy']);
					if(!empty($term) && !is_wp_error($term)){
						echo "\n  Term already imported";
						$term_id = $term->term_id;
					} else {
						$parent = 0;
						if(!empty($object['parent'])){
							$term = get_term_by('slug',$object['parent'],$object['taxonomy']);
							if(!empty($term) && !is_wp_error($term)){
								$parent = $term->term_id;
								echo "\n  Selecting parent: " . $term->name;
							}
						}
						$term = wp_insert_term($object['title'], $object['taxonomy'], array(
							'name' => $object['title'],
							'slug' => $object['slug'],
							'description' => isset($object['description']) ? $object['description'] : '' ,
							'parent' => $parent
						));
						if(is_wp_error($term)){
							echo "\n  " . $term->get_error_message();
						} else {
							$term_id = $term['term_id'];
						}
					}
					if(!empty($object['fields']) && !empty($term_id)){
						$object = $this->import_image_recursive($object);
						echo "\n  - Updating fields.";
						foreach ($object['fields'] as $key => $value) {
							update_term_meta($term_id,$key,$value);
						}
					}
				break;
				case 'post':
					echo "\n- Creating Post: " . $object['title'];
					$post_id = false;
					$args = array(
						'numberposts'	=> 1,
						'post_type'		=> $object['post-type']
					);
					if(empty($object['unique']) || $object['unique'] == 'slug'){
						$args['name'] = $object['slug'];
					} else {
						$args['meta_key'] = $object['unique'];
						$args['meta_value'] = $object['fields'][$object['unique']];
					}
					$post = get_posts($args);
					if(!empty($post)){
						echo "\n  Post already imported";
						$post_id = $post[0]->ID;
					} else {
						$args = array(
						  'post_title'    => wp_strip_all_tags( $object['title'] ),
						  'post_type'	  => $object['post-type'],
						  'post_status'   => 'publish',
						  'post_author'   => 1
						);
						if(!empty($object['slug'])){
							$args['post_name'] = $object['slug'];
						}
						if(!empty($object['content'])){
							$args['post_content'] = $object['content'];	
						} else {
							$args['post_content'] = $object['title'];
						}
						if(!empty($object['excerpt'])){
							$args['post_excerpt'] = $object['excerpt'];	
						} else {
							$args['post_excerpt'] = $args['post_content'];
						}
						$post_id = wp_insert_post($args);
						if(is_wp_error($post_id)){
							echo "\n  " . $post_id->get_error_message();
						}
					}
					if(!empty($post_id) && !is_wp_error($post_id)){
						
						$terms = $this->get_terms_ids($object['terms']);
						if(!empty($terms)){
							echo "\n  - Updating terms.";
							wp_set_post_terms( $post_id, $terms, $object['taxonomy'], false );
						}
						
						if(!empty($object['fields']) ){
							$object = $this->import_image_recursive($object);
							echo "\n  - Updating fields.";
							foreach ($object['fields'] as $key => $value) {
								if(substr($value, 0, 5) == 'eval:'){
									eval('$value = ' . substr($value, 5).';');
								}
								update_post_meta($post_id,$key,$value);
							}
							if(!empty($object['featured_id'])){
								echo "\n  - Updating post thumbnail.";
								set_post_thumbnail( $post_id, $object['featured_id'] );
							}
						}
					}
				break;
				case 'page':
					echo "\n- Creating Page: " . $object['title'];
					$page = get_page_by_path( $object['slug'] );
					$page_id = false;
					if(!empty($page) && !is_wp_error($page)){
						echo "\n  Page already imported";
						$page_id = $page->ID;
					} else {
						$page_id = wp_insert_post( array(
						  'post_title'    => $object['title'],
						  'post_name' 	  => $object['slug'],
						  'post_type'	  => 'page',
						  'post_status'   => 'publish',
						  'post_author'   => 1
						) );
						if(is_wp_error($page_id)){
							echo "\n  " . $page_id->get_error_message();
						}
					}
					if(!empty($page_id) && !is_wp_error($page_id)){
						if(!empty($object['post_content_file']) ){
							$object['post_content'] = file_get_contents($this->plugin->path . 'data/'. $object['post_content_file']);
						}
						if(!empty($object['post_content']) ){
							echo "\n  - Updating content.";
							wp_update_post(array(
								'ID' => $page_id,
								'post_content' => $object['post_content'],
							));
						}
						if(!empty($object['fields']) ){
							$object = $this->import_image_recursive($object);
							echo "\n  - Updating fields.";
							foreach ($object['fields'] as $key => $value) {
								if(substr($value, 0, 5) == 'eval:'){

									$value = eval(substr($value, 5));
								}
								update_post_meta($page_id,$key, $value);
							}
							if(!empty($object['image_id'])){
								echo "\n  - Updating post thumbnail.";
								set_post_thumbnail( $page_id, $object['image_id'] );
							}
						}
					}
				break;
				case 'update_fields':
					$update_by = $object["fields"][$object['update_by']];
					echo "\n- Searching for: " . $update_by;
					$post_id = false;
					$post = get_posts(array(
						'numberposts'	=> 1,
						'post_type'		=> $object['post-type'],
						'meta_key'		=> $object['update_by'],
						'meta_value'	=> $update_by
					));
					if(empty($post)){
						echo "\n  NOT FOUND!";
					} else {
						$post_id = $post[0]->ID;
						$object = $this->import_image_recursive($object);
						echo "\n  - Updating " . $post[0]->post_title;
						foreach ($object['fields'] as $key => $value) {
							update_post_meta($post_id,$key,$value);
						}
					}
				break;
			}
		} else {
			return 'Done';
		}
	}

	private function get_terms_ids( $_terms ){
		$__terms = array();
		foreach ($_terms as $taxonomy => $terms) {	
			for ($i=0; $i < count($terms); $i++) { 
				$term = get_term_by('slug',$terms[$i],$taxonomy);
				if($term instanceof WP_Term){
					$__terms[] = $term->term_id;
				}
			}
		}
		
		return $__terms;
	}

	private function normalize_object( $object ){
		if($object['type'] == 'post'){
			switch($object['post-type']){
				case 'product':
					
				break;
			}
		}
		return $object; 
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
			'icon_url'       	 => 'dashicons-download', // Menu icon. Only applicable if 'parent_slug' is left empty.
			'menu_title'      	 => esc_html__( 'Import', 'cimport' ), // Falls back to 'title' (above).
			// 'capability'      => 'manage_options', // Cap required to view options-page.
			//'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			'display_cb'      	 => array($this, 'display_cb'), // Override the options-page form output (CMB2_Hookup::options_page_output()).
			//'save_button'      => esc_html__( 'Import', 'cmb2' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

	}

	public function display_cb( $cmb_options ) {
		?>
		<div class="wrap cmb2-options-page option-cimport">
			<h2>Import</h2>
			<div id="console"></div>
			<p class="submit">
				<?php 
				$files  = scandir($this->plugin->path . 'data');
				foreach($files as $file){
					$p = pathinfo($file);
					if(isset($p['extension']) && $p['extension'] == 'json'){
						?><input type="radio" name="cmb-option" value="<?=$file?>"><?=$file?> <br /><br /><?php
					}
				}
				?>
				<input type="submit" id="submit-cmb" class="button button-primary" value="Start Import" data-ajax="<?=admin_url('admin-ajax.php?action=' . self::$key);?>">
			</p>
		</div>
		<?php
	}

	public function admin_enqueue_scripts() {
		if(!empty($_GET['page']) && $_GET['page'] == self::$key){
			wp_enqueue_script( 'c-import', $this->plugin->url . 'assets/js/import.js', array('jquery'), '1.0.0', true );
		}
	}

	public function wp_ajax(){
		echo $this->import(!empty($_GET['index']) ? (int) $_GET['index'] : 0,!empty($_GET['option']) ? $_GET['option'] : 'info');
		wp_die();
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
