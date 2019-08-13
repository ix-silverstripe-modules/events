<div class="blog content-area">
     <div class="row">

        <!-- Main Content Area-->
        <div class="medium-8 small-12 large-9 columns medium-push-4 large-push-3 main-content">
            <% if $Finished %>
                <div class="content">
                    $FinishedMessage
                </div>
            <% else %>
                <script type="text/javascript">
                    tinyMCE.init({
                        theme : "advanced",
                        mode: "textareas",
                        theme_advanced_toolbar_location : "top",
                        theme_advanced_buttons1 : "formatselect,|,bold,italic,underline,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,outdent,indent,separator,undo,redo",
                        theme_advanced_buttons2 : "",
                        theme_advanced_buttons3 : "",
                        height:"250px",
                        width:"400px",
                        max_chars : 1000,
                        max_chars_indicator : ".maxCharsSpan",

                        setup : function(ed) {
                            wordcount = 0;
                            wordCounter = function (ed, e) {
                                text = ed.getContent().replace(/<[^>]*>/g, '').replace(/\s+/g, ' ');
                                text = text.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
                                this.wordcount = ed.getParam('max_chars') - text.length;
                                $(ed.getParam('max_chars_indicator')).text( this.wordcount + " (out of " +ed.getParam('max_chars')+ ") char(s) left." );
                            };

                            ed.onKeyUp.add( wordCounter );

                            ed.onKeyDown.add(function(ed, e) {
                                if(this.wordcount <= 0 && e.keyCode != 8 && e.keyCode != 46) {
                                     tinymce.dom.Event.cancel(e);
                                }
                            });
                        }
                    });
                </script>
                <% if $AddForm %>
                    <div class="customform cm-form">$AddForm</div>
                <% end_if %>
            <% end_if %>
        </div>
    </div>
</div>
