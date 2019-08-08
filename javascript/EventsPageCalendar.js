(function($) {
    $(function () {
        applyStyles();

        /* jump links */
        loadCalendarJumps();

        $(".calendar").on("click", "#month-navigator a", function() {
            var me = $(this);
            var url = me.attr("href");
            var float = me.hasClass('calendar-prev')  ? "left" : "right";

            var nextmonth = $('.calendar-jump-forward').html();
            var prevmonth = $('.calendar-jump-back').html();

            if(nextmonth && float == "right") {
                $(".calendar").html(nextmonth);
            }

            if(prevmonth && float == "left") {
                $(".calendar").html(prevmonth);
            }

            applyStyles();
            loadCalendarJumps();

            /*$(".calendar").load(url, null, function(){
                    applyStyles();
            });*/
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
                });
            });
        }

        function loadCalendarJumps() {
            $(".ui-datepicker-title").append('<img class="spinner" style="width: 25px; text-align:center;" src="/_resources/vendor/internetrix/silverstripe-events/images/loading_transparent.gif" />');

            /* jump links */
            var prevurl = $('#month-navigator .calendar-prev').attr('href');
            var nexturl = $('#month-navigator .calendar-next').attr('href');

            /* remove jump icons for now */
            $('#month-navigator .calendar-next').hide();
            $('#month-navigator .calendar-prev').hide();

            /* Load Back and Forward in the background*/
            $(".calendar-jump-forward").load(nexturl, null, function(){
                $('#month-navigator .calendar-next').show();
                $(".spinner").remove();
            });
            $(".calendar-jump-back").load(prevurl, null, function() {
                $('#month-navigator .calendar-prev').show();
                $(".spinner").remove();
            });
        }
    })
})(jQuery);
