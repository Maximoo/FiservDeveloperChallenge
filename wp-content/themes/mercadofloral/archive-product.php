<?php $categories = get_product_categories(true); ?>
<!-- PRODUCTS BROWSER -->
  <div class="categoryBrowser">
    <h1>productos</h1>
    <div>
      <div>
        <button class="paneButton" data-state="off">
          <i class="far fa-list-alt"></i>
          categorías <span>+</span>
        </button>
        <div>
        	<?php $on = true; foreach(group_product_categories($categories) as $title => $grouped): ?>
          	<h3 data-state="<?=$on?'on':'off'?>"><?=$title?><span>-</span></h3>
			<div>
			<?php for($i = 0; $i < count($grouped); $i++): ?>
				<a href="<?=get_term_link($grouped[$i])?>"><?=$grouped[$i]->name?></a>
			<?php endfor; ?>
			</div>
			<?php $on = false; endforeach; ?>
			<button class="closePane">
				<i class="fas fa-times"></i>
			</button>
        </div>
      </div>
      <div>
        <?php print_breadcrumbs( array( array( 'label' => 'Productos' ) ) ); ?>
        <?php 
        for($i = 0; $i < count($categories); $i++):
          if($categories[$i] instanceof WP_Term): ?>
        <a class="pcategory" href="<?=get_term_link($categories[$i])?>">
          <img src="<?=get_term_meta($categories[$i]->term_id,'image',true)?>" alt="<?=$categories[$i]->name?>" />
          <?=$categories[$i]->description?>
        </a>
        <?php endif; endfor; ?>
      </div>
    </div>
  </div>
  <!-- PRODUCTS BROWSER END -->