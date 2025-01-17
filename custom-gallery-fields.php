<?php
/**
 * @wordpress-plugin
 * Plugin Name: Custom Gallery Fields
 * Description: Adds a custom gallery field to posts and makes it available to use for editors like Elementor and Bloksy.
 * Version: 1.0.0
 * Author: Berk Ilgar Ozalp
 * Author URI: https://biozalp.com/wordpress-plugins
 * License: GPL-2.0+	 
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

 if (!defined('ABSPATH')) {
    exit;
}

/**
 * Currently plugin version.
 */
define('CUSTOM_GALLERY_FIELDS_VERSION', '1.0.0');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-custom-gallery-fields.php';

/**
 * Begins execution of the plugin.
 */
function run_custom_gallery_fields() {
    $plugin = new Custom_Gallery_Fields();
}
run_custom_gallery_fields();

/**
 * Check if Elementor is installed and activated
 */
function is_elementor_activated() {
    return did_action('elementor/loaded');
}

/**
 * Initialize Elementor integration
 */
function initialize_elementor_integration() {
    // If Elementor is not active, return early
    if (!is_elementor_activated()) {
        return;
    }

    // Register Dynamic Tag
    add_action('elementor/dynamic_tags/register', function($dynamic_tags) {
        class Gallery_Dynamic_Tag extends \Elementor\Core\DynamicTags\Data_Tag {
            public function get_name() {
                return 'custom-gallery-field';
            }
        
            public function get_title() {
                return __('Custom Gallery Field', 'custom-gallery-fields');
            }
        
            public function get_group() {
                return 'media';
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
                $gallery_data = [];
                
                foreach ($gallery_ids_array as $attachment_id) {
                    $attachment = get_post($attachment_id);
                    if ($attachment) {
                        $gallery_data[] = [
                            'id' => (int)$attachment_id,
                            'url' => wp_get_attachment_url($attachment_id),
                            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                            'caption' => $attachment->post_excerpt,
                            'description' => $attachment->post_content,
                            'title' => $attachment->post_title
                        ];
                    }
                }
                
                return $gallery_data;
            }
        }
        
        $dynamic_tags->register(new Gallery_Dynamic_Tag());
    });

    // Make sure the Elementor classes are available
    if (!class_exists('\Elementor\Widget_Base')) {
        return;
    }

    /**
     * Custom Gallery Fields Widget for Elementor
     */
    class Custom_Gallery_Fields_Widget extends \Elementor\Widget_Base {
        public function get_name() {
            return 'custom_gallery_fields';
        }

        public function get_title() {
            return __('Custom Gallery Fields', 'custom-gallery-fields');
        }

        public function get_icon() {
            return 'eicon-gallery-grid';
        }

        public function get_categories() {
            return ['general'];
        }

        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Content', 'custom-gallery-fields'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'gallery_style',
                [
                    'label' => __('Gallery Style', 'custom-gallery-fields'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'grid',
                    'options' => [
                        'grid' => __('Grid', 'custom-gallery-fields'),
                        'masonry' => __('Masonry', 'custom-gallery-fields'),
                        'carousel' => __('Carousel', 'custom-gallery-fields'),
                    ],
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $post_id = get_the_ID();
            $gallery_ids = get_post_meta($post_id, '_custom_gallery', true);

            if (empty($gallery_ids)) {
                return;
            }

            $gallery_ids_array = explode(',', $gallery_ids);
            echo '<div class="custom-gallery ' . esc_attr($settings['gallery_style']) . '">';
            foreach ($gallery_ids_array as $image_id) {
                echo wp_get_attachment_image($image_id, 'large');
            }
            echo '</div>';
        }
    }

    // Register widget with Elementor
    add_action('elementor/widgets/register', function($widgets_manager) {
        $widgets_manager->register(new Custom_Gallery_Fields_Widget());
    });
}

// Initialize Elementor integration after Elementor is loaded
add_action('plugins_loaded', 'initialize_elementor_integration');

/**
 * Add settings link to plugin page
 */
function custom_gallery_fields_plugin_action_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('tools.php?page=custom-gallery-fields') . '">' . __('Settings', 'custom-gallery-fields') . '</a>'
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'custom_gallery_fields_plugin_action_links');

/**
 * Add More Plugins link to plugin meta
 */
function custom_gallery_fields_plugin_row_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = array(
            'more_plugins' => '<a href="https://biozalp.com/wordpress-plugins" target="_blank">' . __('More Plugins', 'custom-gallery-fields') . '</a>'
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'custom_gallery_fields_plugin_row_meta', 10, 2);