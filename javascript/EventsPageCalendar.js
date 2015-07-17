(function($) {
	$(function () {
		applyStyles();
		
		$(".calendar").on("click", "#month-navigator a", function() {
			var me 	= $(this);
			var url = me.attr("href");
			var float = me.hasClass('calendar-prev')  ? "left" : "right";
			me.replaceWith('<img style="width: 25px; float:' + float + ';" src="events/images/loading_transparent.gif" />');
			
			$(".calendar").load(url, null, function(){
					applyStyles();
			});
			return false;
		});
		
		function applyStyles()
		{
			jQuery.each(jQuery(".calendar table .has-event a"), function(i, el) {
				var title = jQuery(this).attr('data-title');
				
				jQuery(this).qtip({
						content: title,
						show: {
							event: "mouseover"
						},
						hide: {
							event: "mouseout"
						},
						style: {
							classes: "qtip-bootstrap"
						},
						position: {
							at: "right center",
							my: "bottom left"
						}
					}
				);
			});
		}
	})
})(jQuery);