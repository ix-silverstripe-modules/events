(function($) {
	$.entwine(function($) {
		
		var types = [];
		var priceranges = [];
		
		if(!$('.type input[type=checkbox]:checked').length){
			$('input#all-types').attr('checked', 'checked');
		}
		
		if(!$('.price-range input[type=checkbox]:checked').length){
			$('input#all-ranges').attr('checked', 'checked');
		}
		
		$('form#refine-events').entwine({
			onsubmit: function(e){
				types 		= [];
				priceranges = [];
				
				$('.type input[type=checkbox]:checked').each(function(){
					types.push($(this).val())
				});
				
				$('.price-range input[type=checkbox]:checked').each(function(){
					priceranges.push($(this).val())
				});
				
				if(types.length){
					$('input#types').val(types.join("."));
				}else{
					$('input#types').removeAttr('name');
				}
				
				if(priceranges.length){
					$('input#ranges').val(priceranges.join("."));
				}else{
					$('input#ranges').removeAttr('name');
				}
				
				if(!$('input#end').val()){
					$('input#end').removeAttr('name');
				}
				
				return true;
			}
		});
		
		$('input#all-types').entwine({
			onclick: function(e){
				$('.type input[type=checkbox]').removeAttr('checked');
			}
		});
		
		$('.type input[type=checkbox]').entwine({
			onclick: function(e){
				if($('.type input[type=checkbox]:checked').length){
					$('input#all-types').removeAttr('checked');
				}else{
					$('input#all-types').attr('checked', 'checked');
				}
			}
		});
		
		function addslashes(string) {
			return string.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/@/g, '\\@').replace(/#/g, '\\#').replace(/%/g, '\\%').replace(/&/g, '\\&').replace(/!/g, '\\!');
		}
		
	});
})(jQuery);