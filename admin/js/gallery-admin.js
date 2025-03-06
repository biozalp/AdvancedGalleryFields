jQuery(document).ready(function($) {
    var frame;
    var $galleryPreview = $('.gallery-preview');
    var $galleryData = $('#gallery_data');
    var $clearButton = $('.clear-gallery');

    // Initialize sortable for drag-and-drop reordering
    function initSortable() {
        if ($galleryPreview.children().length > 1) {
            $galleryPreview.sortable({
                items: '.gallery-item',
                cursor: 'move',
                opacity: 0.7,
                placeholder: 'gallery-item-placeholder',
                update: function() {
                    updateGalleryOrder();
                }
            });
            $galleryPreview.disableSelection();
        }
    }

    // Update the hidden input with the new order of images
    function updateGalleryOrder() {
        var ids = [];
        $galleryPreview.find('.gallery-item').each(function() {
            ids.push($(this).data('id'));
        });
        $galleryData.val(ids.join(','));
    }

    // Initialize sortable on page load
    initSortable();

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

            // If there are existing images, append the new ones
            if ($galleryPreview.children().length > 0) {
                $galleryPreview.append(html);
                // Get all current IDs and update the input
                updateGalleryOrder();
            } else {
                $galleryPreview.html(html);
                $galleryData.val(ids.join(','));
            }
            $clearButton.show();
            
            // Re-initialize sortable after adding new images
            initSortable();
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
