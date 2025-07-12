<?php
/**
 * Database management for SRS AI ChatBot
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Database {

    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'check_database_version'));
    }

    /**
     * Check if database needs updating
     */
    public function check_database_version() {
        $installed_version = get_option('srs_ai_chatbot_db_version', '0.0.0');
        
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->create_tables();
            update_option('srs_ai_chatbot_db_version', self::DB_VERSION);
        }
    }

    /**
     * Create all database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Chatbots table
        $table_chatbots = $wpdb->prefix . 'srs_chatbots';
        $sql_chatbots = "CREATE TABLE $table_chatbots (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL UNIQUE,
            system_prompt text DEFAULT NULL,
            greeting_message text DEFAULT NULL,
            model varchar(100) DEFAULT 'gpt-3.5-turbo',
            api_provider varchar(50) DEFAULT 'openai',
            avatar_url varchar(500) DEFAULT NULL,
            max_memory_messages int(11) DEFAULT 10,
            temperature decimal(3,2) DEFAULT 0.70,
            max_tokens int(11) DEFAULT 1000,
            status enum('active','inactive') DEFAULT 'active',
            created_by int(11) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_slug (slug),
            KEY idx_status (status),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        // Chat sessions table
        $table_sessions = $wpdb->prefix . 'srs_chat_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL UNIQUE,
            chatbot_id int(11) NOT NULL,
            user_id int(11) DEFAULT NULL,
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            page_url varchar(500) DEFAULT NULL,
            status enum('active','completed','abandoned') DEFAULT 'active',
            started_at timestamp DEFAULT CURRENT_TIMESTAMP,
            last_activity timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ended_at timestamp NULL DEFAULT NULL,
            total_messages int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            total_cost decimal(10,6) DEFAULT 0.000000,
            metadata json DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_session_id (session_id),
            KEY idx_chatbot_id (chatbot_id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_started_at (started_at),
            FOREIGN KEY (chatbot_id) REFERENCES $table_chatbots(id) ON DELETE CASCADE
        ) $charset_collate;";
        // Chat history table
        $table_history = $wpdb->prefix . 'srs_chat_history';
        $sql_history = "CREATE TABLE $table_history (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            chatbot_id int(11) NOT NULL,
            message_type enum('user','assistant','system') NOT NULL,
            message text NOT NULL,
            response text DEFAULT NULL,
            input_tokens int(11) DEFAULT 0,
            output_tokens int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            cost decimal(10,6) DEFAULT 0.000000,
            model_used varchar(100) DEFAULT NULL,
            response_time decimal(8,3) DEFAULT NULL,
            error_message text DEFAULT NULL,
            attachments json DEFAULT NULL,
            timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_session_id (session_id),
            KEY idx_chatbot_id (chatbot_id),
            KEY idx_message_type (message_type),
            KEY idx_timestamp (timestamp),
            FOREIGN KEY (chatbot_id) REFERENCES $table_chatbots(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Contact information table
        $table_contacts = $wpdb->prefix . 'srs_contact_info';
        $sql_contacts = "CREATE TABLE $table_contacts (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            chatbot_id int(11) NOT NULL,
            name varchar(255) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            company varchar(255) DEFAULT NULL,
            message text DEFAULT NULL,
            source enum('chat','form','api') DEFAULT 'chat',
            status enum('new','contacted','converted','archived') DEFAULT 'new',
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            page_url varchar(500) DEFAULT NULL,
            additional_data json DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_session_id (session_id),
            KEY idx_chatbot_id (chatbot_id),
            KEY idx_email (email),
            KEY idx_status (status),
            KEY idx_created_at (created_at),
            FOREIGN KEY (chatbot_id) REFERENCES $table_chatbots(id) ON DELETE CASCADE
        ) $charset_collate;";
        // Content index table
        $table_content = $wpdb->prefix . 'srs_content_index';
        $sql_content = "CREATE TABLE $table_content (
            id int(11) NOT NULL AUTO_INCREMENT,
            content_id int(11) NOT NULL,
            content_type varchar(50) NOT NULL,
            title varchar(500) DEFAULT NULL,
            content text NOT NULL,
            content_hash varchar(64) NOT NULL,
            post_type varchar(50) DEFAULT NULL,
            post_status varchar(20) DEFAULT NULL,
            author_id int(11) DEFAULT NULL,
            excerpt text DEFAULT NULL,
            metadata json DEFAULT NULL,
            embedding_vector json DEFAULT NULL,
            last_indexed timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_content_hash (content_hash),
            KEY idx_content_id (content_id),
            KEY idx_content_type (content_type),
            KEY idx_post_type (post_type),
            KEY idx_last_indexed (last_indexed)
        ) $charset_collate;";

        // Token usage table
        $table_tokens = $wpdb->prefix . 'srs_token_usage';
        $sql_tokens = "CREATE TABLE $table_tokens (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            chatbot_id int(11) NOT NULL,
            model varchar(100) NOT NULL,
            api_provider varchar(50) NOT NULL,
            input_tokens int(11) DEFAULT 0,
            output_tokens int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            cost decimal(10,6) DEFAULT 0.000000,
            currency varchar(3) DEFAULT 'USD',
            request_type enum('chat','embedding','image','audio') DEFAULT 'chat',
            response_time decimal(8,3) DEFAULT NULL,
            success boolean DEFAULT true,
            error_message text DEFAULT NULL,
            date_created date NOT NULL,
            timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_session_id (session_id),
            KEY idx_chatbot_id (chatbot_id),
            KEY idx_model (model),
            KEY idx_date_created (date_created),
            KEY idx_timestamp (timestamp),
            FOREIGN KEY (chatbot_id) REFERENCES $table_chatbots(id) ON DELETE CASCADE
        ) $charset_collate;";
        // File uploads table
        $table_files = $wpdb->prefix . 'srs_file_uploads';
        $sql_files = "CREATE TABLE $table_files (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            chatbot_id int(11) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL,
            file_type varchar(100) NOT NULL,
            mime_type varchar(100) NOT NULL,
            file_hash varchar(64) NOT NULL,
            processed boolean DEFAULT false,
            extracted_text longtext DEFAULT NULL,
            metadata json DEFAULT NULL,
            expires_at timestamp NULL DEFAULT NULL,
            uploaded_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_session_id (session_id),
            KEY idx_chatbot_id (chatbot_id),
            KEY idx_file_hash (file_hash),
            KEY idx_expires_at (expires_at),
            KEY idx_uploaded_at (uploaded_at),
            FOREIGN KEY (chatbot_id) REFERENCES $table_chatbots(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Debug log table
        $table_debug = $wpdb->prefix . 'srs_debug_log';
        $sql_debug = "CREATE TABLE $table_debug (
            id int(11) NOT NULL AUTO_INCREMENT,
            level enum('info','warning','error','debug') NOT NULL,
            component varchar(100) NOT NULL,
            message text NOT NULL,
            context json DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            chatbot_id int(11) DEFAULT NULL,
            user_id int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            stack_trace text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_level (level),
            KEY idx_component (component),
            KEY idx_session_id (session_id),
            KEY idx_chatbot_id (chatbot_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_chatbots);
        dbDelta($sql_sessions);
        dbDelta($sql_history);
        dbDelta($sql_contacts);
        dbDelta($sql_content);
        dbDelta($sql_tokens);
        dbDelta($sql_files);
        dbDelta($sql_debug);

        // Create default chatbot if none exists
        $this->create_default_chatbot();
    }

    /**
     * Create default chatbot
     */
    private function create_default_chatbot() {
        global $wpdb;

        $table_chatbots = $wpdb->prefix . 'srs_chatbots';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_chatbots");

        if ($count == 0) {
            $wpdb->insert(
                $table_chatbots,
                array(
                    'name' => __('Default Assistant', 'srs-ai-chatbot'),
                    'slug' => 'default-assistant',
                    'system_prompt' => __('You are a helpful AI assistant for this website. Provide accurate and helpful information to users. Be friendly, professional, and concise in your responses.', 'srs-ai-chatbot'),
                    'greeting_message' => __('Hello! I\'m here to help you. How can I assist you today?', 'srs-ai-chatbot'),
                    'model' => 'gpt-3.5-turbo',
                    'api_provider' => 'openai',
                    'created_by' => get_current_user_id()
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
    }
    /**
     * Get chatbot by ID
     */
    public function get_chatbot($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chatbots';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get chatbot by slug
     */
    public function get_chatbot_by_slug($slug) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chatbots';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug));
    }

    /**
     * Get all active chatbots
     */
    public function get_active_chatbots() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chatbots';
        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'active' ORDER BY name ASC");
    }

    /**
     * Create new session
     */
    public function create_session($session_id, $chatbot_id, $user_data = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chat_sessions';
        
        $data = array_merge(array(
            'session_id' => $session_id,
            'chatbot_id' => $chatbot_id,
            'user_id' => get_current_user_id() ?: null,
            'user_ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'page_url' => $_SERVER['HTTP_REFERER'] ?? null
        ), $user_data);
        
        return $wpdb->insert($table, $data);
    }
    /**
     * Log chat message
     */
    public function log_message($session_id, $chatbot_id, $message_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chat_history';
        
        $data = array_merge(array(
            'session_id' => $session_id,
            'chatbot_id' => $chatbot_id,
            'timestamp' => current_time('mysql')
        ), $message_data);
        
        return $wpdb->insert($table, $data);
    }

    /**
     * Get session messages
     */
    public function get_session_messages($session_id, $limit = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chat_history';
        $sql = "SELECT * FROM $table WHERE session_id = %s ORDER BY timestamp ASC";
        
        if ($limit) {
            $sql .= " LIMIT %d";
            return $wpdb->get_results($wpdb->prepare($sql, $session_id, $limit));
        }
        
        return $wpdb->get_results($wpdb->prepare($sql, $session_id));
    }

    /**
     * Save contact information
     */
    public function save_contact($session_id, $chatbot_id, $contact_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_contact_info';
        
        $data = array_merge(array(
            'session_id' => $session_id,
            'chatbot_id' => $chatbot_id,
            'user_ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'page_url' => $_SERVER['HTTP_REFERER'] ?? null
        ), $contact_data);
        
        return $wpdb->insert($table, $data);
    }
    /**
     * Log token usage
     */
    public function log_token_usage($session_id, $chatbot_id, $usage_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_token_usage';
        
        $data = array_merge(array(
            'session_id' => $session_id,
            'chatbot_id' => $chatbot_id,
            'date_created' => current_time('Y-m-d')
        ), $usage_data);
        
        return $wpdb->insert($table, $data);
    }

    /**
     * Log debug message
     */
    public function log_debug($level, $component, $message, $context = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_debug_log';
        
        $data = array(
            'level' => $level,
            'component' => $component,
            'message' => $message,
            'context' => json_encode($context),
            'user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        if (isset($context['session_id'])) {
            $data['session_id'] = $context['session_id'];
        }
        
        if (isset($context['chatbot_id'])) {
            $data['chatbot_id'] = $context['chatbot_id'];
        }
        
        return $wpdb->insert($table, $data);
    }
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Drop all plugin tables
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'srs_debug_log',
            $wpdb->prefix . 'srs_file_uploads',
            $wpdb->prefix . 'srs_token_usage',
            $wpdb->prefix . 'srs_content_index',
            $wpdb->prefix . 'srs_contact_info',
            $wpdb->prefix . 'srs_chat_history',
            $wpdb->prefix . 'srs_chat_sessions',
            $wpdb->prefix . 'srs_chatbots'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}
