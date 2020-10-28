<div class="mf-slider mf-slider--bg mf-slider--c3 mb-5" data-slide="1">
	<div class="mf-slider__inner">
		<div class="mf-slider__item" style="background-image: url(<?=_image('home-slider.jpg')?>)"></div>
		<div class="mf-slider__item" style="background-image: url(<?=_image('home-slider.jpg')?>)"></div>
		<div class="mf-slider__item" style="background-image: url(<?=_image('home-slider.jpg')?>)"></div>	
	</div>
	<div class="mf-slider__dots">
		<div class="mf-slider__dots__item"></div>
		<div class="mf-slider__dots__item"></div>
		<div class="mf-slider__dots__item"></div>
	</div>
	<div class="mf-slider__arrows">
		<div class="mf-slider__arrows__item mf-slider__arrows__item--left"><i class="fas fa-chevron-left"></i></div>
		<div class="mf-slider__arrows__item mf-slider__arrows__item--right"><i class="fas fa-chevron-right"></i></div>
	</div>
</div>

<div class="container mb-5">
	<p class="text-center text-uppercase font-size--6 color--pink mb-4 font-weight--bold">Envíos CDMX y Área Metropolitana</p>
	<div class="row color--purple font-weight--bold">
		<div class="col-md-4 offset-lg-2 col-lg-3 mb-3">
			<div class="media" style="max-width: 240px; margin: auto;">
				<i class="far fa-credit-card mr-3" style="font-size: 70px"></i>
				<div class="media-body align-self-end" style="line-height: 1.2em;">Aceptamos<br /> todas las tarjetas</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-3 mb-3">
			<div class="media" style="max-width: 240px; margin: auto;">
				<img src="<?=_image('fiserv_bg.jpg')?>" alt="Fiserv" class="img-fluid mr-3" style="max-width: 68px;" />
				<div class="media-body align-self-end" style="line-height: 1.2em;">Seguridad<br /> al pagar</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-3 mb-3">
			<div class="media" style="max-width: 240px; margin: auto;">
				<i class="fas fa-shipping-fast mr-3" style="font-size: 70px"></i>
				<div class="media-body align-self-end" style="line-height: 1.2em;">Envíos el<br /> mismo día</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="offset-sm-2 col-sm-9"><hr></div>
	</div>
</div>

<?php 

$terms = get_terms( array( 'taxonomy' => 'product-category', 'parent' => 0, 'hide_empty' => false ) ); 

for ($t=0; $t < count($terms); $t++): 
	$term = $terms[$t];
	$color = get_term_meta($term->term_id,'color',true);
	$images = get_term_meta($term->term_id,'images',true);

	$posts = get_posts(array(
		'post_type' => 'product',
		'numberposts' => 4,
		'tax_query' => array(
		    array(
		      'taxonomy' => 'product-category',
		      'field' => 'term_id', 
		      'terms' => $term->term_id,
		      'include_children' => false
		    )
		)
	));
?>

<div class="container mb-5">
	<div class="row">
		<div class="col-lg-3 mb-4">
			<div class="mf-card mf-card--menu">
				<div class="mf-card__menu mf-card__menu--<?=$color?>">
					<a href="<?=get_term_link($term->term_id)?>" class="mf-card__menu__title">
						<?=$term->name?>
					</a>
					<div class="mf-card__menu__items">
						<nav>
							<ul>
								<?php 
									$_terms = get_terms( array( 'taxonomy' => 'product-category', 'parent' => $term->term_id, 'hide_empty' => false ) );
									for ($i=0; $i < count($_terms); $i++):
								?>
								<li>
									<a href="<?=get_term_link($_terms[$i])?>"><?=$_terms[$i]->name?></a>
								</li>
								<?php endfor; ?>
							</ul>
						</nav>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-9">
			<div class="row">
				<div class="col-md-8 mb-4">
					<?php if(!empty($images)): ?>
					<div class="mf-slider mf-slider--c<?=count($images)?>" data-slide="1" data-slide-interval="12000">
						<div class="mf-slider__inner">
							<?php for ($i=0; $i < count($images); $i++): ?>
							<div class="mf-slider__item"><img src="<?=$images[$i]['image']?>" class="img-fluid" /></div>
							<?php endfor; ?>
						</div>
						<div class="mf-slider__dots">
							<?php for ($i=0; $i < count($images); $i++): ?>
							<div class="mf-slider__dots__item"></div>
							<?php endfor; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
				<div class="col-md-4 mb-4">
					<?php if(!empty($posts)) { print_card($posts[0], $color); array_shift($posts); } ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4 mb-4">
					<?php if(!empty($posts)) { print_card($posts[0], $color); array_shift($posts); } ?>
				</div>
				<div class="col-md-4 mb-4">
					<?php if(!empty($posts)) { print_card($posts[0], $color); array_shift($posts); } ?>
				</div>
				<div class="col-md-4 mb-4">
					<?php if(!empty($posts)) { print_card($posts[0], $color); } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php endfor; ?>