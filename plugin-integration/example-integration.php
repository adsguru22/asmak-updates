<?php
/**
 * Example: ASMAK Command Center Plugin Main File
 * 
 * This is an example of how to integrate the ASMAK updater
 * into your WordPress plugin.
 * 
 * @package ASMAK_Command_Center
 * @version 1.2.0
 */

/**
 * Plugin Name: ASMAK Command Center
 * Plugin URI: https://github.com/adsguru22/asmak-updates
 * Description: Command Center untuk integrasi berbagai API eksternal dengan 11 modul, 42+ API endpoints, dan 6+ integrasi layanan
 * Version: 1.2.0
 * Author: ASMAK Team
 * Author URI: https://github.com/adsguru22
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.4
 * Text Domain: asmak-command-center
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ASMAK_CC_VERSION', '1.2.0');
define('ASMAK_CC_PLUGIN_FILE', __FILE__);
define('ASMAK_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASMAK_CC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASMAK_CC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Load Plugin Updater
 * 
 * IMPORTANT: This is the auto-update integration
 */
require_once ASMAK_CC_PLUGIN_DIR . 'includes/updater.php';

/**
 * Initialize Auto-Updater (Admin only)
 * 
 * The updater will:
 * - Check for updates from GitHub Pages
 * - Show update notifications in WordPress admin
 * - Handle automatic updates when user clicks "Update"
 */
if (is_admin()) {
    $asmak_updater = new ASMAK_Plugin_Updater(ASMAK_CC_PLUGIN_FILE);
    $asmak_updater->set_update_url('https://adsguru22.github.io/asmak-updates/info.json');
    $asmak_updater->initialize();
}

/**
 * Main Plugin Class
 */
class ASMAK_Command_Center {
    
    /**
     * Plugin instance
     * @var ASMAK_Command_Center
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     * 
     * @return ASMAK_Command_Center
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation hook
        register_activation_hook(ASMAK_CC_PLUGIN_FILE, array($this, 'activate'));
        
        // Deactivation hook
        register_deactivation_hook(ASMAK_CC_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        add_option('asmak_cc_version', ASMAK_CC_VERSION);
        add_option('asmak_cc_activated', current_time('mysql'));
        
        // Flush rewrite rules if needed
        flush_rewrite_rules();
        
        // Log activation
        error_log('ASMAK Command Center activated - Version ' . ASMAK_CC_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('ASMAK Command Center deactivated');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Your plugin initialization code here
        
        // Example: Load modules
        // $this->load_modules();
        
        // Example: Register REST API endpoints
        // $this->register_rest_routes();
        
        // Example: Initialize integrations
        // $this->init_integrations();
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'asmak-command-center',
            false,
            dirname(ASMAK_CC_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ASMAK Command Center', 'asmak-command-center'),
            __('ASMAK CC', 'asmak-command-center'),
            'manage_options',
            'asmak-command-center',
            array($this, 'admin_page'),
            'dashicons-networking',
            30
        );
        
        // Add submenu pages
        add_submenu_page(
            'asmak-command-center',
            __('Settings', 'asmak-command-center'),
            __('Settings', 'asmak-command-center'),
            'manage_options',
            'asmak-cc-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('ASMAK Command Center', 'asmak-command-center'); ?></h1>
            <p><?php _e('Version:', 'asmak-command-center'); ?> <?php echo ASMAK_CC_VERSION; ?></p>
            <div class="card">
                <h2><?php _e('Plugin Stats', 'asmak-command-center'); ?></h2>
                <ul>
                    <li><strong>11</strong> <?php _e('Modules', 'asmak-command-center'); ?></li>
                    <li><strong>42+</strong> <?php _e('API Endpoints', 'asmak-command-center'); ?></li>
                    <li><strong>6+</strong> <?php _e('Integrations', 'asmak-command-center'); ?></li>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('Auto-Update Status', 'asmak-command-center'); ?></h2>
                <p>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    <?php _e('Auto-update enabled via GitHub Pages', 'asmak-command-center'); ?>
                </p>
                <p>
                    <?php _e('Update server:', 'asmak-command-center'); ?> 
                    <code>https://adsguru22.github.io/asmak-updates/info.json</code>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('ASMAK Command Center Settings', 'asmak-command-center'); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Add your settings fields here
                settings_fields('asmak_cc_settings');
                do_settings_sections('asmak_cc_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check if just activated
        if (get_transient('asmak_cc_activation_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('ASMAK Command Center', 'asmak-command-center'); ?></strong>
                    <?php _e('has been activated successfully!', 'asmak-command-center'); ?>
                </p>
            </div>
            <?php
            delete_transient('asmak_cc_activation_notice');
        }
    }
}

/**
 * Initialize the plugin
 */
function asmak_command_center() {
    return ASMAK_Command_Center::get_instance();
}

// Start the plugin
asmak_command_center();
