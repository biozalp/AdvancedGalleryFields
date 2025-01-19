<?php
/**
 * The admin-specific functionality of the plugin.
 */

class Advanced_Gallery_Fields_Admin {
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
            'Advanced Gallery Fields Settings',
            'Advanced Gallery Fields',
            'manage_options',
            'advanced-gallery-fields',
            array($this, 'display_settings_page')
        );
    }

    public function register_settings() {
        register_setting(
            'advanced_gallery_settings',
            'advanced_gallery_post_types',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_post_types'),
            )
        );
    }

    /**
     * Sanitize the post types array
     *
     * @param array $input Array of post types to sanitize
     * @return array Sanitized array of post types
     */
    public function sanitize_post_types($input) {
        if (!is_array($input)) {
            return array('post');
        }
        
        return array_map('sanitize_key', $input);
    }

    public function display_settings_page() {
        $post_types = get_post_types(['public' => true], 'objects');
        $saved_post_types = get_option('advanced_gallery_post_types', ['post']);
        ?>
        <div class="wrap">
            <div class="notice-container">
                <?php do_action('admin_notices'); ?>
            </div>
            <div class="cgf-wrap">
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
                            settings_fields('advanced_gallery_settings');
                            do_settings_sections('advanced_gallery_settings');
                            ?>
                            <h2>Enable Gallery Fields</h2>
                            <p class="description">Select which post types should have the custom gallery field available. The gallery field will appear in the editor for the selected post types.</p>
                            
                            <ul class="cgf-post-type-list">
                                <?php foreach ($post_types as $post_type): ?>
                                    <li>
                                        <label>
                                            <input type="checkbox"
                                                   name="advanced_gallery_post_types[]"
                                                   value="<?php echo esc_attr($post_type->name); ?>"
                                                   <?php checked(in_array($post_type->name, $saved_post_types)); ?>>
                                            <span class="dashicons dashicons-<?php echo esc_attr($post_type->name === 'post' ? 'admin-post' : 'admin-page'); ?>"></span>
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
                            <p>Check out the <a href="https://wordpress.org/plugins/advanced-gallery-fields" target="_blank">plugin documentation</a> or <a href="https://wordpress.org/support/plugin/advanced-gallery-fields" target="_blank">support forums</a> for assistance. If you cannot find what you're looking for, please <a href="mailto:berk@biozalp.com" target="_blank">send an email</a>.</p>
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
        </div>
        <?php
    }

    public function register_meta_box() {
        $enabled_post_types = get_option('advanced_gallery_post_types', ['post']);
        
        foreach ($enabled_post_types as $post_type) {
            add_meta_box(
                'advanced_gallery_field',
                'Advanced Gallery Fields',
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('advanced_gallery_nonce', 'advanced_gallery_nonce');
        
        // Sanitize and validate the gallery IDs when retrieving from database
        $gallery_ids = get_post_meta($post->ID, '_advanced_gallery', true);
        $gallery_ids = $this->sanitize_gallery_ids($gallery_ids);
        ?>
        <div class="advanced-gallery-wrapper">
            <input type="hidden" name="gallery_data" id="gallery_data" value="<?php echo esc_attr($gallery_ids); ?>">
            <button type="button" class="button gallery-button">
                <?php esc_html_e('Add/Edit Gallery', 'advanced-gallery-fields'); ?>
            </button>

            <div class="gallery-preview">
                <?php
                if (!empty($gallery_ids)) {
                    $gallery_ids_array = array_filter(explode(',', $gallery_ids), 'is_numeric');
                    foreach ($gallery_ids_array as $image_id) {
                        // Validate that the image ID exists and is an attachment
                        $image_id = absint($image_id);
                        if (!$this->is_valid_attachment($image_id)) {
                            continue;
                        }
                        
                        $image = wp_get_attachment_image_src($image_id, 'thumbnail');
                        if ($image) {
                            echo '<div class="gallery-item" data-id="' . esc_attr($image_id) . '">';
                            echo '<img src="' . esc_url($image[0]) . '" alt="' . esc_attr(get_post_meta($image_id, '_wp_attachment_image_alt', true)) . '">';
                            echo '<div class="delete-image" title="' . esc_attr__('Remove Image', 'advanced-gallery-fields') . '">';
                            echo '<span class="dashicons dashicons-no-alt"></span>';
                            echo '</div></div>';
                        }
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function save_meta_box($post_id) {
        // Verify nonce
        if (!isset($_POST['advanced_gallery_nonce']) || 
            !wp_verify_nonce($_POST['advanced_gallery_nonce'], 'advanced_gallery_nonce')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Sanitize and save gallery data
        if (isset($_POST['gallery_data'])) {
            $gallery_data = $this->sanitize_gallery_ids($_POST['gallery_data']);
            update_post_meta($post_id, '_advanced_gallery', $gallery_data);
        }
    }

    /**
     * Sanitize and validate gallery IDs
     *
     * @param string $gallery_ids Comma-separated list of gallery image IDs
     * @return string Sanitized comma-separated list of gallery image IDs
     */
    private function sanitize_gallery_ids($gallery_ids) {
        if (empty($gallery_ids)) {
            return '';
        }

        // Convert to array and ensure all values are numeric
        $ids_array = array_filter(explode(',', $gallery_ids), 'is_numeric');
        
        // Convert all IDs to positive integers
        $ids_array = array_map('absint', $ids_array);
        
        // Filter out any invalid attachment IDs
        $ids_array = array_filter($ids_array, array($this, 'is_valid_attachment'));

        return implode(',', $ids_array);
    }

    /**
     * Validate if an ID represents a valid attachment
     *
     * @param int $attachment_id The attachment ID to validate
     * @return bool Whether the attachment ID is valid
     */
    private function is_valid_attachment($attachment_id) {
        $attachment = get_post($attachment_id);
        return !empty($attachment) && $attachment->post_type === 'attachment';
    }
}
