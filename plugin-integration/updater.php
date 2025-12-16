<?php
/**
 * ASMAK Plugin Updater Class
 * 
 * Integrates with GitHub Pages update server for automatic plugin updates
 * 
 * Usage:
 * Add this to your main plugin file:
 * 
 * require_once plugin_dir_path(__FILE__) . 'includes/updater.php';
 * 
 * if (is_admin()) {
 *     $updater = new ASMAK_Plugin_Updater(__FILE__);
 *     $updater->set_update_url('https://adsguru22.github.io/asmak-updates/info.json');
 *     $updater->initialize();
 * }
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ASMAK_Plugin_Updater {
    
    /**
     * Plugin file path
     * @var string
     */
    private $plugin_file;
    
    /**
     * Plugin slug
     * @var string
     */
    private $plugin_slug;
    
    /**
     * Update server URL
     * @var string
     */
    private $update_url;
    
    /**
     * Plugin data
     * @var array
     */
    private $plugin_data;
    
    /**
     * Cache expiration time in seconds (12 hours)
     * @var int
     */
    private $cache_expiration = 43200;
    
    /**
     * Constructor
     * 
     * @param string $plugin_file Full path to the main plugin file
     */
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $this->plugin_data = get_plugin_data($plugin_file);
    }
    
    /**
     * Set update URL
     * 
     * @param string $url URL to the info.json file
     */
    public function set_update_url($url) {
        $this->update_url = $url;
    }
    
    /**
     * Initialize the updater
     */
    public function initialize() {
        if (empty($this->update_url)) {
            return;
        }
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }
    
    /**
     * Check for plugin updates
     * 
     * @param object $transient WordPress update transient
     * @return object Modified transient
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $remote_info = $this->get_remote_info();
        
        if ($remote_info && version_compare($this->plugin_data['Version'], $remote_info->version, '<')) {
            $plugin_info = new stdClass();
            $plugin_info->slug = $this->get_plugin_slug();
            $plugin_info->new_version = $remote_info->version;
            $plugin_info->url = $remote_info->author_profile;
            $plugin_info->package = $remote_info->download_url;
            $plugin_info->tested = $remote_info->tested;
            $plugin_info->requires = $remote_info->requires;
            $plugin_info->requires_php = $remote_info->requires_php;
            
            $transient->response[$this->plugin_slug] = $plugin_info;
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for the update details page
     * 
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return false|object
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if ($args->slug !== $this->get_plugin_slug()) {
            return $result;
        }
        
        $remote_info = $this->get_remote_info();
        
        if (!$remote_info) {
            return $result;
        }
        
        $plugin_info = new stdClass();
        $plugin_info->name = $remote_info->name;
        $plugin_info->slug = $remote_info->slug;
        $plugin_info->version = $remote_info->version;
        $plugin_info->author = $remote_info->author;
        $plugin_info->author_profile = $remote_info->author_profile;
        $plugin_info->requires = $remote_info->requires;
        $plugin_info->tested = $remote_info->tested;
        $plugin_info->requires_php = $remote_info->requires_php;
        $plugin_info->download_link = $remote_info->download_url;
        $plugin_info->last_updated = $remote_info->last_updated;
        $plugin_info->sections = (array) $remote_info->sections;
        
        if (isset($remote_info->banners)) {
            $plugin_info->banners = (array) $remote_info->banners;
        }
        
        if (isset($remote_info->icons)) {
            $plugin_info->icons = (array) $remote_info->icons;
        }
        
        return $plugin_info;
    }
    
    /**
     * Post-installation hook
     * 
     * @param bool $response
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->plugin_file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        if ($this->is_plugin_active()) {
            activate_plugin($this->plugin_slug);
        }
        
        return $result;
    }
    
    /**
     * Get remote plugin information
     * 
     * @return object|false Remote plugin info or false on failure
     */
    private function get_remote_info() {
        $cache_key = 'asmak_plugin_update_' . md5($this->update_url);
        $remote_info = get_transient($cache_key);
        
        if ($remote_info !== false) {
            return $remote_info;
        }
        
        $response = wp_remote_get($this->update_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $remote_info = json_decode($body);
        
        if (!$remote_info) {
            return false;
        }
        
        set_transient($cache_key, $remote_info, $this->cache_expiration);
        
        return $remote_info;
    }
    
    /**
     * Get plugin slug from file path
     * 
     * @return string Plugin slug
     */
    private function get_plugin_slug() {
        $parts = explode('/', $this->plugin_slug);
        return str_replace('.php', '', end($parts));
    }
    
    /**
     * Check if plugin is active
     * 
     * @return bool
     */
    private function is_plugin_active() {
        return in_array($this->plugin_slug, get_option('active_plugins', array()));
    }
}
