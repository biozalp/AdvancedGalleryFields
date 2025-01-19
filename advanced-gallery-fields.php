<?php
/**
 * @wordpress-plugin
 * Plugin Name: Advanced Gallery Fields
 * Description: Adds an advanced gallery field to posts and makes it available to use for editors like Elementor.
 * Version: 1.0.0
 * Author: Berk Ilgar Ozalp
 * Author URI: https://biozalp.com/
 * License: GPL-2.0+	 
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('ADVANCED_GALLERY_FIELDS_VERSION', '1.0.0');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-advanced-gallery-fields.php';

/**
 * Initialize the plugin
 */
function advanced_gallery_fields_init() {
    $plugin = new Advanced_Gallery_Fields();
    $plugin->run();
}
advanced_gallery_fields_init();

/**
 * Check if Elementor is installed and activated
 *
 * @return bool
 */
function advanced_gallery_fields_has_elementor() {
    return did_action('elementor/loaded');
}

/**
 * Initialize Elementor integration
 */
function advanced_gallery_fields_elementor_init() {
    // If Elementor is not active, return early
    if (!advanced_gallery_fields_has_elementor()) {
        return;
    }

    // Register Dynamic Tag
    add_action('elementor/dynamic_tags/register', function($dynamic_tags) {
        /**
         * Dynamic tag for Elementor integration
         */
        class Advanced_Gallery_Fields_Dynamic_Tag extends \Elementor\Core\DynamicTags\Data_Tag {
            public function get_name() {
                return 'advanced-gallery-field';
            }
        
            public function get_title() {
                return __('Advanced Gallery Field', 'advanced-gallery-fields');
            }
        
            public function get_group() {
                return 'media';
            }
        
            public function get_categories() {
                return ['gallery'];
            }
        
            protected function get_value(array $options = []) {
                $post_id = get_the_ID();
                $gallery_ids = get_post_meta($post_id, '_advanced_gallery', true);
                
                if (empty($gallery_ids)) {
                    return [];
                }
                
                $gallery_ids_array = explode(',', $gallery_ids);
                $gallery_data = [];
                
                foreach ($gallery_ids_array as $attachment_id) {
                    $attachment = get_post($attachment_id);
                    if ($attachment) {
                        $gallery_data[] = [
                            'id' => $attachment_id,
                            'url' => wp_get_attachment_url($attachment_id),
                        ];
                    }
                }
                
                return $gallery_data;
            }
        }
        
        $dynamic_tags->register(new Advanced_Gallery_Fields_Dynamic_Tag());
    });

    // Make sure the Elementor classes are available
    if (!class_exists('\Elementor\Widget_Base')) {
        return;
    }

    /**
     * Advanced Gallery Fields Widget for Elementor
     */
    class Advanced_Gallery_Fields_Widget extends \Elementor\Widget_Base {
        public function get_name() {
            return 'advanced-gallery-fields';
        }

        public function get_title() {
            return __('Advanced Gallery Fields', 'advanced-gallery-fields');
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
                    'label' => __('Content', 'advanced-gallery-fields'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'gallery_style',
                [
                    'label' => __('Gallery Style', 'advanced-gallery-fields'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'grid',
                    'options' => [
                        'grid' => __('Grid', 'advanced-gallery-fields'),
                        'masonry' => __('Masonry', 'advanced-gallery-fields'),
                        'carousel' => __('Carousel', 'advanced-gallery-fields'),
                    ],
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $post_id = get_the_ID();
            $gallery_ids = get_post_meta($post_id, '_advanced_gallery', true);

            if (empty($gallery_ids)) {
                return;
            }

            // Sanitize and validate gallery IDs
            $gallery_ids_array = array_filter(explode(',', $gallery_ids), 'is_numeric');
            $gallery_ids_array = array_map('absint', $gallery_ids_array);

            echo '<div class="advanced-gallery ' . esc_attr($settings['gallery_style']) . '">';
            foreach ($gallery_ids_array as $image_id) {
                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                $image_html = wp_get_attachment_image($image_id, 'large', false, array('alt' => esc_attr($image_alt)));
                if ($image_html) {
                    echo wp_kses_post($image_html);
                }
            }
            echo '</div>';
        }
    }

    // Register widget with Elementor
    add_action('elementor/widgets/register', function($widgets_manager) {
        $widgets_manager->register(new Advanced_Gallery_Fields_Widget());
    });
}

// Initialize Elementor integration after Elementor is loaded
add_action('plugins_loaded', 'advanced_gallery_fields_elementor_init');

/**
 * Add settings link to plugin page
 *
 * @param array $links Existing plugin action links
 * @return array Modified plugin action links
 */
function advanced_gallery_fields_add_action_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('tools.php?page=advanced-gallery-fields') . '">' . __('Settings', 'advanced-gallery-fields') . '</a>'
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'advanced_gallery_fields_add_action_links');

/**
 * Add More Plugins link to plugin meta
 *
 * @param array $links Existing plugin row meta
 * @param string $file Plugin base file
 * @return array Modified plugin row meta
 */
function advanced_gallery_fields_add_row_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = array(
            'more_plugins' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url('https://biozalp.com/wordpress-plugins'),
                esc_html__('More Plugins', 'advanced-gallery-fields')
            )
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'advanced_gallery_fields_add_row_meta', 10, 2);