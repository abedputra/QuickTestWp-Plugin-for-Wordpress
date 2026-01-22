<?php

/**
 * Plugin Name: QuickTestWP
 * Plugin URI: https://github.com/abedputra/QuickTestWP-Plugin-Wordpress
 * Description: A comprehensive quiz/test plugin with image upload support, progress tracking, and email results.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: Abed Putra
 * Author URI: https://abedputra.my.id
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quicktestwp
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('QUICKTESTWP_VERSION', '1.0.0');
define('QUICKTESTWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUICKTESTWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUICKTESTWP_PLUGIN_FILE', __FILE__);

// Include required files
require_once QUICKTESTWP_PLUGIN_DIR . 'includes/class-quicktestwp-database.php';
require_once QUICKTESTWP_PLUGIN_DIR . 'includes/class-quicktestwp-ajax.php';
require_once QUICKTESTWP_PLUGIN_DIR . 'includes/class-quicktestwp-admin.php';
require_once QUICKTESTWP_PLUGIN_DIR . 'includes/class-quicktestwp-frontend.php';

/**
 * Main QuickTestWP Plugin Class
 */
class QuickTestWP
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        // Activation and deactivation hooks
        register_activation_hook(QUICKTESTWP_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(QUICKTESTWP_PLUGIN_FILE, array($this, 'deactivate'));

        // Initialize AJAX handlers (once for both admin and frontend)
        add_action('init', array($this, 'init_ajax'));

        // Upgrade database on plugins_loaded (runs every time, but checks if columns exist)
        add_action('plugins_loaded', array($this, 'upgrade_database_check'), 1);

        // Initialize admin early (before admin_menu hook which runs at priority 10)
        add_action('plugins_loaded', array($this, 'init_admin'), 5);

        // Initialize frontend
        add_action('init', array($this, 'init_frontend'));
    }

    public function init_ajax()
    {
        static $ajax_initialized = false;
        if (!$ajax_initialized) {
            new QuickTestWP_Ajax();
            $ajax_initialized = true;
        }
    }

    public function activate()
    {
        QuickTestWP_Database::create_tables();
        QuickTestWP_Database::upgrade_database();
        flush_rewrite_rules();
    }

    public function upgrade_database_check()
    {
        // Run database upgrade check (safe to run multiple times)
        QuickTestWP_Database::upgrade_database();
    }

    public function init_admin()
    {
        static $admin_initialized = false;
        if (!$admin_initialized && is_admin()) {
            new QuickTestWP_Admin();
            $admin_initialized = true;
        }
    }

    public function init_frontend()
    {
        static $frontend_initialized = false;
        if (!$frontend_initialized && !is_admin()) {
            new QuickTestWP_Frontend();
            $frontend_initialized = true;
        }
    }

    public function deactivate()
    {
        // Cleanup if needed
    }
}

// Initialize the plugin
QuickTestWP::get_instance();
