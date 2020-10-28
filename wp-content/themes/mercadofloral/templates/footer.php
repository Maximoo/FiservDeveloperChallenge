<footer class="mf-footer">
	<div class="container">
		<div class="row">
			<div class="col-md-4">
				<a href="<?= esc_url(home_url('/')); ?>" class="mf-footer__logo mx-auto mx-md-0">
					<?php _svg('mercado-floral-logo.svg'); ?>
				</a>
				<nav class="mf-footer__menu text-center text-md-left pb-3">
					<ul>
						<li>
							<a href="#">¿Eres Vendedor?</a>
						</li>
						<li>
							<a href="#">Contacto</a>
						</li>
						<li>
							<a href="#">Tiendas Oficiales</a>
						</li>
					</ul>
				</nav>
			</div>
			<div class="col-md-4 mb-3">
				<div class="mf-footer__social">
					<p class="text-uppercase">Síguenos en:</p>
					<p>
						<a class="mf-footer__social__icon" href="#"><i class="fab fa-facebook-f"></i></a>
						<a class="mf-footer__social__icon" href="#"><i class="fab fa-twitter"></i></a>
						<a class="mf-footer__social__icon" href="#"><i class="fab fa-instagram"></i></a>
					</p>
				</div>
			</div>
			<div class="col-md-4">
				<div class="mf-cc mb-3">
		          <span class="mf-cc__card">
		            <?php _svg('visa.svg'); ?>
		          </span>
		          <span class="mf-cc__card">
		            <?php _svg('mastercard.svg'); ?>
		          </span>
		          <span class="mf-cc__card">
		            <?php _svg('amex.svg'); ?>
		          </span>
		        </div>
		        <div class="mf-cc">
		          <div class="mf-cc__card">
		            Con tecnología de:<br /><br />
		            <img src="<?=_image('fiserv.png')?>" class="mf-cc__card mf-cc__card--fiserv" alt="">
		          </div>
		        </div>
			</div>
		</div>
	</div>
	<div class="container text-center py-3">
		©<?=date('Y')?> Mercado Floral, Todos los derechos reservados.
	</div>
</footer>
