<?php
/**
 * Admin functionality
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('SRS AI ChatBot', 'srs-ai-chatbot'),
            __('AI ChatBot', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot',
            array($this, 'dashboard_page'),
            'dashicons-format-chat',
            30
        );

        // Dashboard
        add_submenu_page(
            'srs-ai-chatbot',
            __('Dashboard', 'srs-ai-chatbot'),
            __('Dashboard', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot',
            array($this, 'dashboard_page')
        );

        // Chatbots
        add_submenu_page(
            'srs-ai-chatbot',
            __('Chatbots', 'srs-ai-chatbot'),
            __('Chatbots', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot-bots',
            array($this, 'chatbots_page')
        );

        // Chat History
        add_submenu_page(
            'srs-ai-chatbot',
            __('Chat History', 'srs-ai-chatbot'),
            __('Chat History', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot-history',
            array($this, 'history_page')
        );

        // Contacts
        add_submenu_page(
            'srs-ai-chatbot',
            __('Contacts', 'srs-ai-chatbot'),
            __('Contacts', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot-contacts',
            array($this, 'contacts_page')
        );

        // Analytics
        add_submenu_page(
            'srs-ai-chatbot',
            __('Analytics', 'srs-ai-chatbot'),
            __('Analytics', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot-analytics',
            array($this, 'analytics_page')
        );

        // Settings
        add_submenu_page(
            'srs-ai-chatbot',
            __('Settings', 'srs-ai-chatbot'),
            __('Settings', 'srs-ai-chatbot'),
            'manage_options',
            'srs-ai-chatbot-settings',
            array($this, 'settings_page')
        );
    }
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'srs-ai-chatbot') === false) {
            return;
        }

        wp_enqueue_style(
            'srs-ai-chatbot-admin',
            SRS_AI_CHATBOT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SRS_AI_CHATBOT_VERSION
        );

        wp_enqueue_script(
            'srs-ai-chatbot-admin',
            SRS_AI_CHATBOT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SRS_AI_CHATBOT_VERSION,
            true
        );

        wp_localize_script('srs-ai-chatbot-admin', 'srs_ai_chatbot_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srs_ai_chatbot_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'srs-ai-chatbot'),
                'testing_connection' => __('Testing connection...', 'srs-ai-chatbot'),
                'connection_successful' => __('Connection successful!', 'srs-ai-chatbot'),
                'connection_failed' => __('Connection failed:', 'srs-ai-chatbot')
            )
        ));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Main settings
        register_setting('srs_ai_chatbot_settings', 'srs_ai_chatbot_settings');
        register_setting('srs_ai_chatbot_api_settings', 'srs_ai_chatbot_api_settings');
        register_setting('srs_ai_chatbot_email_settings', 'srs_ai_chatbot_email_settings');
        register_setting('srs_ai_chatbot_file_settings', 'srs_ai_chatbot_file_settings');
        register_setting('srs_ai_chatbot_woocommerce_settings', 'srs_ai_chatbot_woocommerce_settings');
        register_setting('srs_ai_chatbot_training_settings', 'srs_ai_chatbot_training_settings');
        register_setting('srs_ai_chatbot_token_settings', 'srs_ai_chatbot_token_settings');
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $analytics = new SRS_AI_ChatBot_Analytics();
        $stats = $analytics->get_usage_stats(30);
        $database = new SRS_AI_ChatBot_Database();
        $chatbots = $database->get_active_chatbots();
        
        include SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Chatbots page
     */
    public function chatbots_page() {
        $database = new SRS_AI_ChatBot_Database();
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $this->handle_chatbot_actions();
        }
        
        $chatbots = $database->get_active_chatbots();
        include SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/views/chatbots.php';
    }
    /**
     * Chat history page
     */
    public function history_page() {
        $database = new SRS_AI_ChatBot_Database();
        
        // Get filter parameters
        $chatbot_id = isset($_GET['chatbot_id']) ? intval($_GET['chatbot_id']) : null;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Get sessions with filters
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'srs_chat_sessions';
        $where = array('1=1');
        $values = array();
        
        if ($chatbot_id) {
            $where[] = 'chatbot_id = %d';
            $values[] = $chatbot_id;
        }
        
        if ($date_from) {
            $where[] = 'DATE(started_at) >= %s';
            $values[] = $date_from;
        }
        
        if ($date_to) {
            $where[] = 'DATE(started_at) <= %s';
            $values[] = $date_to;
        }
        
        $sql = "SELECT * FROM $sessions_table WHERE " . implode(' AND ', $where) . " ORDER BY started_at DESC LIMIT 50";
        
        if (!empty($values)) {
            $sessions = $wpdb->get_results($wpdb->prepare($sql, $values));
        } else {
            $sessions = $wpdb->get_results($sql);
        }
        
        $chatbots = $database->get_active_chatbots();
        include SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/views/history.php';
    }

    /**
     * Contacts page
     */
    public function contacts_page() {
        $contact_manager = new SRS_AI_ChatBot_Contact_Manager();
        
        // Get filter parameters
        $filters = array(
            'chatbot_id' => isset($_GET['chatbot_id']) ? intval($_GET['chatbot_id']) : null,
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null,
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : null,
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : null
        );
        
        $contacts = $contact_manager->get_contacts(array_filter($filters));
        $database = new SRS_AI_ChatBot_Database();
        $chatbots = $database->get_active_chatbots();
        
        include SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/views/contacts.php';
    }

    /**
     * Analytics page
     */
    public function analytics_page() {
        $analytics = new SRS_AI_ChatBot_Analytics();
        $database = new SRS_AI_ChatBot_Database();
        
        $period = isset($_GET['period']) ? intval($_GET['period']) : 30;
        $chatbot_id = isset($_GET['chatbot_id']) ? intval($_GET['chatbot_id']) : null;
        
        $stats = $analytics->get_usage_stats($period, $chatbot_id);
        $chart_data = $analytics->get_daily_usage_chart($period, $chatbot_id);
        $token_usage = $analytics->get_token_usage_by_model($period, $chatbot_id);
        $chatbots = $database->get_active_chatbots();
        
        include SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/views/analytics.php';
    }
    /**
     * Settings page
     */
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        // Get current settings
        $api_settings = get_option('srs_ai_chatbot_api_settings', array());
        $email_settings = get_option('srs_ai_chatbot_email_settings', array());
        $file_settings = get_option('srs_ai_chatbot_file_settings', array());
        $training_settings = get_option('srs_ai_chatbot_training_settings', array());
        $token_settings = get_option('srs_ai_chatbot_token_settings', array());
        
        if (class_exists('WooCommerce')) {
            $wc_settings = get_option('srs_ai_chatbot_woocommerce_settings', array());
        }
        
        include SRS_AI_CHATBOT_PLUGIN_PATH . 'admin/views/settings.php';
    }

    /**
     * Handle chatbot actions
     */
    private function handle_chatbot_actions() {
        $action = sanitize_text_field($_GET['action']);
        $id = intval($_GET['id']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'chatbot_action_' . $action . '_' . $id)) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'srs_chatbots';
        
        switch ($action) {
            case 'delete':
                $wpdb->delete($table, array('id' => $id), array('%d'));
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __('Chatbot deleted successfully.', 'srs-ai-chatbot') . '</p></div>';
                });
                break;
                
            case 'activate':
                $wpdb->update($table, array('status' => 'active'), array('id' => $id), array('%s'), array('%d'));
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __('Chatbot activated.', 'srs-ai-chatbot') . '</p></div>';
                });
                break;
                
            case 'deactivate':
                $wpdb->update($table, array('status' => 'inactive'), array('id' => $id), array('%s'), array('%d'));
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __('Chatbot deactivated.', 'srs-ai-chatbot') . '</p></div>';
                });
                break;
        }
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'srs_ai_chatbot_settings')) {
            wp_die('Security check failed');
        }
        
        // API Settings
        if (isset($_POST['api_settings'])) {
            update_option('srs_ai_chatbot_api_settings', $_POST['api_settings']);
        }
        
        // Email Settings
        if (isset($_POST['email_settings'])) {
            update_option('srs_ai_chatbot_email_settings', $_POST['email_settings']);
        }
        
        // File Settings
        if (isset($_POST['file_settings'])) {
            update_option('srs_ai_chatbot_file_settings', $_POST['file_settings']);
        }
        
        // Training Settings
        if (isset($_POST['training_settings'])) {
            update_option('srs_ai_chatbot_training_settings', $_POST['training_settings']);
        }
        
        // Token Settings
        if (isset($_POST['token_settings'])) {
            update_option('srs_ai_chatbot_token_settings', $_POST['token_settings']);
        }
        
        // WooCommerce Settings
        if (isset($_POST['wc_settings'])) {
            update_option('srs_ai_chatbot_woocommerce_settings', $_POST['wc_settings']);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'srs-ai-chatbot') . '</p></div>';
        });
    }
}
