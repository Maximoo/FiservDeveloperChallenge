(function($,window,undefined){
	$(function(){

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
			console.log(action,actions);
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