(function($) {
	$(function() {
		
		$(document).on('click', 'a.select-all', function(){
			$(this).closest('fieldset').find(':checkbox').attr('checked', 'checked');
			return false;
		})
		
		$(document).on('click', 'a.deselect-all', function(){
			$(this).closest('fieldset').find(':checkbox').removeAttr('checked');
			return false;
		})
	});

})(jQuery);