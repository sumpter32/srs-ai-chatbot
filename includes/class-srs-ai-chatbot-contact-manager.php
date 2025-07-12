<?php
/**
 * Contact Manager for extracting and managing contact information
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Contact_Manager {

    /**
     * Email settings
     */
    private $email_settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->email_settings = get_option('srs_ai_chatbot_email_settings', array());
    }

    /**
     * Extract and save contact information from message
     */
    public function extract_and_save_contact($session_id, $chatbot_id, $message) {
        $contact_data = $this->extract_contact_info($message);
        
        if (!empty($contact_data)) {
            $database = new SRS_AI_ChatBot_Database();
            $contact_id = $database->save_contact($session_id, $chatbot_id, $contact_data);
            
            if ($contact_id && $this->email_settings['notify_on_contact'] ?? false) {
                $this->send_contact_notification($contact_data, $session_id);
            }
            
            return $contact_id;
        }
        
        return false;
    }

    /**
     * Extract contact information from text
     */
    private function extract_contact_info($text) {
        $contact_data = array();

        // Extract email
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $text, $matches)) {
            $contact_data['email'] = $matches[1];
        }

        // Extract phone numbers (various formats)
        $phone_patterns = array(
            '/(\+?1[-.\s]?)?(\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})/', // US format
            '/(\+?\d{1,3}[-.\s]?)?(\(?\d{2,4}\)?[-.\s]?\d{3,4}[-.\s]?\d{3,4})/', // International
            '/(\d{3,4}[-.\s]?\d{3,4}[-.\s]?\d{3,4})/' // Simple format
        );

        foreach ($phone_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $contact_data['phone'] = $matches[0];
                break;
            }
        }

        // Extract name (basic pattern - can be improved)
        $name_patterns = array(
            '/(?:my name is|i am|i\'m|call me)\s+([a-zA-Z\s]{2,30})/i',
            '/name:\s*([a-zA-Z\s]{2,30})/i'
        );

        foreach ($name_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $contact_data['name'] = trim($matches[1]);
                break;
            }
        }

        // Extract company
        $company_patterns = array(
            '/(?:work at|company|from)\s+([a-zA-Z\s&.,]{2,50})/i',
            '/company:\s*([a-zA-Z\s&.,]{2,50})/i'
        );

        foreach ($company_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $contact_data['company'] = trim($matches[1]);
                break;
            }
        }

        return $contact_data;
    }
    /**
     * Send contact notification email
     */
    private function send_contact_notification($contact_data, $session_id) {
        $admin_email = $this->email_settings['admin_email'] ?? get_option('admin_email');
        
        $subject = sprintf(__('[%s] New Contact from ChatBot', 'srs-ai-chatbot'), get_bloginfo('name'));
        
        $message = __('New contact information captured from chatbot:', 'srs-ai-chatbot') . "\n\n";
        
        if (!empty($contact_data['name'])) {
            $message .= __('Name:', 'srs-ai-chatbot') . ' ' . $contact_data['name'] . "\n";
        }
        
        if (!empty($contact_data['email'])) {
            $message .= __('Email:', 'srs-ai-chatbot') . ' ' . $contact_data['email'] . "\n";
        }
        
        if (!empty($contact_data['phone'])) {
            $message .= __('Phone:', 'srs-ai-chatbot') . ' ' . $contact_data['phone'] . "\n";
        }
        
        if (!empty($contact_data['company'])) {
            $message .= __('Company:', 'srs-ai-chatbot') . ' ' . $contact_data['company'] . "\n";
        }
        
        $message .= "\n" . __('Session ID:', 'srs-ai-chatbot') . ' ' . $session_id . "\n";
        $message .= __('View in admin:', 'srs-ai-chatbot') . ' ' . admin_url('admin.php?page=srs-ai-chatbot-contacts');
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Get all contacts
     */
    public function get_contacts($filters = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_contact_info';
        $where = array('1=1');
        $values = array();

        if (!empty($filters['chatbot_id'])) {
            $where[] = 'chatbot_id = %d';
            $values[] = $filters['chatbot_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $filters['date_to'];
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
        
        if (!empty($values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $values));
        } else {
            return $wpdb->get_results($sql);
        }
    }

    /**
     * Update contact status
     */
    public function update_contact_status($contact_id, $status) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_contact_info';
        
        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $contact_id),
            array('%s'),
            array('%d')
        );
    }
}
