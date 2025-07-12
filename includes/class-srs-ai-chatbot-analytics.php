<?php
/**
 * Analytics and Token Tracking
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Analytics {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('srs_ai_chatbot_daily_cleanup', array($this, 'cleanup_old_data'));
    }

    /**
     * Get usage statistics
     */
    public function get_usage_stats($period = '30', $chatbot_id = null) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$period} days"));
        $date_to = date('Y-m-d');
        
        $where_chatbot = $chatbot_id ? $wpdb->prepare('AND chatbot_id = %d', $chatbot_id) : '';
        
        // Total conversations
        $sessions_table = $wpdb->prefix . 'srs_chat_sessions';
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table 
             WHERE DATE(started_at) BETWEEN %s AND %s $where_chatbot",
            $date_from, $date_to
        ));

        // Total messages
        $history_table = $wpdb->prefix . 'srs_chat_history';
        $total_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $history_table 
             WHERE DATE(timestamp) BETWEEN %s AND %s $where_chatbot",
            $date_from, $date_to
        ));

        // Token usage
        $tokens_table = $wpdb->prefix . 'srs_token_usage';
        $token_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(total_tokens) as total_tokens,
                SUM(cost) as total_cost,
                AVG(response_time) as avg_response_time
             FROM $tokens_table 
             WHERE date_created BETWEEN %s AND %s $where_chatbot",
            $date_from, $date_to
        ));

        // Contacts collected
        $contacts_table = $wpdb->prefix . 'srs_contact_info';
        $total_contacts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $contacts_table 
             WHERE DATE(created_at) BETWEEN %s AND %s $where_chatbot",
            $date_from, $date_to
        ));

        return array(
            'total_sessions' => intval($total_sessions),
            'total_messages' => intval($total_messages),
            'total_tokens' => intval($token_stats->total_tokens ?? 0),
            'total_cost' => floatval($token_stats->total_cost ?? 0),
            'avg_response_time' => floatval($token_stats->avg_response_time ?? 0),
            'total_contacts' => intval($total_contacts),
            'period' => $period,
            'date_from' => $date_from,
            'date_to' => $date_to
        );
    }
    /**
     * Get daily usage chart data
     */
    public function get_daily_usage_chart($days = 30, $chatbot_id = null) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        $where_chatbot = $chatbot_id ? $wpdb->prepare('AND chatbot_id = %d', $chatbot_id) : '';
        
        // Sessions per day
        $sessions_table = $wpdb->prefix . 'srs_chat_sessions';
        $daily_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(started_at) as date,
                COUNT(*) as sessions
             FROM $sessions_table 
             WHERE DATE(started_at) >= %s $where_chatbot
             GROUP BY DATE(started_at)
             ORDER BY date ASC",
            $date_from
        ));

        // Messages per day
        $history_table = $wpdb->prefix . 'srs_chat_history';
        $daily_messages = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(timestamp) as date,
                COUNT(*) as messages
             FROM $history_table 
             WHERE DATE(timestamp) >= %s $where_chatbot
             GROUP BY DATE(timestamp)
             ORDER BY date ASC",
            $date_from
        ));

        // Combine data and fill missing dates
        $chart_data = array();
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $chart_data[] = array(
                'date' => $date,
                'sessions' => $this->find_value_by_date($daily_sessions, $date, 'sessions'),
                'messages' => $this->find_value_by_date($daily_messages, $date, 'messages')
            );
        }

        return $chart_data;
    }

    /**
     * Get token usage by model
     */
    public function get_token_usage_by_model($period = 30, $chatbot_id = null) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$period} days"));
        $where_chatbot = $chatbot_id ? $wpdb->prepare('AND chatbot_id = %d', $chatbot_id) : '';
        
        $tokens_table = $wpdb->prefix . 'srs_token_usage';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                model,
                SUM(total_tokens) as total_tokens,
                SUM(cost) as total_cost,
                COUNT(*) as requests
             FROM $tokens_table 
             WHERE date_created >= %s $where_chatbot
             GROUP BY model
             ORDER BY total_tokens DESC",
            $date_from
        ));
    }

    /**
     * Get most active sessions
     */
    public function get_most_active_sessions($limit = 10, $chatbot_id = null) {
        global $wpdb;
        
        $where_chatbot = $chatbot_id ? $wpdb->prepare('AND chatbot_id = %d', $chatbot_id) : '';
        
        $sessions_table = $wpdb->prefix . 'srs_chat_sessions';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                session_id,
                total_messages,
                total_tokens,
                total_cost,
                started_at,
                ended_at
             FROM $sessions_table 
             WHERE total_messages > 0 $where_chatbot
             ORDER BY total_messages DESC
             LIMIT %d",
            $limit
        ));
    }
    /**
     * Helper function to find value by date
     */
    private function find_value_by_date($data, $date, $field) {
        foreach ($data as $item) {
            if ($item->date === $date) {
                return intval($item->{$field});
            }
        }
        return 0;
    }

    /**
     * Cleanup old data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        $retention_days = apply_filters('srs_ai_chatbot_data_retention_days', 90);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Cleanup old sessions
        $sessions_table = $wpdb->prefix . 'srs_chat_sessions';
        $old_sessions = $wpdb->get_col($wpdb->prepare(
            "SELECT session_id FROM $sessions_table WHERE ended_at < %s OR (status = 'abandoned' AND last_activity < %s)",
            $cutoff_date,
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));

        if (!empty($old_sessions)) {
            $session_ids = "'" . implode("','", array_map('esc_sql', $old_sessions)) . "'";
            
            // Delete related data
            $wpdb->query("DELETE FROM {$wpdb->prefix}srs_chat_history WHERE session_id IN ($session_ids)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}srs_token_usage WHERE session_id IN ($session_ids)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}srs_file_uploads WHERE session_id IN ($session_ids)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}srs_contact_info WHERE session_id IN ($session_ids)");
            $wpdb->query("DELETE FROM $sessions_table WHERE session_id IN ($session_ids)");
        }

        // Cleanup old debug logs
        $debug_table = $wpdb->prefix . 'srs_debug_log';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $debug_table WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        // Cleanup expired files
        $file_handler = new SRS_AI_ChatBot_File_Handler();
        $file_handler->cleanup_expired_files();

        return array(
            'sessions_cleaned' => count($old_sessions),
            'cutoff_date' => $cutoff_date
        );
    }

    /**
     * Export usage data to CSV
     */
    public function export_usage_data($chatbot_id = null, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        $where_chatbot = $chatbot_id ? $wpdb->prepare('AND s.chatbot_id = %d', $chatbot_id) : '';
        
        $sessions_table = $wpdb->prefix . 'srs_chat_sessions';
        $history_table = $wpdb->prefix . 'srs_chat_history';
        $tokens_table = $wpdb->prefix . 'srs_token_usage';
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.session_id,
                s.started_at,
                s.ended_at,
                s.total_messages,
                s.total_tokens,
                s.total_cost,
                s.status,
                COALESCE(SUM(t.total_tokens), 0) as calculated_tokens,
                COALESCE(SUM(t.cost), 0) as calculated_cost
             FROM $sessions_table s
             LEFT JOIN $tokens_table t ON s.session_id = t.session_id
             WHERE DATE(s.started_at) BETWEEN %s AND %s $where_chatbot
             GROUP BY s.session_id
             ORDER BY s.started_at DESC",
            $date_from, $date_to
        ));

        return $data;
    }
}
