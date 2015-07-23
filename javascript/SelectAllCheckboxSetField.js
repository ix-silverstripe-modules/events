;jQuery(function($) {
	
	$(document).ready(function(){
		
		$('a.select-all').live('click', function(){
			$(this).closest('fieldset').find(':checkbox').attr('checked', 'checked');
			return false;
		})
		
		$('a.deselect-all').live('click', function(){
			$(this).closest('fieldset').find(':checkbox').removeAttr('checked');
			return false;
		})
		
	});
	
}); 