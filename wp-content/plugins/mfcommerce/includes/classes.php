<?php 

Class MFC_Payment{

	protected $contact;
	protected $contact_allow;
	protected $shipping;
	protected $shipping_allow;
	protected $data;
	protected $data_allow;
	protected $items;
	protected $details;

	public function __construct(){

		$this->shipping = array(); 
		$this->shipping_allow = array('name','last_name','street','ext_number','int_number','phone','postal_code','state','city','municipality','neighborhood','directions');

		$this->contact = array(); 
		$this->contact_allow = array('name','email');

		$this->data = array(); 
		$this->data_allow = array('coupon','payment_method','payment_method_card','transaction_amount','installments','token','card_number', 'card_securityCode','card_month','card_year');

		$this->items = array();
		$this->details = array(); 
	}

	public function __set( $key, $val ){
		if(is_array($val)){
			if($key == 'items'){
				$this->items = new MFC_Order_Items($val);
			} elseif (in_array($key, array('shipping','data','contact'))){
				foreach ($val as $k => $value) {
					$this->{$key . '_' . $k} = $value;
				}
			}
		} else {
			$option = $this->get_option($key);
			if($option){
				extract($option, EXTR_OVERWRITE);
				if(in_array($key, $this->{$option.'_allow'})){
		    		$this->{$option}[$key] = $val;
		    	}
			}
		}
	}

	public function __get( $key ){
		if(in_array($key, array('shipping','data','contact','items','details'))){
			return $this->{$key};
		}
		$option = $this->get_option($key);
		if($option){
			extract($option, EXTR_OVERWRITE);
			if(in_array($key, $this->{$option.'_allow'})){
	    		return $this->{$option}[$key];
	    	}
		}
	}

	private function get_option( $key ){
		for ($i=0,$options = array('shipping','contact','data'); $i < count($options); $i++) { 
			$len = strlen($options[$i]);
			if(substr( $key, 0, $len + 1 ) == $options[$i] . "_"){
				return array(
					'option' => $options[$i],
					'key' => substr( $key, $len + 1 )
				);
			}
		}
		return false;
	}

	public function calculate(){
		$bill = $this->items->get_total();
		$discounted = $this->items->get_discounted($this->data_coupon);
		if($discounted == 0){
			$this->data['coupon'] = '';
		}
		$shipping = (float) apply_filters('mfc_payment_calculate_shipping',0,$this->items,$this->shipping,$this->data_coupon);
		$this->details = array(
			'bill' => $bill,
			'discounted' => $discounted,
			'shipping' => $shipping,
			'total' => $bill - $discounted + $shipping,
		);
	}

	public function get_resumen(){
		$text = '';
		foreach($this->products as $product){
			$count = isset($product['count']) ? $product['count'] : 1;
			$text .= "\n- " . $product['title'] . ' ' . $product['description'] . ' (' . $count . ') $' . number_format($product['price'] * $count,2,'.',',');
		}
		return $text;
	}

	public function to_array() {
		return array(
			'contact' => $this->contact,
			'shipping' => $this->shipping,
			'data' => $this->data,
			'items' => $this->items->to_array(),
			'details' => $this->details
		);
    }
}

class MFC_Product{

	protected $data;
	protected $post;
	protected $cast = array(
		'price' => 'float',
		'stock' => 'int'
	);

	protected static $fields = array(
		'sku',
		'stock',
		'price',
		'seller',
		'images'
	);

	public function __construct( $post, $fields = array() ){
		$this->post = $post;
		$this->data = array();
		$this->populate_fields($fields === true ? self::$fields : $fields);
	}

	public function __get( $key ) {
		switch($key){
			case 'id':
				return $this->post->ID;
			break;
			case 'title':
				return $this->post->post_title;
			break;
			case 'label':
				return $this->post->post_excerpt;
			break;
			default:
				$this->populate_field($key);
				return isset($this->data[$key]) ? $this->data[$key] : '';
			break;
		}
		return '';
	}

