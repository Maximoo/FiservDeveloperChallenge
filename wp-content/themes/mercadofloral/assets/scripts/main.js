/* ========================================================================
 * DOM-based Routing
 * Based on http://goo.gl/EUTi53 by Paul Irish
 *
 * Only fires on body classes that match. If a body class contains a dash,
 * replace the dash with an underscore when adding it to the object below.
 *
 * .noConflict()
 * The routing is enclosed within an anonymous function so that you can
 * always reference jQuery with $, even when in .noConflict() mode.
 * ======================================================================== */

(function($) {



  // Use this variable to set up the common and page specific functions. If you
  // rename this variable, you will also need to rename the namespace below.
  var Sage = {
    // All pages
    'common': {
      init: function() {
        // JavaScript to be fired on all pages
        var $menu_trigger = $('#menu-trigger'),
            $cart_trigger = $('#cart-trigger'),
            $search = $('#header-input-search');

        function update_menu_trigger(){
          $menu_trigger.toggleClass('is-active',$('body').hasClass('menu-open'));
        }

        function set_open_class( $class, $toggle ){
          var $classes = ['menu','search','cart'];
          for (var i = $classes.length - 1; i >= 0; i--) {
            if($classes[i] !== $class){
              $('body').removeClass($classes[i] + '-open');
            }
          }
          $('body').toggleClass($class + '-open', $toggle);
          update_menu_trigger();
        }

        update_menu_trigger();
        $menu_trigger.click(function(){
          set_open_class('menu');
        });
        $search.focus(function(){
          set_open_class('search',true);
        });
        $search.blur(function( event ){
          if(event.relatedTarget && event.relatedTarget.type !== "submit"){
            set_open_class('search',false);
          }
        });
        $cart_trigger.click(function(){
          set_open_class('cart');
        });
        $('#focus-modal, #cart-modal').click(function(){
          set_open_class('');
        });

        var cart = new window.cart('mf-');
        cart.on('click-close', function(){
          set_open_class('');
        });
        cart.on('change-totals', function(event, total, quantity){
          $cart_trigger.attr('data-badge',quantity);
        });
        cart.render();

        if($('.mf-product__actions').length){
          var data = $('.mf-product__actions').data('object'),
              $buy = $('.mf-product__action--buy'),
              $cart = $('.mf-product__action--cart'),
              $quantity = $('.mf-counter');

          $buy.click(function(){
            data.quantity = $quantity.data('counter');
            cart.add(data);
            window.location.href = "/checkout";
          });

          $cart.click(function(){
            data.quantity = $quantity.data('counter');
            cart.add(data);
            set_open_class('cart');
          });
        }

          

        function checkout(items, contact, shipping, data){
          $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '/wp-json/checkout/payment',
            data: {items:items,contact:contact,shipping:shipping,data:data},
            success: function(data){
              console.log(data);
              if(data.status === 'approved'){
                window.cart.storage([]);
                window.location.href = "/gracias?order=" + data.order_uniqid;
              } else {
                alert(data.message);
              }
            },
            error: function (jqXHR, textStatus, errorThrown) {
              alert(jqXHR.responseJSON.message);
            }
          });
        }

        if($("#checkout-cart").length){
          (function(){
            var data = window.cart.retrieve(),
                total = 0,
                quantity = 0;
            for (var i = data.length - 1; i >= 0; i--) {
              quantity += data[i].quantity;
              total += data[i].price * data[i].quantity;
              $("#checkout-cart").prepend($(
                '<li class="list-group-item d-flex justify-content-between lh-condensed">'+
                      '<div>'+
                        '<div class="media">'+
                          '<img src="'+data[i].image+'" width="70px" class="mr-2">'+
                          '<div class="media-body">'+
                              '<h6 class="my-0">('+data[i].quantity+') '+data[i].title+'</h6>'+
                              '<small class="text-muted">'+data[i].excerpt+'</small>'+
                          '</div>  '+
                        '</div>'+
                      '</div>'+
                      '<span class="text-muted">$'+data[i].price+'</span>'+
                  '</li>'
              ));
              $("#checkout-cart-total").text(total);
              $("#checkout-cart-quantity").text(quantity);
            }

            var $form = $("#checkout-form");
            $form.on('submit',function( event ){
              event.preventDefault();
              event.stopPropagation();
              if (this.checkValidity() === true) {
                checkout(
                  data,
                  {
                    'name': $('#name').val() + ' ' + $('#last_name').val(),
                    'email': $('#email').val(),
                  },{
                    'name': $('#name').val(),
                    'last_name': $('#last_name').val(),
                    'street': 'Calle A',
                    'ext_number': '1',
                    'int_number': '',
                    'phone': '555555555',
                    'postal_code': $('#zip').val(),
                    'state': 'CDMX',
                    'city': 'CDMX',
                    'municipality': 'Miguel Hidalgo',
                    'neighborhood': 'Narvarte',
                    'directions': 'Entre Amores y Xola'
                  },{
                    'coupon':'',
                    'payment_method':'fiserv',
                    'payment_method_card':'VISA',
                    'transaction_amount':total,
                    'installments':'0',
                    'token':'',
                    'card_number':$('#cc-number').val(),
                    'card_securityCode':$('#cc-cvv').val(),
                    'card_month':$('#cc-month').val(),
                    'card_year':$('#cc-year').val()
                  }
                )
              } 
              this.classList.add('was-validated');
            })

          })();
        }

      },
      finalize: function() {
        $('[data-radio]').click(function(){
          $(this).addClass('checked');
          $(this).siblings().removeClass('checked');
        });
        $('[data-counter]').each(function( i, e ){
          var $quantity = $(this).find(".quantity"),
              $plus = $(this).find(".plus"),
              $minus = $(this).find(".minus"),
              $self = $(this);

          $plus.click(function(){
            var c = parseInt($self.data('counter')) + 1;
            $self.data('counter', c);
            $quantity.text(c);
          });
          $minus.click(function(){
            var c = Math.max(1, parseInt($self.data('counter')) - 1);
            $self.data('counter', c);
            $quantity.text(c);
          });
        });
        $('.mf-slider').each(function(){
          var $element = $(this),
              $items = $element.find('.mf-slider__item'),
              $dots = $element.find('.mf-slider__dots__item'),
              $left = $element.find('.mf-slider__arrows__item--left'),
              $right = $element.find('.mf-slider__arrows__item--right'),
              posts_slider = {
                $element: $element,
                $items: $items,
                $dots: $dots,
                $left: $left,
                $right: $right,
                count: $items.length,
                active: $element.data('slide') || 1,
                hover: false,
                active_slide: function(index){
                  this.active = index;
                  if(this.active > this.count){
                    this.active = 1;
                  }
                  if(this.active < 1){
                    this.active = this.count;
                  }
                  this.$element.attr('data-slide',this.active);
                },
                next_slide: function(){
                  this.active_slide(this.active + 1);
                },
                prev_slide: function(){
                  this.active_slide(this.active - 1);
                },
                init: function(){
                  var _this = this;
                  setInterval(function(){
                    if(!_this.hover){
                      _this.next_slide();
                    }
                  },$element.data('slide-interval') || 10000);
                  this.$dots.click(function(event){
                    event.preventDefault();
                    _this.active_slide($(this).index() + 1);
                  });
                  this.$left.click(function(event){
                    _this.prev_slide();
                  });
                  this.$right.click(function(event){
                    _this.next_slide();
                  });
                  this.$element.hover(function(){
                    _this.hover = true;
                  },function(){
                    _this.hover = false;
                  });
                }
              };
          $element.data('posts-slide',posts_slider);
          posts_slider.init();
        });
      }
    },
    // Home page
    'home': {
      init: function() {
        // JavaScript to be fired on the home page
      },
      finalize: function() {
        // JavaScript to be fired on the home page, after the init JS
      }
    },
    // About us page, note the change from about-us to about_us.
    'about_us': {
      init: function() {
        // JavaScript to be fired on the about us page
      }
    }
  };

  // The routing fires all common scripts, followed by the page specific scripts.
  // Add additional events for more control over timing e.g. a finalize event
  var UTIL = {
    fire: function(func, funcname, args) {
      var fire;
      var namespace = Sage;
      funcname = (funcname === undefined) ? 'init' : funcname;
      fire = func !== '';
      fire = fire && namespace[func];
      fire = fire && typeof namespace[func][funcname] === 'function';

      if (fire) {
        namespace[func][funcname](args);
      }
    },
    loadEvents: function() {
      // Fire common init JS
      UTIL.fire('common');

      // Fire page-specific init JS, and then finalize JS
      $.each(document.body.className.replace(/-/g, '_').split(/\s+/), function(i, classnm) {
        UTIL.fire(classnm);
        UTIL.fire(classnm, 'finalize');
      });

      // Fire common finalize JS
      UTIL.fire('common', 'finalize');
    }
  };

  // Load Events
  $(document).ready(UTIL.loadEvents);

})(jQuery); // Fully reference jQuery after this point.
