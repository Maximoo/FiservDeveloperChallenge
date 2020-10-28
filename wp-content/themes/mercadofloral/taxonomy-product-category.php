<?php 

	$_category = get_queried_object();
  $top_category = $_category->parent == 0 ? $_category : get_term_by('id',$_category->parent,'product-category');
  $color = get_term_meta($top_category->term_id,'color',true);
?>

<div class="container py-5">
  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="mf-card mf-card--menu">
        <div class="mf-card__menu mf-card__menu--<?=$color?>">
          <a href="<?=get_term_link($top_category->term_id)?>" class="mf-card__menu__title">
            <?=$top_category->name?>
          </a>
          <div class="mf-card__menu__items">
            <nav>
              <ul>
                <?php 
                  $_terms = get_terms( array( 'taxonomy' => 'product-category', 'parent' => $top_category->term_id, 'hide_empty' => false ) );
                  for ($i=0; $i < count($_terms); $i++):
                ?>
                <li>
                  <a href="<?=get_term_link($_terms[$i])?>"><?=$_terms[$i]->name?></a>
                </li>
                <?php endfor; ?>
                <li>
                  <a href="<?=get_term_link($top_category->term_id)?>">Ver Todos</a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-9">
      <div class="row" <?= !have_posts() ? 'style="height: 100%;"' : '' ?>>
        <?php 
        if ( have_posts() ):
          while ( have_posts() ):
            global $post;
            the_post();
            ?>
            <div class="col-md-4 mb-4">
              <?php print_card($post); ?>
            </div>
            <?php
          endwhile;
        else: ?>
          <div class="d-flex justify-content-center align-items-center" style="height: 100%; width: 100%;">
            <h2 class="color--<?=$color?>">¡Más productos próximamente!</h2>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>