	public function __isset( $key ) {
		switch($key){
			case 'id':
				return isset($this->post->ID);
			break;
			case 'title':
				return isset($this->post->post_title);
			break;
			case 'label':
				return isset($this->post->post_excerpt);
			break;
			default:
				$this->populate_field($key);
				return isset($this->data[$key]) ? $this->data[$key] : '';
			break;
		}
		return '';
	}

	public function populate_field( $field ){
		if(!isset($this->data[$field])){
			$val = get_post_meta($this->id,$field,true);
			if(isset($this->cast[$field])){
				switch ($this->cast[$field]) {
					case 'float':
						$val = (float) $val;
					break;
					case 'int':
						$val = (int) $val;
					break;
				}
			}
			$this->data[$field] = $val;
		}
	}

	public function populate_fields( $fields ){
		for ($i=0; $i < count($fields); $i++) { 
			$this->populate_field($fields[$i]);
		}
	}

	public static function get_fields(){
		return self::$fields;
	}

	public function to_array() {
		return array_merge(array(
			'id' => $this->id,
			'title' => $this->title,
			'label' => $this->label,
		),$this->data);
    }
}

class MFC_Order_Item{

	protected $product;
	protected $quantity;
	protected $order;

	public function __construct( MFC_Product $product, $quantity = 1, MFC_Order $order = NULL ){
		$this->product = $product;
		$this->quantity = $quantity;
		$this->order = $order;
	}

	public function __get( $key ){
		switch ($key) {
			case 'product': case 'quantity':
				return $this->{$key};
			break;
			default:
				return $this->product->{$key};
		}
	}

	public function get_discount( $coupon ){
		return apply_filters('mfc_payment_get_product_discount',0,$this->product,$coupon);
	}

	public function get_discounted( $coupon ){
		return $this->product->price * $this->get_discount($coupon) * $this->quantity;
	}

	public function to_array() {
		return array(
			'id' => $this->product->id,
			'quantity' => $this->quantity,
			//'product' => $this->product->to_array()
		);
    }

    public function set_order( MFC_Order $order ){
    	$this->order = $order;
    } 

    public function save(){
    	if(!empty($this->order) && !empty($this->order->id) && !empty($this->product->id)){
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . "mfc_order_items", array(
				'order_id' => $this->order->id,
				'product_id' => $this->product->id,
				'quantity' => $this->quantity
			) );
		}
    }
}

class MFC_Order_Items{

	protected $items;
	protected $order;

	public function __construct( $items, MFC_Order $order = NULL ){
		$this->items = array();
		$this->order = $order;

		$items = MFC_Utils::array_pluck_assoc($items,'id','quantity',1);

		$posts = get_posts(array(
			'numberposts' 	=> -1,
			'post_type' 	=> MFC_Product_CPT::$slug,
			'include' 		=> array_keys($items)
		));
		for ($i = 0; $i < count($posts); $i++) { 
			$this->items[] = new MFC_Order_Item(new MFC_Product($posts[$i]),$items[$posts[$i]->ID],$this->order);
		}
	}

	public function get_total(){
		$total = 0;
		for ($i=0; $i < count($this->items); $i++) { 
			$total += $this->items[$i]->price * $this->items[$i]->quantity;
		}
		return $total;
	}

	public function get_discounted( $coupon ){
		$total = 0;
		for ($i=0; $i < count($this->items); $i++) { 
			$total += $this->items[$i]->get_discounted($coupon);
		}
		return $total;
	}

	public function to_array() {
		$_items = array();
		for ($i=0; $i < count($this->items); $i++) { 
			$_items[] = $this->items[$i]->to_array();
		}
		return $_items;
    }

    public function set_order( MFC_Order $order ){
    	$this->order = $order;
    	for ($i=0; $i < count($this->items); $i++) { 
			$this->items[$i]->set_order($this->order);
		}
    }

    public function save(){
		for ($i=0; $i < count($this->items); $i++) { 
			$this->items[$i]->save();
		}
	}
}

class MFC_Order extends MFC_Model{

	protected $items;

