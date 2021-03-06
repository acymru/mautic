mQuery(document).ready( function() {
    CKEDITOR.disableAutoInline = true;
    mQuery("div[contenteditable='true']").each(function (index) {
        var content_id = mQuery(this).attr('id');
        var that = this;
        CKEDITOR.inline(content_id, {
            toolbar: 'advanced',
            on: {
                // Remove inserted <p /> tag if empty to allow the CSS3 placeholder to display
                blur: function( event ) {
                    var data = event.editor.getData();
                    if (!data) {
                        mQuery(that).html('');
                    }
                }
            }
        });
    });
});