<?php
/**
 * Template Name: Gracias Template
 */
?>

<div class="container mb-5">
    <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4 rounded-lg" src="<?=_image('logo-icono.png')?>" alt="" width="72" height="72">
        <h3>Gracias por tu compra</h3>
        <h4>Tu número de pedido es: <strong><?=$_GET['order']?></strong></h4>
    </div>
</div>