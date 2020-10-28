<?php
/**
 * Template Name: Checkout Template
 */
?>

<div class="container mb-5">
    <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4 rounded-lg" src="<?=_image('logo-icono.png')?>" alt="" width="72" height="72">
        <h3>Completa tu compra</h3>
    </div>
    <div class="row">
        <div class="col-md-4 order-md-2 mb-4">
            <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Tu bolsa</span>
                <span class="badge badge-secondary badge-pill" id="checkout-cart-quantity">0</span>
            </h4>
            <ul class="list-group mb-3 sticky-top" id="checkout-cart">
				<!-- li class="list-group-item d-flex justify-content-between bg-light">
                    <div class="text-success">
                        <h6 class="my-0">Código de Descuento</h6>
                        <small>FISERV</small>
                    </div>
                    <span class="text-success">-$5</span>
                </li -->
                <li class="list-group-item d-flex justify-content-between">
                    <span>Total (MXN)</span>
                    <strong>$<span id="checkout-cart-total">0</span></strong>
                </li>
            </ul>
            <!-- form class="card p-2">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Cupón de descuento">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-secondary">Validar</button>
                    </div>
                </div>
            </form -->
        </div>
        <div class="col-md-8 order-md-1">
            <h4 class="mb-3">Dirección de Envío</h4>
            <form class="needs-validation" novalidate="" id="checkout-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name">Nombre</label>
                        <input type="text" class="form-control" id="name" placeholder="" value="" required="">
                        <div class="invalid-feedback">Requerido</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name">Apellido</label>
                        <input type="text" class="form-control" id="last_name" placeholder="" value="" required="">
                        <div class="invalid-feedback">Requerido</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="email@gmail.com">
                    <div class="invalid-feedback">Se requiere un correo electrónico válido.</div>
                </div>
                <div class="mb-3">
                    <label for="address">Dirección</label>
                    <input type="text" class="form-control" id="address" placeholder="" required="">
                    <div class="invalid-feedback">Requerido</div>
                </div>
                <div class="mb-3">
                    <label for="address2">Dirección 2 <span class="text-muted">(Opcional)</span></label>
                    <input type="text" class="form-control" id="address2" placeholder="">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="zip">Código postal</label>
                        <input type="text" class="form-control" id="zip" placeholder="" required="">
                        <div class="invalid-feedback">Requerido</div>
                    </div>
                </div>
                <hr class="mb-4">
                <h4 class="mb-3">Pago</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cc-number">Número de la tarjeta</label>
                        <input type="text" class="form-control" id="cc-number" placeholder="" required="">
                        <div class="invalid-feedback"> Requerido </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cc-name">Nombre de propietario</label>
                        <input type="text" class="form-control" id="cc-name" placeholder="" required="">
                        <small class="text-muted">Como se muestra en la tarjeta</small>
                        <div class="invalid-feedback"> Requerido </div>
                    </div>
                    
                </div>
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label for="cc-month">Mes</label>
                        <input type="text" class="form-control" id="cc-month" placeholder="" required="">
                        <div class="invalid-feedback">Requerido</div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="cc-year">Año</label>
                        <input type="text" class="form-control" id="cc-year" placeholder="" required="">
                        <div class="invalid-feedback">Requerido</div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="cc-cvv">CVV</label>
                        <input type="text" class="form-control" id="cc-cvv" placeholder="" required="">
                        <div class="invalid-feedback">Requerido</div>
                    </div>
                </div>
                <hr class="mb-4">
                <button class="btn btn-primary btn-lg btn-block" type="submit">Pagar</button>
            </form>
        </div>
    </div>
</div>