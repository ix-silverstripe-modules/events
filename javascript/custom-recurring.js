;jQuery(function($) {
	
	$('#Form_EditForm_RecurringFrequency').livequery(function(){
		
		if( $('#Form_EditForm_RecurringFrequency').val() == 'Custom'){
			$('#CustomRecurringNumber').show();
			$('#CustomRecurringFrequency').show();
		}else{
			$('#CustomRecurringNumber').hide();
			$('#CustomRecurringFrequency').hide();
		}
		
		$('#Form_EditForm_RecurringFrequency').livequery('change', function(){
			if( $('#Form_EditForm_RecurringFrequency').val() == 'Custom'){
				$('#CustomRecurringNumber').show();
				$('#CustomRecurringFrequency').show();
			}else{
				$('#CustomRecurringNumber').hide();
				$('#CustomRecurringFrequency').hide();
			}
		});
	});
	
}); 