	public function __construct( $data = array(), MFC_Order_Items $items = NULL ) {
		parent::__construct($data, array(
			'id' => 'int',
			'uniqid' => 'string',
			'transaction_amount' => 'float',
			'shipping' => 'array',
			'contact' => 'array',
			'data' => 'array',
			'details' => 'array',
			'state' => 'string',
			'coupon' => 'string',
			'status' => 'string',
			'created_at' => 'datetime',
			'updated_at' => 'datetime'
		));
		if($items){
			$this->items = $items;
			$this->items->set_order($this);
		}
	}

	public function __get( $key ){
		switch ($key) {
			case 'items':
				if(!isset($this->items)){
					$this->items = new MFC_Order_Items($this->get_order_items(),$this);
				}
				return $this->items;
			break;
			case 'created_at_str':
				if(isset($this->created_at)){
					return $this->created_at->format('Y-m-d H:i:s');
				}
				return '';
			break;
			case 'uniqid':
				if(empty($this->data['uniqid'])){
					$this->data['uniqid'] = uniqid();
				}
				return $this->data['uniqid'];
			break;
		}
		return parent::__get($key);
	}

	private function get_order_items(){
		if(isset($this->id)){
			$query = "SELECT product_id as id, quantity FROM " . $wpdb->prefix . "mfc_order_items WHERE order_id = %d";
			return $wpdb->get_results( $wpdb->prepare( $query, $this->id ), 'ARRAY_A' );
		}
		return array();
	}

	protected function get_insert_or_update_args(){
		$args = parent::get_insert_or_update_args();
		unset($args['created_at']);
		unset($args['updated_at']);
		unset($args['id']);
		if(!isset($this->id)){
			unset($args['status']);
		}
		return $args;
	}

	public function save(){
		$this->insert_or_update('mfc_orders');
		$this->items->save();
	}
	
}

class MFC_Model {
	protected $data;
	protected $fields;

	public function __construct( $data = array(), $fields = array() ) {
		$this->data = empty($data) ? array() : (array) $data;
		$this->fields = $fields;
		foreach ($this->data as $key => $value) {
			if(isset($this->fields[$key])){
				switch ($this->fields[$key]) {
					case 'float':
						$this->data[$key] = (float) $value;
					break;
					case 'int':
						$this->data[$key] = (int) $value;
					break;
					case 'string':
						$this->data[$key] = (string) $value;
					break;
					case 'array':
						if(!is_array($value)){
							$this->data[$key] = json_decode($value, true);
						}
					break;
					case 'datetime':
						if(!$value instanceof DateTime){
							$this->data[$key] = new DateTime($value);
						}
					break;
				}
			}
		}
	}

	public function __get( $key ) {
		return isset($this->data[$key]) ? $this->data[$key] : '';
	}

	public function __isset( $key ) {
		return isset($this->data[$key]);
	}

	public function to_array() {
		return $this->data;
    }

    protected function get_insert_or_update_args(){
    	$args = array();
    	foreach ($this->fields as $key => $cast) {
    		if(isset($this->data[$key])){
    			switch ($cast) {
    				case 'array':
						if(is_array($this->data[$key])){
							$args[$key] = json_encode($this->data[$key],JSON_HEX_QUOT);
						}
					break;
					case 'datetime':
						if($value instanceof DateTime){
							$args[$key] = $this->data[$key]->format('Y-m-d H:i:s');
						}
					break;
					default:
						$args[$key] = (string) $this->{$key};
					break;
    			}
    		} else {
    			$args[$key] = (string) $this->{$key};
    		}
    	}
    	return $args;
    }

    public function insert_or_update( $table ){
    	$args = $this->get_insert_or_update_args();
    	if(isset($this->id)){
    		self::update($table,$args);
    	} else {
    		$this->id = self::insert($table,$args);
    	}
    }

    public static function get_row( $table, $value, $field = 'id' ){
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . $table . " WHERE $field = %d";
		return $wpdb->get_row( $wpdb->prepare( $query, $value ), 'ARRAY_A' );
	}

