<?php
/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
  'lib/assets.php',    // Scripts and stylesheets
  'lib/extras.php',    // Custom functions
  'lib/setup.php',     // Theme setup
  'lib/titles.php',    // Page titles
  'lib/wrapper.php',   // Theme wrapper class
  'lib/customizer.php' // Theme customizer
];

foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);

$dir_uri = '';
function _asset( $file = '' ){
  global $dir_uri;
  if(empty($dir_uri)){
    $dir_uri = get_template_directory_uri();
  }
  return $dir_uri . '/dist/' . $file;
}

function _image( $file = '' ){
  return _asset('images/' . $file);
}

$dir_path = '';
function _asset_path( $file = '' ){
  global $dir_path;
  if(empty($dir_path)){
    $dir_path = get_template_directory();
  }
  return $dir_path . '/dist/' . $file;
}

function _image_path( $file = '' ){
  return _asset_path('images/' . $file);
}

function get_page_link_by_template( $template ){
  $pages = get_posts(array(
    'post_type' =>'page',
    'meta_key'  =>'_wp_page_template',
    'meta_value'=> $template
  ));
  $url = '';
  if(isset($pages[0])) {
    $url = get_page_link($pages[0]->ID);
  }
  return $url;
}

function _svg( $file = '' ){
  include(_image_path($file));
}

add_action('after_setup_theme', function(){
  add_image_size( 'product-box', 348, 278, true );
  add_image_size( 'product-cart', 100, 100, true );
});

function print_card( $post ){
  $terms = get_the_terms($post,'product-category');
  $color = '';
  if(!is_wp_error($terms)){
    foreach ($terms as $term) {
      if( $term->parent == 0 ) {
         $color = get_term_meta($term->term_id,'color',true);
         break;
      }
    }
  }
  $price = (float) get_post_meta($post->ID, 'price', true); ?>
  <div class="mf-card mf-card--<?=$color?>">
    <a href="<?=get_permalink($post)?>">
      <img src="<?=get_the_post_thumbnail_url($post,'product-box')?>" class="mf-card__image" />
      <div class="mf-card__title"><?=$post->post_title?></div>
      <div class="mf-card__price"><!-- del>$3,500</del --> <span>precio</span> <strong>$<?=number_format($price)?></strong></div>
      <div class="mf-card__stars mf-stars" data-rank="<?=rand(7,10)/2?>">
        <i></i><i></i><i></i><i></i><i></i>
      </div>
    </a>
  </div><?php
}


/****************/
