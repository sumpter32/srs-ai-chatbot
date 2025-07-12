<?php
/**
 * Plugin Name: SRS AI ChatBot
 * Plugin URI: https://srsdesignsllc.com/plugins/srs-ai-chatbot
 * Description: A smart, modular, privacy-respecting AI chatbot plugin for WordPress that allows users to deploy, customize, and manage site-native AI assistants with full support for OpenAI, OpenRouter, and Open WebUI APIs.
 * Version: 1.0.0
 * Author: SRS Designs LLC
 * Author URI: https://srsdesignsllc.com
 * Text Domain: srs-ai-chatbot
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 * WC requires at least: 6.0
 * WC tested up to: 8.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SRS_AI_CHATBOT_VERSION', '1.0.0');
define('SRS_AI_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRS_AI_CHATBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SRS_AI_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SRS_AI_CHATBOT_TEXT_DOMAIN', 'srs-ai-chatbot');

/**
 * Main SRS AI ChatBot Plugin Class
 */
class SRS_AI_ChatBot {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Plugin modules
     */
    public $admin;
    public $public;
    public $database;
    public $chatbot_engine;
    public $analytics;
    public $elementor;

    /**
     * Get single instance
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
        $this->load_dependencies();
        $this->init_modules();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('SRS_AI_ChatBot', 'uninstall'));
        
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-database.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-engine.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-api.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-analytics.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-file-handler.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-content-indexer.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-contact-manager.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-woocommerce.php';
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-shortcodes.php';

        // Admin includes
        if (is_admin()) {
            require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/class-srs-ai-chatbot-admin.php';
        }

        // Public includes
        if (!is_admin() || wp_doing_ajax()) {
            require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'public/class-srs-ai-chatbot-public.php';
        }

        // Elementor integration
        if (did_action('elementor/loaded')) {
            $elementor_file = SRS_AI_CHATBOT_PLUGIN_PATH . 'elementor/class-srs-ai-chatbot-elementor.php';
            if (file_exists($elementor_file)) {
                require_once $elementor_file;
            }
        }
    }

    /**
     * Initialize plugin modules
     */
    private function init_modules() {
        $this->database = new SRS_AI_ChatBot_Database();
        $this->chatbot_engine = new SRS_AI_ChatBot_Engine();
        $this->analytics = new SRS_AI_ChatBot_Analytics();

        if (is_admin()) {
            $this->admin = new SRS_AI_ChatBot_Admin();
        }

        if (!is_admin() || wp_doing_ajax()) {
            $this->public = new SRS_AI_ChatBot_Public();
        }

        if (did_action('elementor/loaded')) {
            $elementor_file = SRS_AI_CHATBOT_PLUGIN_PATH . 'elementor/class-srs-ai-chatbot-elementor.php';
            if (file_exists($elementor_file)) {
                $this->elementor = new SRS_AI_ChatBot_Elementor();
            }
        }

        // Initialize shortcodes
        new SRS_AI_ChatBot_Shortcodes();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check minimum requirements
        if (!$this->check_requirements()) {
            return;
        }

        // Load integrations
        $this->load_integrations();
    }

