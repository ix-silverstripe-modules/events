(function($) {
//	alert('loaded');
	var categories 		= [];
	var categoriesField = $('input[name="category"]');
	var fetching = false;
	
	if(categoriesField.val()){
		$.each(categoriesField.val().split("."), function(key, value) {
			categories.push(value);
			console.log(categories);
//			$('.filter-events a[data-urlsegment="'+ value +'"]').parent().toggleClass('selected');
		});
	}
		
	$('.filter-events a').entwine({
		onclick: function(e) {
			e.preventDefault();
			var me = $(this);
			var urlsegment = me.attr('data-urlsegment');
			
			if(urlsegment){
				me.parent().toggleClass('selected');
				
				if(me.parent().hasClass('selected')){
					categories.push(urlsegment);
				}else{
					categories = jQuery.grep(categories, function(value) {
						return value != urlsegment;
					});
				}
				$('input[name="category"]').val(categories.join("."));
				
//				console.log(categories);
			}
			
			
			return false;
		}
	});
	
	$('#printpdf').click(function(e){
		
		var me = $(this);
		me.addClass("loading");
		
		if(!fetching){
			fetching = true;
			setTimeout(function() {
				me.removeClass("loading");
			}, 10000);
		}else{
			e.preventDefault();
			return false;
		}
		
		
	});
	
}(jQuery));