/* filter.js
 * ==========================
 * File contains javascript code relating to the "filter" search type in the events module.
 * Automatically included when chosen as the search type.
 */

(function($) {
    $.entwine(function($) {

        $('form#filter-search').entwine({
            onsubmit: function(e){

                if(!$('input#startd').val()){
                    $('input#startd').removeAttr('name');
                }

                if(!$('input#end').val()){
                    $('input#end').removeAttr('name');
                }

                if(!$('input#searchQuery').val()){
                    $('input#searchQuery').removeAttr('name');
                }

                return true;
            }
        });

    });
})(jQuery);