    /**
     * Check plugin requirements
     */
    private function check_requirements() {
        global $wp_version;

        $requirements = array(
            'php_version' => '8.0',
            'wp_version' => '6.0'
        );

        if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
            add_action('admin_notices', function() use ($requirements) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('SRS AI ChatBot requires PHP %s or higher. You are running PHP %s.', 'srs-ai-chatbot'),
                    $requirements['php_version'],
                    PHP_VERSION
                );
                echo '</p></div>';
            });
            return false;
        }

        if (version_compare($wp_version, $requirements['wp_version'], '<')) {
            add_action('admin_notices', function() use ($requirements) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('SRS AI ChatBot requires WordPress %s or higher. You are running WordPress %s.', 'srs-ai-chatbot'),
                    $requirements['wp_version'],
                    $GLOBALS['wp_version']
                );
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * Load integrations
     */
    private function load_integrations() {
        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            new SRS_AI_ChatBot_WooCommerce();
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'srs-ai-chatbot',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (!class_exists('SRS_AI_ChatBot_Database')) {
            require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'includes/class-srs-ai-chatbot-database.php';
        }
        
        $database = new SRS_AI_ChatBot_Database();
        $database->create_tables();

        // Set default options
        $this->set_default_options();

        // Create upload directory
        $this->create_upload_directory();

        // Schedule events
        if (!wp_next_scheduled('srs_ai_chatbot_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'srs_ai_chatbot_daily_cleanup');
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('srs_ai_chatbot_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Only run if user has chosen to delete data
        if (get_option('srs_ai_chatbot_delete_data_on_uninstall', false)) {
            global $wpdb;

            // Drop custom tables
            $tables = array(
                $wpdb->prefix . 'srs_chatbots',
                $wpdb->prefix . 'srs_chat_sessions',
                $wpdb->prefix . 'srs_chat_history',
                $wpdb->prefix . 'srs_contact_info',
                $wpdb->prefix . 'srs_content_index',
                $wpdb->prefix . 'srs_token_usage',
                $wpdb->prefix . 'srs_file_uploads',
                $wpdb->prefix . 'srs_debug_log'
            );

            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
            }

            // Delete options
            $options = array(
                'srs_ai_chatbot_version',
                'srs_ai_chatbot_settings',
                'srs_ai_chatbot_api_settings',
                'srs_ai_chatbot_email_settings',
                'srs_ai_chatbot_file_settings',
                'srs_ai_chatbot_woocommerce_settings',
                'srs_ai_chatbot_training_settings',
                'srs_ai_chatbot_token_settings',
                'srs_ai_chatbot_delete_data_on_uninstall'
            );

            foreach ($options as $option) {
                delete_option($option);
            }

            // Remove upload directory
            $upload_dir = wp_upload_dir();
            $chatbot_dir = $upload_dir['basedir'] . '/srs-ai-chatbot';
            if (is_dir($chatbot_dir)) {
                self::delete_directory($chatbot_dir);
            }
        }
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        // Main settings
        if (!get_option('srs_ai_chatbot_settings')) {
            add_option('srs_ai_chatbot_settings', array(
                'max_memory_messages' => 10,
                'session_timeout' => 3600,
                'enable_file_uploads' => true,
                'enable_contact_capture' => true,
                'enable_analytics' => true
            ));
        }

        // API settings
        if (!get_option('srs_ai_chatbot_api_settings')) {
            add_option('srs_ai_chatbot_api_settings', array(
                'openai_api_key' => '',
                'openrouter_api_key' => '',
                'open_webui_url' => '',
                'open_webui_token' => '',
                'default_model' => 'gpt-3.5-turbo'
            ));
        }

        // Email settings
        if (!get_option('srs_ai_chatbot_email_settings')) {
            add_option('srs_ai_chatbot_email_settings', array(
                'enable_notifications' => false,
                'admin_email' => get_option('admin_email'),
                'notify_on_contact' => true,
                'notify_on_error' => true
            ));
        }

        // File settings
        if (!get_option('srs_ai_chatbot_file_settings')) {
            add_option('srs_ai_chatbot_file_settings', array(
                'allowed_types' => array('pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png'),
                'max_file_size' => 10485760, // 10MB
                'retention_days' => 30
            ));
        }

        // Token pricing settings
        if (!get_option('srs_ai_chatbot_token_settings')) {
            add_option('srs_ai_chatbot_token_settings', array(
                'currency' => 'USD',
                'openai_pricing' => array(
                    'gpt-3.5-turbo' => array('input' => 0.0015, 'output' => 0.002),
                    'gpt-4' => array('input' => 0.03, 'output' => 0.06),
                    'gpt-4o' => array('input' => 0.005, 'output' => 0.015)
                ),
                'openrouter_pricing' => array(
                    'anthropic/claude-3-haiku' => array('input' => 0.00025, 'output' => 0.00125),
                    'anthropic/claude-3-sonnet' => array('input' => 0.003, 'output' => 0.015)
                )
            ));
        }

        // Version
        update_option('srs_ai_chatbot_version', SRS_AI_CHATBOT_VERSION);
    }

    /**
     * Create upload directory
     */
    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $chatbot_dir = $upload_dir['basedir'] . '/srs-ai-chatbot';
        
        if (!file_exists($chatbot_dir)) {
            wp_mkdir_p($chatbot_dir);
            
            // Add .htaccess for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            file_put_contents($chatbot_dir . '/.htaccess', $htaccess_content);
            
            // Add index.php
            file_put_contents($chatbot_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Recursively delete directory
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::delete_directory($path) : unlink($path);
        }
        return rmdir($dir);
    }
}

/**
 * Initialize the plugin
 */
function srs_ai_chatbot() {
    return SRS_AI_ChatBot::get_instance();
}

// Initialize plugin
add_action('plugins_loaded', 'srs_ai_chatbot');

// AJAX endpoints for non-logged-in users
add_action('wp_ajax_nopriv_srs_ai_chatbot_send_message', 'srs_ai_chatbot_ajax_send_message');
add_action('wp_ajax_srs_ai_chatbot_send_message', 'srs_ai_chatbot_ajax_send_message');

function srs_ai_chatbot_ajax_send_message() {
    $chatbot_engine = new SRS_AI_ChatBot_Engine();
    $chatbot_engine->handle_ajax_message();
}

// Daily cleanup hook
add_action('srs_ai_chatbot_daily_cleanup', 'srs_ai_chatbot_daily_cleanup');

function srs_ai_chatbot_daily_cleanup() {
    $analytics = new SRS_AI_ChatBot_Analytics();
    $analytics->cleanup_old_data();
}
