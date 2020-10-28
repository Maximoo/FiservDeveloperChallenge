(function($,window,undefined){
	$(function(){

		function update_inventory( ids, stock ){
			$('#list-modal').show();
			update_inventory_vars.ids = ids;
			update_inventory_vars.stock = stock;
			$.post(update_inventory_vars.url, update_inventory_vars, function(data, textStatus) {
			  alert(data.message);
			  if(data.success){
			  	window.location.reload(true); 
			  } else {
			  	$('#list-modal').hide();
			  }
			}, "json");
		}

		$('[data-list-action=update]').click(function( event ){
			event.preventDefault();
			var id = $(this).data('list-id'),
				title = $(this).data('list-title');
			var stock = prompt(title + " \n Ingrese la cantidad:", "");
			if(stock !== null){
				if($.isNumeric(stock)){
					update_inventory(id, stock);
				} else {
					alert('Cantidad no válida.');
				}
			}
		});

		$('[name="items[]"], .cb-select-all').change(function(){
			$ids = '';
			$('[name="items[]"]:checked').each(function(i,e){
				$ids += $(this).val() + ',';
			});
			$ids = $ids.substring(0,$ids.length - 1);
			$('.check-ids').val($ids);
		});

		$('form[data-actions]').submit(function( e ){
			var action = $(this).find('[name=action]').val(),
				actions = $(this).data('actions');
			
			if(action == 'update'){
				e.preventDefault();
				var ids = $('.check-ids').val();

				if(ids){
					var stock = prompt("Ingrese la cantidad:", "");
					if(stock !== null){
						if($.isNumeric(stock)){
							update_inventory(ids, stock);
						} else {
							alert('Cantidad no válida.');
						}
					}
				} else {
					alert('Debe seleccionar como mínimo 1 elemento.');
				}
				return false;
			}
			for (var i = actions.length - 1; i >= 0; i--) {
				if(actions[i]['id'] == action){
					if(typeof actions[i]['confirm'] == 'boolean' && actions[i]['confirm']){
						return confirm('¿Está seguro que desea '+ actions[i]['label'].toLowerCase() +' estos elementos?');
					}
					break;
				}
			}
		});

	});
})(jQuery, window);