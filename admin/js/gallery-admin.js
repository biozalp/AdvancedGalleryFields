jQuery(document).ready(function($) {
    var frame;
    var $galleryPreview = $('.gallery-preview');
    var $galleryData = $('#gallery_data');
    var $clearButton = $('.clear-gallery');

    // Open media library
    $('.gallery-button').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Gallery Images',
            button: {
                text: 'Add to Gallery'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            var ids = [];
            var html = '';

            attachments.forEach(function(attachment) {
                ids.push(attachment.id);
                html += '<div class="gallery-item" data-id="' + attachment.id + '">';
                html += '<img src="' + attachment.sizes.thumbnail.url + '" alt="">';
                html += '<div class="delete-image" title="Remove Image">';
                html += '<span class="dashicons dashicons-no-alt"></span>';
                html += '</div>';
                html += '</div>';
            });

            $galleryPreview.html(html);
            $galleryData.val(ids.join(','));
            $clearButton.show();
        });

        frame.open();
    });

    // Delete single image
    $galleryPreview.on('click', '.delete-image', function() {
        var $item = $(this).closest('.gallery-item');
        var itemId = $item.data('id');
        var currentIds = $galleryData.val().split(',');
        
        // Remove the ID from the array
        currentIds = currentIds.filter(function(id) {
            return id != itemId;
        });

        // Update the hidden input
        $galleryData.val(currentIds.join(','));

        // Remove the image from preview
        $item.remove();

        // Hide clear button if no images left
        if (currentIds.length === 0) {
            $clearButton.hide();
        }
    });

    // Clear entire gallery
    $clearButton.on('click', function() {
        if (confirm('Are you sure you want to clear the entire gallery?')) {
            $galleryPreview.empty();
            $galleryData.val('');
            $(this).hide();
        }
    });
});
