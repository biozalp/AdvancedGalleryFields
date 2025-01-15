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
    $enabled_post_types = get_option('custom_gallery_post_types', ['post']);
    
    foreach ($enabled_post_types as $post_type) {
        add_meta_box(
            'custom_gallery_field',
            'Custom Gallery',
            'render_gallery_meta_box',
            $post_type,
            'normal',
            'high'
        );
    }
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

// Add Admin Menu
function custom_gallery_admin_menu() {
    add_options_page(
        'Custom Gallery Settings',
        'Custom Gallery',
        'manage_options',
        'custom-gallery-settings',
        'custom_gallery_settings_page'
    );
}
add_action('admin_menu', 'custom_gallery_admin_menu');

// Register Settings
function custom_gallery_register_settings() {
    register_setting('custom_gallery_settings', 'custom_gallery_post_types');
}
add_action('admin_init', 'custom_gallery_register_settings');

// Create Settings Page
function custom_gallery_settings_page() {
    $post_types = get_post_types(['public' => true], 'objects');
    $saved_post_types = get_option('custom_gallery_post_types', ['post']);
    ?>
    <div class="wrap">
        <h1>Custom Gallery Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom_gallery_settings');
            do_settings_sections('custom_gallery_settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable for Post Types</th>
                    <td>
                        <?php foreach ($post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" 
                                       name="custom_gallery_post_types[]" 
                                       value="<?php echo esc_attr($post_type->name); ?>"
                                       <?php checked(in_array($post_type->name, $saved_post_types)); ?>>
                                <?php echo esc_html($post_type->labels->singular_name); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

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