<?php
/**
 * Plugin Name: Custom Gallery Field
 * Description: Adds a custom gallery field to posts and makes it available as an Elementor dynamic tag
 * Version: 1.0
 * Author: Berk Ilgar Ozalp
 */

 if (!defined('ABSPATH')) {
    exit;
}

// Register Meta Box for Gallery Field
function register_gallery_meta_box() {
    add_meta_box(
        'custom_gallery_field',
        'Custom Gallery',
        'render_gallery_meta_box',
        'post', // Post type
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'register_gallery_meta_box');

// Render Meta Box Content
function render_gallery_meta_box($post) {
    wp_nonce_field('custom_gallery_nonce', 'custom_gallery_nonce');
    $gallery_ids = get_post_meta($post->ID, '_custom_gallery', true);
    ?>
    <div class="custom-gallery-wrapper">
        <div id="custom-gallery-container">
            <?php
            if (!empty($gallery_ids)) {
                $gallery_ids_array = explode(',', $gallery_ids);
                foreach ($gallery_ids_array as $image_id) {
                    echo wp_get_attachment_image($image_id, 'thumbnail');
                }
            }
            ?>
        </div>
        <input type="hidden" id="custom_gallery_ids" name="custom_gallery_ids" value="<?php echo esc_attr($gallery_ids); ?>">
        <button type="button" class="button" id="custom_gallery_button">Add/Edit Gallery</button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            var customGalleryFrame;
            $('#custom_gallery_button').click(function(e) {
                e.preventDefault();
                
                if (customGalleryFrame) {
                    customGalleryFrame.open();
                    return;
                }

                customGalleryFrame = wp.media({
                    title: 'Select Gallery Images',
                    button: {
                        text: 'Add to gallery'
                    },
                    multiple: true
                });

                customGalleryFrame.on('select', function() {
                    var selection = customGalleryFrame.state().get('selection');
                    var imageIds = [];
                    var container = $('#custom-gallery-container');
                    container.empty();

                    selection.forEach(function(attachment) {
                        imageIds.push(attachment.id);
                        container.append('<img src="' + attachment.attributes.sizes.thumbnail.url + '" />');
                    });

                    $('#custom_gallery_ids').val(imageIds.join(','));
                });

                customGalleryFrame.open();
            });
        });
    </script>
    <style>
        #custom-gallery-container {
            margin: 10px 0;
        }
        #custom-gallery-container img {
            margin: 5px;
            max-width: 150px;
            height: auto;
        }
    </style>
    <?php
}

// Save Meta Box Data
function save_gallery_meta_box($post_id) {
    if (!isset($_POST['custom_gallery_nonce']) || 
        !wp_verify_nonce($_POST['custom_gallery_nonce'], 'custom_gallery_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['custom_gallery_ids'])) {
        update_post_meta($post_id, '_custom_gallery', sanitize_text_field($_POST['custom_gallery_ids']));
    }
}
add_action('save_post', 'save_gallery_meta_box');

// Only load Elementor integration if Elementor is active
function initialize_elementor_integration() {
    // Check if Elementor is installed and activated
    if (!did_action('elementor/loaded')) {
        return;
    }

    // Register the dynamic tag class
    add_action('elementor/dynamic_tags/register', function($dynamic_tags_manager) {
        // Define the Dynamic Tag class inside the callback to ensure Elementor classes exist
        class Custom_Gallery_Tag extends \Elementor\Core\DynamicTags\Data_Tag {
            public function get_name() {
                return 'custom-gallery';
            }

            public function get_title() {
                return 'Custom Gallery';
            }

            public function get_group() {
                return 'post';
            }

            public function get_categories() {
                return ['gallery'];
            }

            protected function get_value(array $options = []) {
                $post_id = get_the_ID();
                $gallery_ids = get_post_meta($post_id, '_custom_gallery', true);
                
                if (empty($gallery_ids)) {
                    return [];
                }

                $gallery_ids_array = explode(',', $gallery_ids);
                $gallery = [];

                foreach ($gallery_ids_array as $image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                    if ($image_url) {
                        $gallery[] = [
                            'id' => $image_id,
                            'url' => $image_url,
                        ];
                    }
                }

                return $gallery;
            }
        }

        // Register the dynamic tag
        $dynamic_tags_manager->register(new Custom_Gallery_Tag());
    });
}

// Initialize Elementor integration after plugins are loaded
add_action('plugins_loaded', 'initialize_elementor_integration');