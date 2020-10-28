;(function ( $, window, document, undefined ) {

    function format_money( number ){
        return '$' + (number).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function CartItem( data, selector, parent ){
        var _self = this;

        this.data = $.extend({
            id: 0,
            title: '',
            excerpt: '',
            price: 0,
            image: '',
            quantity: 1
        },data);
        this.data.quantity = Math.max(this.data.quantity,1);
        this.$events = $({});
        this.$element = $(
        '<div class="'+ selector +'__item">' +
            '<div class="media">' +
                '<div class="'+ selector +'__item__image">' +
                    '<img src="'+ this.data.image +'" />' +
                '</div>' +
                '<div class="'+ selector +'__item__data media-body">' +
                    '<p>'+ this.data.title +'</p>' +
                    '<p>'+ this.data.excerpt +'</p>' +
                    '<p><strong>'+ format_money(this.data.price) +'</strong></p>' +
                '</div>' +
            '</div>' +
            '<div class="'+ selector +'__actions">' +
                '<div class="'+ selector +'__remove"><i class="far fa-trash-alt"></i></div>' +
                '<div class="mf-counter">' +
                  '<div class="mf-counter__item quantity">'+ this.data.quantity +'</div>' +
                  '<div class="mf-counter__item minus"><i class="fas fa-minus"></i></div>' +
                  '<div class="mf-counter__item plus"><i class="fas fa-plus"></i></div>' +
                '</div>' +
            '</div>' +
        '</div>');

        this.$quantity = this.$element.find('.quantity');
        this.$element.find('.plus').click(function(){
            _self.plus();
        });
        this.$minus = this.$element.find('.minus');
        this.$minus.click(function(){
            _self.minus();
        });
        this.$element.find('.' + selector +'__remove').click(function(){
            parent.remove(_self);
        });
    }
    CartItem.prototype.on = function( event, fn ){
        this.$events.on(event, fn);
    };
    CartItem.prototype.off = function( event ){
        this.$events.off(event);
    };
    CartItem.prototype.get_id = function(){
        return this.data.id;
    };
    CartItem.prototype.get_data = function(){
        this.data.quantity = this.get_quantity();
        return this.data;
    };
    CartItem.prototype.get_total = function(){
        return this.get_quantity() * this.data.price;
    };
    CartItem.prototype.get_quantity = function(){
        return parseInt(this.$quantity.text());
    };
    CartItem.prototype.set_quantity = function(quantity){
        var val = this.get_quantity();
        if(quantity < 1){
            this.$minus.prop('disabled','disabled');
            this.$quantity.text(1);
        } else {
            this.$minus.prop('disabled',false);
            this.$quantity.text(quantity);
        }
        if(val !== this.get_quantity()){
            this.$events.trigger('change');
        }
    };
    CartItem.prototype.add = function( quantity ){
        return this.set_quantity(this.get_quantity() + quantity);
    };
    CartItem.prototype.plus = function(){
        return this.set_quantity(this.get_quantity() + 1);
    };
    CartItem.prototype.minus = function(){
        return this.set_quantity(this.get_quantity() - 1);
    };


    function CartItems( $element, selector ){
        var _self = this;
        this.$events = $({});
        this.$element = $element;
        this.items = [];
        this.selector = selector;
    }
    CartItems.prototype.on = function( event, fn ){
        this.$events.on(event, fn);
    };
    CartItems.prototype.off = function( event ){
        this.$events.off(event);
    };
    CartItems.prototype.get_item = function( id ){
        for (var i = 0; i < this.items.length; i++) {
            if(this.items[i].get_id() === id){
                return this.items[i];
            }
        }
        return false;
    };
    CartItems.prototype.add = function( data, trigger ){
        var item = this.get_item(data.id),
            _self = this;
        if(item){
            item.add(data.quantity);
        } else {
            item = new CartItem(data, this.selector, this);
            item.on('change',function( event ){
                _self.$events.trigger('change');
            });
            this.items.push( item );
            this.$element.append( item.$element );
            if(trigger !== false){
                this.$events.trigger('change');
            }
        }
        return item;
    };
    CartItems.prototype.remove = function( item ){
        var i = this.items.indexOf(item);
        if (i > -1) {
            this.items.splice(i, 1);
            item.$element.remove();
            this.$events.trigger('change');
        }
    };
    CartItems.prototype.get_totals = function(){
        var total = 0,
            quantity = 0;
        for (var i = 0; i < this.items.length; i++) {
            total += this.items[i].get_total();
            quantity += this.items[i].get_quantity();
        }
        return [total,quantity];
    };
    CartItems.prototype.add_items = function( data ){
        for (var i = 0; i < data.length; i++) {
            this.add(data[i],false);
        }
    };
    CartItems.prototype.empty = function(){
        this.items = [];
        this.$element.empty();
    };
    CartItems.prototype.get_data = function(){
        var data = [];
        for (var i = 0; i < this.items.length; i++) {
            data.push(this.items[i].get_data());
        }
        return data;
    };

    function Cart( $prefix ){
        var _self = this;

        this.selector = ($prefix || '') + 'cart';
        this.$events = $({});
        this.$element = $('.' + this.selector);
        
        this.$items = this.$element.find('.' + this.selector + '__items');
        this.$quantity = this.$element.find('.' + this.selector + '__subtotal span');
        this.$subtotal = this.$element.find('.' + this.selector + '__subtotal strong');

        this.$element.find('.' + this.selector + '__close, .' + this.selector + '__button--close').click(function(){
            _self.$events.trigger('click-close');
        });

        this.items = new CartItems(this.$items, this.selector);
        this.items.on('change', function(){
            _self.on_change();
        });
    }

    Cart.prototype.on = function( event, fn ){
        this.$events.on(event, fn);
    };
    Cart.prototype.off = function( event ){
        this.$events.off(event);
    };
    Cart.prototype.render = function(){
        this.render_items();
        this.render_totals();
    };
    
    Cart.prototype.on_change = function(){
        Cart.storage(this.items.get_data());
        this.render_totals();
        this.$events.trigger('change');
    };
    Cart.prototype.render_items = function(){
        this.items.empty();
        this.items.add_items(Cart.retrieve());
    };
    Cart.prototype.render_totals = function(){
        var totals = this.items.get_totals();
        this.$subtotal.text(format_money(totals[0]));
        this.$quantity.text(totals[1]);
        this.$events.trigger('change-totals',totals);
    };
    Cart.prototype.add = function( data ){
        this.items.add(data);
    };

    Cart.retrieve = function(){
        try {
            var data = JSON.parse(window.localStorage.getItem('cart'));
            return Array.isArray(data) ? data : [];
        } catch (e) {}
        return [];
    };
    Cart.storage = function( data ){
        window.localStorage.setItem('cart', JSON.stringify(data));
    };
    
    window.cart = Cart;
    
})( jQuery, window, document );