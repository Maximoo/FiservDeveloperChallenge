<?php 

while (have_posts()) : the_post(); 

$images = get_post_meta(get_the_ID(),'images',true);

$price = (float) get_post_meta(get_the_ID(),'price',true);

$data = json_encode(array(
  'id' => get_the_ID(),
  'title' => get_the_title(),
  'excerpt' => get_the_excerpt(),
  'price' => $price,
  'quantity' => 1,
  'image' => get_the_post_thumbnail_url(get_the_ID(),'product-cart')
));

?>
<div class="container py-5">

  <div class="row mb-5">
    <div class="col-md-7">
      <div class="mf-photos">
        <div class="mf-photos__thumbs">
          <?php for ($i=0; $i < count($images); $i++): ?>
          <div class="mf-photos__thumb" style="background-image: url(<?=$images[$i]["square"]?>);"></div>
          <?php endfor; ?>
        </div>
        <div class="mf-photos__photo">
          <?php if(!empty($images)): ?>
          <img src="<?=$images[0]["full"]?>" alt="">
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="mf-product">
        <h1 class="mf-product__title"><?php the_title(); ?></h1>
        <div class="mf-product__stars mf-stars" data-rank="<?=rand(7,10)/2?>">
          <i></i><i></i><i></i><i></i><i></i>
        </div>
        <div class="mf-product__price">$<span>280</span> MXN</div>
        <p>Selecciona la fecha de entrega:</p>
        <div class="mf-product__schedule mb-3">
          <div class="mf-product__schedule__item" data-radio="today">
            Hoy
          </div><div class="mf-product__schedule__item" data-radio="tomorrow">
            Mañana
          </div><div class="mf-product__schedule__item mf-product__schedule__item--small" data-radio="cal">
            <i class="far fa-calendar-alt"></i>
          </div>
        </div> 
        <p>Cantidad:</p>
        <div class="mb-3 mf-counter" data-counter="1">
          <div class="mf-counter__item quantity">1</div>
          <div class="mf-counter__item minus"><i class="fas fa-minus"></i></div>
          <div class="mf-counter__item plus"><i class="fas fa-plus"></i></div>
        </div>
        <p>Aceptamos las siguientes tarjetas:</p>
        <div class="mf-cc mb-3 text-left">
          <span class="mf-cc__card">
            <?php _svg('visa.svg'); ?>
          </span>
          <span class="mf-cc__card">
            <?php _svg('mastercard.svg'); ?>
          </span>
          <span class="mf-cc__card">
            <?php _svg('amex.svg'); ?>
          </span>
          <img src="<?=_image('fiserv.png')?>" class="mf-cc__card mf-cc__card--fiserv" alt="">
        </div>
        <hr />
        <div class="mf-product__actions" data-object='<?=$data?>'>
          <div class="mf-product__action mf-product__action--buy"><i class="fas fa-store"></i> Comprar Ahora</div><div class="mf-product__action mf-product__action--cart" ></div>
        </div>
      </div>      
    </div>
  </div>
  <div class="row mb-5">
    <div class="offset-md-1 col-md-10">
      <div class="mf-product-description">
        <h4>Descripción:</h4>
        <?php the_content(); ?>
        <hr />
      </div>
    </div>
  </div>  
</div>

<?php endwhile; ?>