	public static function get_results( $table, $fields = array(), $orderby = 'updated_at', $order = 'DESC', $search = '', $search_fields = array(), $search_fields_relation = 'OR', $paged = false, $post_per_page = 10, $count = false ){
		global $wpdb;
		$query = "SELECT ". ($count ? 'COUNT(*)' : '*') ." FROM " . $wpdb->prefix . $table;
		$where = array();
		foreach ($fields as $key => $value) {
			if($value != 'all'){
				$where[] = $wpdb->_real_escape($key) . " = '" . $wpdb->_real_escape($value) . "'";
			}
		}
		if(!empty($search)){
			$aux = array();
			$search = $wpdb->_real_escape($search);
			for ($i=0; $i < count($search_fields); $i++) { 
				$aux[] = $search_fields[$i] . " LIKE '%$search%'";
			}
			$where[] = "( ". implode($search_fields_relation, $aux) ." )";
		}
		if(!empty($where)){
			$query .= " WHERE " . implode(' AND ', $where);
		}
		if(!$count){
			$query .= " ORDER BY ". $wpdb->_real_escape($orderby) . " ". $wpdb->_real_escape($order);
			if($paged !== false ){
				$query .= " LIMIT ". (($paged - 1) * $post_per_page) . "," . $wpdb->_real_escape($post_per_page);
			}
			return $wpdb->get_results( $query, 'ARRAY_A' );
		} else {
			return (int) $wpdb->get_var( $query );
		}
	}

	public static function get_results_count( $table, $fields = array(), $search = '', $search_fields = array(), $search_fields_relation = 'OR' ){
		return self::get_results( $table, $fields, 'updated_at', 'DESC', $search, $search_fields, $search_fields_relation, false, 10, true );
	}

	public static function get_count( $table ){
		global $wpdb;
		$query = "SELECT COUNT(*) FROM " . $wpdb->prefix . $table;
		return $wpdb->get_var( $query );
	}

	public static function insert( $table, $args ){
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . $table, $args );
		return $wpdb->insert_id;
	}

	public static function update( $table, $id, $args ){
		global $wpdb;
		$wpdb->update( $wpdb->prefix . $table, $args, array('id' => $id));
	}

}

class MFC_Utils{

