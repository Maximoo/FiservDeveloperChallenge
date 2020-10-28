(function($,window,undefined){
	$(function(){

		var $console = $('#console'),
			$start = $('#submit-cmb'),
			ajax_url = $start.data('ajax');

		function console_log( text ){
			$console.append('<div><pre>'+text+'</pre></div>');
		}

		function import_index( index, option ){
			$.get(ajax_url + '&index=' + index + '&option=' + (option || ''),function(data){
				console_log(data);
				if(data !== 'Done'){
					import_index( index + 1, option );
				}
			});
		}

		$start.click(function(event){
			event.preventDefault();
			$('.submit').hide();
			console_log('Importing...');
			import_index(0, $('input[name=cmb-option]:checked').val());
		});

	});
})(jQuery, window);