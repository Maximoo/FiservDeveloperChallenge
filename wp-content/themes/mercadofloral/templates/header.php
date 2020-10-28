<header class="mf-header">
  <div class="mf-header__top">
    <div class="container">
      <button id="menu-trigger" class="mf-header__top__trigger hamburger hamburger--spin" type="button">
        <span class="hamburger-box"><span class="hamburger-inner"></span></span>
      </button>
      <a class="mf-header__top__brand" href="<?= esc_url(home_url('/')); ?>">
        <?php bloginfo('name'); ?>
      </a>
      <div class="mf-header__top__search">
        <div class="mf-header__top__search__inner">
          <form action="<?= esc_url(home_url('/')); ?>">
            <input id="header-input-search" type="text" name="s" value="" autocomplete="off" placeholder="Buscar en Mercado Floral">
            <button type="submit"><i class="fas fa-search"></i></button>
          </form>
        </div>
      </div>
      <?php if(!is_page('checkout') && !is_page('gracias')): ?>
      <div class="mf-header__top__text">Mi Bolsa:</div>
      <div class="mf-header__top__icon mf-header__top__icon--bolsa mf-badge" data-badge="0" id="cart-trigger"></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="mf-header__bottom">
    <div class="container">
      <nav class="mf-header__bottom__nav">
        <?php
        if (has_nav_menu('primary_navigation')) :
          wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav', 'container'=> false]);
        endif;
        ?>
      </nav>
    </div>
  </div>
</header>

<div id="focus-modal" class="mf-focusmodal"></div>
<div id="cart-modal" class="mf-cartmodal"></div>

<div id="cart" class="mf-cart">
  <div class="mf-cart__title">Mi Bolsa</div>
  <div class="mf-cart__close"><i class="fas fa-times"></i></div>
  <div class="mf-cart__items">
    <!-- div class="mf-cart__item">
      <div class="mf-cart__item__image">
        <img src="" alt="" />
      </div>
      <div class="mf-cart__item__data">
        <p>lorem ipsum</p>
        <p>lorem ipsum lorem ipsum lorem ipsum</p>
        <p><strong>$299.00</strong></p>
      </div>
      <div class="mf-cart__actions">
        <div class="mf-cart__remove"></div>
        <div class="mf-cart__quantity">
          <div class="mf-cart__quantity__value">1</div>
          <button class="mf-cart__quantity__button plus"><i class="fas fa-plus"></i></button>
          <button class="mf-cart__quantity__button minus"><i class="fas fa-minus"></i></button>
        </div>  
      </div>
    </div -->
  </div>
  <div class="mf-cart__subtotal">
    <p>Subtotal (<span>1</span>)</p>
    <p><strong>$300.00</strong></p>
  </div>
  <a class="mf-cart__button" href="<?= esc_url(home_url('/checkout')); ?>"><i class="fas fa-store"></i> Completar compra</a>
  <a class="mf-cart__button mf-cart__button--close" href="#">Seguir Comprando</a>
</div>