<?php
/**
 * The core plugin class.
 */

class Advanced_Gallery_Fields {
    protected $plugin_name;
    protected $version;
    protected $admin;

    public function __construct() {
        $this->plugin_name = 'advanced-gallery-fields';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-advanced-gallery-fields-admin.php';
    }

    private function define_admin_hooks() {
        $this->admin = new Advanced_Gallery_Fields_Admin($this->plugin_name, $this->version);

        // Admin menu and settings
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_init', array($this->admin, 'register_settings'));

        // Meta box
        add_action('add_meta_boxes', array($this->admin, 'register_meta_box'));
        add_action('save_post', array($this->admin, 'save_meta_box'));

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