	public static function call_api( $api_url, $parameters = array(), $headers = array(), $method = 'GET', $is_file = false ){
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

	public static function array_pluck_assoc($array, $key, $value_key = false, $default = true) {
	    $plucked = array();
	    foreach($array as $v){
	    	$k = is_object($v) ? $v->$key : $v[$key];
	    	if($value_key === false){
	    	    $plucked[] = $k;
	    	} elseif(!isset($plucked[$k])) {
	    		if(is_object($v)){
	    			$plucked[$k] = isset($v->{$value_key}) ? $v->{$value_key} : $default;
	    		} else {
	    			$plucked[$k] = isset($v[$value_key]) ? $v[$value_key] : $default;
	    		}
	    	}
	    }
	    return $plucked;
	}

	/*----------------------------------------------------------------------------------------------------
		REST API Functions
	----------------------------------------------------------------------------------------------------*/

	public static function api_success( $data = array() ){
	    $data['success'] = true;
	    return $data;
	}

	public static function api_error( $message, $status, $code = 'error', $params = array() ){
	    return new WP_Error( $code, $message, array( 'status' => $status, 'params' => $params ) );
	}

	public static function api_error_bad_request( $params ){
	    return self::api_error( sprintf(__( 'Missing parameter(s): %s', 'mfc-firstdatagateway' ),implode(', ', array_values($params))), 400, 'rest_missing_callback_param', array_keys($params));
	}

	public static function api_error_unprocessable_entity( $message, $params = array() ){
	    return self::api_error( $message, 422, 'unprocessable_entity', $params );
	}

	public static function api_error_internal_server_error(){
	    return self::api_error(__( 'An error occurred, please try again later.', 'mfc-firstdatagateway' ), 500, 'internal_server_error');
	}

	public static function middleware_auth( $role = false ){
	    if(is_user_logged_in()){
	        if(!empty($role)){
	            $user = wp_get_current_user();
	            if(!empty(array_intersect(is_array($role) ? $role : array($role), $user->roles))){
	                return true;
	            }
	        } else return true;
	    }
	    return new WP_Error( 'authentication_failed', __( 'Not authorized.', 'mfc-firstdatagateway' ), array( 'status' => 401 ) );
	}

	public static function middleware_get_post( $post_id, $data = array() ){
	    $post = get_post( $post_id );
	    if(!empty($post) && !is_wp_error($post)){
	        if(self::valid_post_data($post, $data)){
	            return $post;
	        }
	    }
	    return new WP_Error( 'not_found', __( 'Not Found.', 'mfc-firstdatagateway' ), array( 'status' => 404 ) );
	}

	private static function valid_post_data( $post, $data ){
	    $valid = true;
	    foreach ($data as $key => $value) {
	        switch ($key) {
	            case 'post_type':
	            case 'post_author':
	                $_value = $post->{$key};
	            break;
	            default:
	                $_value = get_post_meta($post->ID, $key, true);
	        }
	        if(is_array($value)){
	            for ($i=0; $i < count($value); $i++) { 
	                if($valid = $_value == $value[$i]) break;
	            }
	        } else {
	            $valid = $_value == $value;
	        }
	        if(!$valid){
	            break;
	        }
	    }
	    return $valid;
	}

	public static function api_validation_rule( $required, $validation = '' ){
	    return array(
	        'required' => $required,
	        'validate_callback' => function($param, $request, $key) use ($required, $validation) {
	            if(empty($param)) return !$required;
	            switch ($validation) {
	                case 'email';
	                    return filter_var($param, FILTER_VALIDATE_EMAIL);
	                break;
	                case 'phone':
	                    return is_numeric( $param ) && strlen( $param ) >= 8 && strlen( $param ) <= 10;
	                break;
	                case 'Y-m-d':
	                case 'H:i:s':
	                case 'Y-m-d H:i:s':
	                    $d = DateTime::createFromFormat($validation, $param);
	                    return $d && $d->format($validation) == $param;
	                break;
	                case 'password':
	                    return strlen($param) >= 8;
	                break;
	            }
	            return true;
	        }
	    );
	}

	public static function print_csv( $items, $headers ){
		for ($i=0; $i < count($headers); $i++){
			echo '"' . $headers[$i]['label'] . '"' . ($i < count($headers) - 1 ? ',' : '');
		}
		echo "\r\n";
		for ($j=0; $j < count($items); $j++){
			for ($i=0; $i < count($headers); $i++){
				echo '"' . $items[$j]->{empty($headers[$i]['field'])?$headers[$i]['id']:$headers[$i]['field']} . '"' . ($i < count($headers) - 1 ? ',' : '');
			}
			echo "\r\n";
		}
	}

	public static function print_table( $page, $title, $items, $headers, $tabs = array(), $actions = array(), $filters = array(), $total_items = 0, $items_per_page = 10, $search = true, $add_btn = true, $export_btn = true ){
		$url_admin = admin_url('admin.php');
		$url_admin_page = $url_admin . '?page=' . $page;

		$hidden_page = '<input type="hidden" name="page" value="'.$page.'" />';
		
		$current_tab = '';
		$hidden_tab = '';
		if(!empty($tabs)){
		 	$current_tab = empty($_GET['tab']) ? $tabs[0]['id'] : $_GET['tab'];
		 	$hidden_tab = '<input type="hidden" name="tab" value="'.$current_tab.'" />';
		}

		$url_filters = '';
		$hidden_filters = '';
		if(!empty($filters)){
			for ($i=0; $i < count($filters); $i++) { 
				if(isset($_GET[$filters[$i]['id']])){
					$url_filters .= '&' . $filters[$i]['id'] . '=' . $_GET[$filters[$i]['id']];
					$hidden_filters .= '<input type="hidden" name="'.$filters[$i]['id'].'" value="'.$_GET[$filters[$i]['id']].'" />';
				}
			}
		}
		$url_search = '';
		$hidden_search = '';
		if(!empty($_GET['s'])){
			$url_search .= '&s=' . $_GET['s'];
			$hidden_search .= '<input type="hidden" name="s" value="'.$_GET['s'].'" />';
		}


		$column_dic = array(
			'primary' => 'column-title column-primary',
			'large' => 'column-categories',
			'small' => 'column-tags',
			'date' => 'column-date'
		);//author

		$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
		$current_order = isset($_GET['order']) ? ($_GET['order'] == 'asc' ? 'asc' : 'desc') : '';
		$order = $current_order == 'asc' ? 'desc' : 'asc';

		$_actions = array(); 
		for ($i=0; $i < count($actions); $i++) { 
			if( (isset($actions[$i]['tab']) && (is_array($actions[$i]['tab']) ? in_array($current_tab, $actions[$i]['tab']) : $actions[$i]['tab'] == $current_tab)) || !isset($actions[$i]['tab']) ){
				$_actions[] = $actions[$i];
			}
		}
		$actions = $_actions;
		$actions_html = '';

		$has_bulk = false; 
		for($i = 0; $i < count($actions); $i++){
			if(!empty($actions[$i]['bulk'])){
				$has_bulk = true;
				break;
			}
		}

		$primary_action = false;
		for ($i=0; $i < count($actions); $i++) { 
			if(!empty($actions[$i]['primary'])){
				$primary_action = $actions[$i];
				break;
			}
		}

		$total_pages = ceil($total_items / $items_per_page);
		$current_page = empty($_GET['paged']) ? 1 : (int) $_GET['paged'];
		if( $current_page > $total_pages ){
			$current_page = max(1,$total_pages);
		}

		$paged_url = $url_admin . '?';
		$order_url = $url_admin . '?';
		foreach ($_GET as $key => $value) {
			if($key != 'paged'){
			 	$paged_url .= $key .'='.$value.'&';
			 	if($key != 'order' && $key != 'orderby'){
			 		$order_url .= $key .'='.$value.'&';
			 	}
			 }
		}
		$paged_url .= 'paged=';

		?>
		<div class="wrap">
		<h1 class="wp-heading-inline"><?=$title?></h1>
		<?php if($add_btn): ?>
		<a href="<?=$url_admin_page?>&action=add" class="page-title-action">+ Agregar</a>
		<?php endif; ?>
		<hr class="wp-header-end">
		<?php if(!empty($tabs)): ?>
		<ul class="subsubsub">
			<?php for ($i=0; $i < count($tabs); $i++): ?>
			<li>
				<a href="<?=$url_admin_page.$url_filters.$url_search?>&tab=<?=$tabs[$i]['id']?>" <?=$tabs[$i]['id'] == $current_tab ? 'class="current" aria-current="page"':''?>>
					<?=$tabs[$i]['label']?> 
					<?php if(isset($tabs[$i]['count'])): ?>
					<span class="count">(<?=$tabs[$i]['count']?>)</span>
					<?php endif; ?>
				</a> <?php echo $i < count($tabs) -1 ? '|' : ''; ?>
			</li>
			<?php endfor; ?>
		</ul>
		<?php endif; ?>
		<?php if($search): ?>
		<form action="<?=$url_admin?>" method="GET">
			<p class="search-box">
				<?=$hidden_page . $hidden_tab . $hidden_filters?>
				<label class="screen-reader-text" for="post-search-input">Buscar:</label>
				<input type="search" id="post-search-input" name="s" value="<?=empty($_GET['s'])?'':$_GET['s']?>">
				<input type="submit" id="search-submit" class="button" value="Buscar">
				<?php if(!empty($_GET['s'])): ?>
				<a href="<?=$url_admin_page.$url_filters?>" class="button" style=""><span class="dashicons dashicons-no-alt" style="vertical-align: sub;"></span></a>
				<?php endif; ?>
			</p>
		</form>
		<?php endif; ?>
		<?php if(!empty($actions) || !empty($filters)): ?>
		<div class="tablenav top">
		    <?php if(!empty($actions) && $has_bulk): ob_start(); ?>
		    <div class="alignleft actions">
		    	<form action="<?=$url_admin?>" method="GET" data-actions='<?=json_encode($actions)?>'>
					<?=$hidden_page . $hidden_tab?>
		    		<input type="hidden" class="check-ids" name="id" value="" />
		    		<label class="screen-reader-text" for="action">Acciones en lote</label>
			        <select name="action" class="postform">
						<option value="none">Acciones en lote</option>
						<?php for($i = 0; $i < count($actions); $i++): if(!empty($actions[$i]['bulk'])): ?>
							<option value="<?=$actions[$i]['id']?>"><?=$actions[$i]['label']?></option>
						<?php endif; endfor; ?>
					</select>    
			        <input type="submit" class="button" value="Aplicar" />
		        </form>
		    </div>
			<?php $actions_html = ob_get_flush(); endif; ?>
			<?php if(!empty($filters)): ?>
			<div class="alignleft actions">
		    	<form action="<?=$url_admin?>" method="GET">
					<?=$hidden_page . $hidden_tab . $hidden_search?>
					<?php for ($i=0; $i < count($filters); $i++): 
						if((isset($filters[$i]['tab']) && (is_array($filters[$i]['tab']) ? in_array($current_tab, $filters[$i]['tab']) : $filters[$i]['tab'] == $current_tab)) || !isset($filters[$i]['tab']) ): 
							$selected_filter = !empty($_GET[$filters[$i]['id']]) ? $_GET[$filters[$i]['id']] : ''; ?>
		    		<label class="screen-reader-text" for="<?=$filters[$i]['id']?>"><?=$filters[$i]['label']?></label>
			        <select name="<?=$filters[$i]['id']?>" class="postform">
						<?php foreach($filters[$i]['options'] as $key => $label): ?>
							<option value="<?=$key?>" <?= $key == $selected_filter ? 'selected="selected"' : '' ?> ><?=$label?></option>
						<?php 
						 endforeach; ?>
					</select>
					<?php endif;
						endfor; ?>
			        <input type="submit" class="button" value="Filtrar" />
		        </form>
		    </div>
			<?php endif; ?>
		    <br class="clear">
		</div>
		<?php endif; ?>

		<?php ob_start(); ?>
		<?php if($has_bulk): ?>
		<td id="cb" class="manage-column column-cb check-column">
	        <label class="screen-reader-text" for="cb-select-all-1">Seleccionar todos</label>
	        <input class="cb-select-all" id="cb-select-all-1" type="checkbox">
	    </td>
		<?php endif; ?>
		<?php for ($i=0; $i < count($headers); $i++): $sortable = !empty($headers[$i]['sortable']); ?> 
			<th scope="col" id="<?=$headers[$i]['id']?>" class="manage-column <?=isset($column_dic[$headers[$i]['column']])?$column_dic[$headers[$i]['column']]:$headers[$i]['column']?> <?=$sortable?'sortable ' . ( $orderby == $headers[$i]['id'] ? $order : 'asc' ):''?>">
				<?php if($sortable): ?>
				<a href="<?=$order_url?>orderby=<?=$headers[$i]['id']?>&order=<?=$orderby == $headers[$i]['id'] ? $order : 'asc'?>"><span>
				<?php endif; ?>
				<?=$headers[$i]['label']?>
				<?php if($sortable): ?>
				</span><span class="sorting-indicator"></span></a>
				<?php endif; ?>
			</th>
		<?php endfor; 
			$headers_html = ob_get_clean();
		?>
		
		<table class="wp-list-table widefat fixed striped posts">
			<thead><tr><?= $headers_html ?></tr></thead>
			<tbody id="the-list">
				<?php for ($i = 0; $i < count($items); $i++): $item_id = $items[$i]->id; ?>
				<tr>
					<?php if($has_bulk): ?>
					<th scope="row" class="check-column">
						<label class="screen-reader-text" for="cb-select-<?=$item_id?>">Elegir</label>
						<input id="cb-select-<?=$item_id?>" type="checkbox" name="items[]" value="<?=$item_id?>">
					</th>
					<?php endif; ?>
					<?php for($j = 0; $j < count($headers); $j++): $has_actions = $headers[$j]['column'] == 'primary' && !empty($actions); $value = $items[$i]->{!empty($headers[$j]['field']) ? $headers[$j]['field'] : $headers[$j]['id']}; ?>
					
					<td class="<?=isset($column_dic[$headers[$j]['column']])?$column_dic[$headers[$j]['column']]:$headers[$j]['column']?> <?=$has_actions?'has-row-actions':''?>" data-colname="<?=$headers[$j]['label']?>">
						
						<?php if($has_actions && !empty($primary_action) && $headers[$j]['column'] == 'primary'): ?>
						<strong class="row-title">
							<a href="<?=$url_admin_page?>&action=<?=$primary_action['id']?>&id=<?=$item_id?>" data-list-action="<?=$primary_action['id']?>" data-list-id="<?=$item_id?>" data-list-title="<?=$value?>">
						<?php endif; ?>
						
						<?php if($headers[$j]['column'] == 'date'): ?>
						<abbr title="<?=$items[$i]->{$headers[$j]['title']}?>">
						<?php endif; ?>
						<?=$value?>
						<?php if($headers[$j]['column'] == 'date'): ?>
						</abbr>
						<?php endif; ?>

						<?php if($has_actions && !empty($primary_action) && $headers[$j]['column'] == 'primary'): ?>
							</a>
						</strong>
						<div class="row-actions">
							<?php for($k = 0, $l = count($actions); $k < $l; $k++): $confirm = !empty($actions[$k]['confirm']); ?>
							<span class="<?=$confirm?'delete':''?>">
								<a href="<?=$url_admin_page?>&action=<?=$actions[$k]['id']?>&id=<?=$item_id?>" aria-label="<?=$actions[$k]['label']?>" <?php if($confirm): ?>class="submitdelete" onclick="return confirm('¿Está seguro que desea <?=strtolower($actions[$k]['label'])?> este elemento?')"<?php endif; ?> data-list-action="<?=$actions[$k]['id']?>" data-list-id="<?=$item_id?>" data-list-title="<?=$value?>">
								<?=$actions[$k]['label']?>
								</a>
								<?php if( $k < $l - 1 ): ?> | <?php endif; ?>
							</span>
							<?php endfor; ?>
						</div>
						<?php endif; ?>
						<button type="button" class="toggle-row"><span class="screen-reader-text">Muestra más detalles</span></button>
					</td>						
					<?php endfor; ?>
				</tr>		
				<?php endfor; ?>				
			</tbody>
			<tfoot><tr><?= $headers_html ?></tr></tfoot>
		</table>
		<div class="tablenav bottom">
			<?=$actions_html?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?=$total_items?> elemento<?=$total_items != 1?'s':''?></span>
				<?php if($total_pages > 1): ?>
				<span class="pagination-links">
					<?php if($current_page > 1): ?>
					<a class="first-page button" href="<?=$paged_url . '1'?>">
						<span class="screen-reader-text">Primera página</span>
						<span aria-hidden="true">«</span>
					</a>
					<?php else: ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
					<?php endif; ?>
					
					<?php if($current_page > 1): ?>
					<a class="prev-page button" href="<?=$paged_url . ($current_page - 1)?>">
						<span class="screen-reader-text">Página anterior</span>
						<span aria-hidden="true">‹</span>
					</a>
					<?php else: ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
					<?php endif; ?>
					
					<span class="screen-reader-text">Página actual</span>
					<span id="table-paging" class="paging-input">
						<span class="tablenav-paging-text">
							<?=$current_page?> de <span class="total-pages"><?=$total_pages?></span>
						</span>
					</span>
					
					<?php if($current_page < $total_pages): ?>
					<a class="next-page button" href="<?=$paged_url . ($current_page + 1)?>">
						<span class="screen-reader-text">Página siguiente</span>
						<span aria-hidden="true">›</span>
					</a>
					<?php else: ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
					<?php endif; ?>

					<?php if($current_page < $total_pages): ?>
					<a class="last-page button" href="<?=$paged_url . $total_pages?>">
						<span class="screen-reader-text">Última página</span>
						<span aria-hidden="true">»</span>
					</a>
					<?php else: ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
					<?php endif; ?>
				</span>
				<?php endif; ?>
			</div>
			<?php if($export_btn): ?>
				<a href="<?=$url_admin_page.$url_filters.$url_search?>&tab=<?=$current_tab?>&order=<?=$current_order?>&orderby=<?=$orderby?>&action=export" class="button" style="float: right; margin: 3px 20px 9px 0;"><span class="dashicons dashicons-download" style="vertical-align: sub;"></span> Exportar</a>
			<?php endif; ?>	
			<br class="clear">
		</div>
		<?php
	}
}
