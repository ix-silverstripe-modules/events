(function($) {
    $(function() {

        jQuery(document).on('click', 'a.select-all', function(e) {
            e.preventDefault();
            jQuery(this).closest('fieldset').find(':checkbox').prop('checked', 'checked');
        });

        jQuery(document).on('click', 'a.deselect-all', function(e) {
            e.preventDefault();
            jQuery(this).closest('fieldset').find(':checkbox').removeProp('checked');
        });
    });

})(jQuery);
