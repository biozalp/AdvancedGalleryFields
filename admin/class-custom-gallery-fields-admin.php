<?php
/**
 * The admin-specific functionality of the plugin.
 */

class Custom_Gallery_Fields_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/gallery-admin.css',
            array(),
            $this->version,
            'all'
        );

        // Add Dashicons for feature icons
        wp_enqueue_style('dashicons');
    }

    public function enqueue_scripts() {
        wp_enqueue_media();
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/gallery-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    public function add_admin_menu() {
        add_management_page(
            'Custom Gallery Fields Settings',
            'Custom Gallery Fields',
            'manage_options',
            'custom-gallery-fields',
            array($this, 'display_settings_page')
        );
    }

    public function register_settings() {
        register_setting('custom_gallery_settings', 'custom_gallery_post_types');
    }

    public function display_settings_page() {
        $post_types = get_post_types(['public' => true], 'objects');
        $saved_post_types = get_option('custom_gallery_post_types', ['post']);
        ?>
        <div class="wrap cgf-wrap">
            <div class="cgf-admin-header">
                <div>
                    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                    <p style="margin: 10px 0 0; opacity: 0.9;">Enhance your content with beautiful, customizable galleries</p>
                </div>
            </div>

            <div class="cgf-admin-container">
                <div class="cgf-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('custom_gallery_settings');
                        do_settings_sections('custom_gallery_settings');
                        ?>
                        <h2>Enable Gallery Fields</h2>
                        <p class="description">Select which post types should have the custom gallery field available. The gallery field will appear in the editor for the selected post types.</p>
                        
                        <ul class="cgf-post-type-list">
                            <?php foreach ($post_types as $post_type): ?>
                                <li>
                                    <label>
                                        <input type="checkbox"
                                               name="custom_gallery_post_types[]"
                                               value="<?php echo esc_attr($post_type->name); ?>"
                                               <?php checked(in_array($post_type->name, $saved_post_types)); ?>>
                                        <span class="dashicons dashicons-<?php echo $post_type->name === 'post' ? 'admin-post' : 'admin-page'; ?>"></span>
                                        <span><?php echo esc_html($post_type->labels->singular_name); ?></span>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php submit_button('Save Settings'); ?>
                    </form>
                </div>

                <div class="cgf-admin-sidebar">
                    
                    <h3>Support the Development</h3>
                    <p>If you find this plugin useful, consider supporting its development. Your support helps maintain and improve the plugin with new features and updates!</p>
                    
                    <a href="https://www.buymeacoffee.com/biozalp" target="_blank" class="cgf-coffee-button">
                        <span class="dashicons dashicons-coffee"></span>
                        Buy me a coffee
                    </a>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                        <h3>Need Help?</h3>
                        <p>Check out the <a href="https://wordpress.org/plugins/custom-gallery-fields" target="_blank">plugin documentation</a> or <a href="https://wordpress.org/support/plugin/custom-gallery-fields" target="_blank">support forums</a> for assistance. If you cannot find what you're looking for, please <a href="mailto:berk@biozalp.com" target="_blank">send an email</a>.</p>
                    </div>

                    <div class="cgf-plugin-recommendation">
                        <h3>
                            Check Out My Other Plugins
                        </h3>
                        <p>Love Spotify? Try Spotiembed, my plugin that lets you easily embed Spotify content in your WordPress posts and pages.</p>
                        <a href="https://wordpress.com/plugins/spotiembed" target="_blank" class="button">
                            <span class="dashicons dashicons-external" style="margin: 3px 5px 0 -2px;"></span>
                            View Spotiembed Plugin
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function register_meta_box() {
        $enabled_post_types = get_option('custom_gallery_post_types', ['post']);
        
        foreach ($enabled_post_types as $post_type) {
            add_meta_box(
                'custom_gallery_field',
                'Custom Gallery Fields',
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('custom_gallery_nonce', 'custom_gallery_nonce');

        // Get current gallery
        $gallery_ids = get_post_meta($post->ID, '_custom_gallery', true);
        ?>
        <div class="gallery-field-container">
            <div class="gallery-buttons">
                <button type="button" class="button gallery-button">
                    <?php _e('Add/Edit Gallery', 'custom-gallery-fields'); ?>
                </button>
                <button type="button" class="clear-gallery" style="display: <?php echo empty($gallery_ids) ? 'none' : 'block'; ?>">
                    <?php _e('Clear Gallery', 'custom-gallery-fields'); ?>
                </button>
            </div>

            <div class="gallery-preview">
                <?php
                if (!empty($gallery_ids)) {
                    $gallery_ids_array = explode(',', $gallery_ids);
                    foreach ($gallery_ids_array as $image_id) {
                        $image = wp_get_attachment_image_src($image_id, 'thumbnail');
                        if ($image) {
                            echo '<div class="gallery-item" data-id="' . esc_attr($image_id) . '">';
                            echo '<img src="' . esc_url($image[0]) . '" alt="">';
                            echo '<div class="delete-image" title="' . esc_attr__('Remove Image', 'custom-gallery-fields') . '">';
                            echo '<span class="dashicons dashicons-no-alt"></span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            <input type="hidden" id="gallery_data" name="gallery_data" value="<?php echo esc_attr($gallery_ids); ?>">
        </div>
        <?php
    }

    public function save_meta_box($post_id) {
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

        if (isset($_POST['gallery_data'])) {
            update_post_meta($post_id, '_custom_gallery', sanitize_text_field($_POST['gallery_data']));
        }
    }
}
