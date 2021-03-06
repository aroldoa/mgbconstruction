/**
 * Hooks into the global Plupload instance ('uploader'), which is set when includes/admin/metaboxes.php calls media_form()
 * We hook into this global instance and apply our own changes during and after the upload.
 *
 * @since 1.3.1.3
 */

(function( $ ) {
    $(function() {

        if ( typeof uploader !== 'undefined' ) {

            // Set a custom progress bar
            $('#envira-gallery .drag-drop-inside').append( '<div class="envira-progress-bar"><div></div></div>' );
            var envira_bar      = $('#envira-gallery .envira-progress-bar'),
                envira_progress = $('#envira-gallery .envira-progress-bar div'),
                envira_output   = $('#envira-gallery-output');

            // Files Added for Uploading
            uploader.bind( 'FilesAdded', function ( up, files ) {
                $( envira_bar ).fadeIn();
            });

            // File Uploading - show progress bar
            uploader.bind( 'UploadProgress', function( up, file ) {
                $( envira_progress ).css({
                    'width': up.total.percent + '%'
                });
            });

            // File Uploaded - AJAX call to process image and add to screen.
            uploader.bind( 'FileUploaded', function( up, file, info ) {

                // AJAX call to Envira to store the newly uploaded image in the meta against this Gallery
                $.post(
                    envira_gallery_media_uploader.ajax,
                    {
                        action:  'envira_gallery_load_image',
                        nonce:   envira_gallery_media_uploader.load_image,
                        id:      info.response,
                        post_id: envira_gallery_media_uploader.id
                    },
                    function(res){
                        //console.log(res);

                        // Append the new image to the existing grid of images
                        $(envira_output).append(res);

                        $(res).find('.wp-editor-container').each(function(i, el){
                            var id = $(el).attr('id').split('-')[4];
                            quicktags({id: 'envira-gallery-caption-' + id, buttons: 'strong,em,link,ul,ol,li,close'});
                            QTags._buttonsInit(); // Force buttons to initialize.
                        });

                    },
                    'json'
                );
            });

            // Files Uploaded
            uploader.bind( 'UploadComplete', function() {

                // Hide Progress Bar
                $( envira_bar ).fadeOut();

            });

            // File Upload Error
            uploader.bind('Error', function(up, err) {

                // Show message
                $('#envira-gallery-upload-error').html( '<div class="error fade"><p>' + err.file.name + ': ' + err.message + '</p></div>' );
                up.refresh();

            });

        }

    });
})( jQuery );
