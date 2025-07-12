<?php
/**
 * Chatbot Engine - Core conversation handling
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Engine {

    /**
     * Database instance
     */
    private $database;

    /**
     * API handler
     */
    private $api;

    /**
     * File handler
     */
    private $file_handler;

    /**
     * Contact manager
     */
    private $contact_manager;

    /**
     * Content indexer
     */
    private $content_indexer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new SRS_AI_ChatBot_Database();
        $this->api = new SRS_AI_ChatBot_API();
        $this->file_handler = new SRS_AI_ChatBot_File_Handler();
        $this->contact_manager = new SRS_AI_ChatBot_Contact_Manager();
        $this->content_indexer = new SRS_AI_ChatBot_Content_Indexer();
    }

    /**
     * Handle AJAX message request
     */
    public function handle_ajax_message() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'srs_ai_chatbot_nonce')) {
            wp_die('Security check failed');
        }

        $chatbot_id = intval($_POST['chatbot_id'] ?? 1);
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $reset_session = $_POST['reset_session'] ?? false;

        try {
            $response = $this->process_message($chatbot_id, $session_id, $message, $reset_session);
            wp_send_json_success($response);
        } catch (Exception $e) {
            $this->database->log_debug('error', 'chatbot_engine', $e->getMessage(), array(
                'chatbot_id' => $chatbot_id,
                'session_id' => $session_id
            ));
            wp_send_json_error('An error occurred while processing your message.');
        }
    }
    /**
     * Process incoming message
     */
    public function process_message($chatbot_id, $session_id, $message, $reset_session = false) {
        $start_time = microtime(true);

        // Get chatbot configuration
        $chatbot = $this->database->get_chatbot($chatbot_id);
        if (!$chatbot) {
            throw new Exception('Chatbot not found');
        }

        // Generate session ID if not provided
        if (empty($session_id)) {
            $session_id = $this->generate_session_id();
        }

        // Create or reset session if needed
        if ($reset_session || !$this->session_exists($session_id)) {
            $this->create_new_session($session_id, $chatbot_id);
        }

        // Log user message
        $this->database->log_message($session_id, $chatbot_id, array(
            'message_type' => 'user',
            'message' => $message
        ));

        // Check for contact information in message
        $this->contact_manager->extract_and_save_contact($session_id, $chatbot_id, $message);

        // Get conversation history
        $history = $this->get_conversation_history($session_id, $chatbot->max_memory_messages);

        // Build context
        $context = $this->build_context($chatbot, $message);

        // Prepare messages for API
        $messages = $this->prepare_messages($chatbot, $history, $context, $message);

        // Call AI API
        $ai_response = $this->api->chat_completion($chatbot, $messages);

        if (!$ai_response['success']) {
            throw new Exception($ai_response['error']);
        }

        $response_time = microtime(true) - $start_time;

        // Log AI response
        $this->database->log_message($session_id, $chatbot_id, array(
            'message_type' => 'assistant',
            'message' => $message,
            'response' => $ai_response['message'],
            'input_tokens' => $ai_response['usage']['input_tokens'] ?? 0,
            'output_tokens' => $ai_response['usage']['output_tokens'] ?? 0,
            'total_tokens' => $ai_response['usage']['total_tokens'] ?? 0,
            'cost' => $ai_response['usage']['cost'] ?? 0,
            'model_used' => $ai_response['model'],
            'response_time' => $response_time
        ));
        // Log token usage
        $this->database->log_token_usage($session_id, $chatbot_id, array(
            'model' => $ai_response['model'],
            'api_provider' => $chatbot->api_provider,
            'input_tokens' => $ai_response['usage']['input_tokens'] ?? 0,
            'output_tokens' => $ai_response['usage']['output_tokens'] ?? 0,
            'total_tokens' => $ai_response['usage']['total_tokens'] ?? 0,
            'cost' => $ai_response['usage']['cost'] ?? 0,
            'response_time' => $response_time,
            'success' => true
        ));

        // Update session activity
        $this->update_session_activity($session_id);

        return array(
            'message' => $ai_response['message'],
            'session_id' => $session_id,
            'tokens' => $ai_response['usage']['total_tokens'] ?? 0,
            'cost' => $ai_response['usage']['cost'] ?? 0,
            'response_time' => round($response_time, 3)
        );
    }

    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return 'srs_' . uniqid() . '_' . wp_generate_password(8, false);
    }

    /**
     * Check if session exists
     */
    private function session_exists($session_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chat_sessions';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE session_id = %s",
            $session_id
        ));
        
        return $count > 0;
    }
    /**
     * Create new session
     */
    private function create_new_session($session_id, $chatbot_id) {
        $this->database->create_session($session_id, $chatbot_id);
    }

    /**
     * Get conversation history
     */
    private function get_conversation_history($session_id, $limit = 10) {
        return $this->database->get_session_messages($session_id, $limit);
    }

    /**
     * Build context from site content
     */
    private function build_context($chatbot, $message) {
        $context = array();

        // Get relevant content from site
        $relevant_content = $this->content_indexer->search_content($message);
        if (!empty($relevant_content)) {
            $context['site_content'] = $relevant_content;
        }

        // Check for WooCommerce order queries
        if (class_exists('WooCommerce') && $this->is_order_query($message)) {
            $order_info = $this->handle_order_query($message);
            if ($order_info) {
                $context['order_info'] = $order_info;
            }
        }

        return $context;
    }

    /**
     * Prepare messages for API call
     */
    private function prepare_messages($chatbot, $history, $context, $current_message) {
        $messages = array();

        // System prompt
        $system_prompt = $chatbot->system_prompt;
        
        // Add context to system prompt
        if (!empty($context['site_content'])) {
            $system_prompt .= "\n\nRelevant site content:\n" . implode("\n", $context['site_content']);
        }
        
        if (!empty($context['order_info'])) {
            $system_prompt .= "\n\nOrder information:\n" . $context['order_info'];
        }

        $messages[] = array(
            'role' => 'system',
            'content' => $system_prompt
        );

        // Add conversation history
        foreach ($history as $msg) {
            if ($msg->message_type === 'user') {
                $messages[] = array(
                    'role' => 'user',
                    'content' => $msg->message
                );
            } elseif ($msg->message_type === 'assistant' && !empty($msg->response)) {
                $messages[] = array(
                    'role' => 'assistant',
                    'content' => $msg->response
                );
            }
        }

        // Add current message
        $messages[] = array(
            'role' => 'user',
            'content' => $current_message
        );

        return $messages;
    }

    /**
     * Check if message is an order query
     */
    private function is_order_query($message) {
        $order_keywords = array(
            'order', 'purchase', 'tracking', 'delivery', 'shipment', 
            'status', 'where is my', 'when will', 'receipt'
        );

        $message_lower = strtolower($message);
        foreach ($order_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle order query
     */
    private function handle_order_query($message) {
        // Extract order number and email from message
        $order_number = $this->extract_order_number($message);
        $email = $this->extract_email($message);

        if ($order_number) {
            $wc_integration = new SRS_AI_ChatBot_WooCommerce();
            return $wc_integration->get_order_info($order_number, $email);
        }

        return null;
    }
    /**
     * Extract order number from message
     */
    private function extract_order_number($message) {
        // Look for order number patterns
        if (preg_match('/(?:order|#)\s*(\d+)/i', $message, $matches)) {
            return $matches[1];
        }
        
        // Look for standalone numbers
        if (preg_match('/\b(\d{4,})\b/', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract email from message
     */
    private function extract_email($message) {
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $message, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Update session activity
     */
    private function update_session_activity($session_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_chat_sessions';
        $wpdb->update(
            $table,
            array(
                'last_activity' => current_time('mysql'),
                'total_messages' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}srs_chat_history WHERE session_id = %s",
                    $session_id
                ))
            ),
            array('session_id' => $session_id),
            array('%s', '%d'),
            array('%s')
        );
    }

    /**
     * Handle file upload during conversation
     */
    public function handle_file_upload($session_id, $chatbot_id, $files) {
        $results = array();
        
        foreach ($files as $file) {
            try {
                $upload_result = $this->file_handler->handle_upload($file, $session_id, $chatbot_id);
                if ($upload_result['success']) {
                    $results[] = $upload_result;
                }
            } catch (Exception $e) {
                $this->database->log_debug('error', 'file_upload', $e->getMessage(), array(
                    'session_id' => $session_id,
                    'chatbot_id' => $chatbot_id,
                    'file' => $file['name']
                ));
            }
        }
        
        return $results;
    }
}
