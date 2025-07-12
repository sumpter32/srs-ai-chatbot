<?php
/**
 * Shortcodes for SRS AI ChatBot
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Shortcodes {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('srs_ai_chatbot', array($this, 'render_chatbot_shortcode'));
        add_shortcode('srs_chatbot', array($this, 'render_chatbot_shortcode')); // Alternative shortcode
    }

    /**
     * Render chatbot shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered chatbot HTML
     */
    public function render_chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => null,
            'slug' => null,
            'width' => '100%',
            'height' => '500px',
            'theme' => 'default'
        ), $atts, 'srs_ai_chatbot');

        // Get chatbot
        $database = new SRS_AI_ChatBot_Database();
        
        if ($atts['id']) {
            $chatbot = $database->get_chatbot(intval($atts['id']));
        } elseif ($atts['slug']) {
            $chatbot = $database->get_chatbot_by_slug(sanitize_text_field($atts['slug']));
        } else {
            // Get first active chatbot
            $chatbots = $database->get_active_chatbots();
            $chatbot = !empty($chatbots) ? $chatbots[0] : null;
        }

        if (!$chatbot) {
            return '<p>' . __('Chatbot not found.', 'srs-ai-chatbot') . '</p>';
        }

        // Enqueue scripts if not already enqueued
        if (!wp_script_is('srs-ai-chatbot-public', 'enqueued')) {
            wp_enqueue_style('srs-ai-chatbot-public', SRS_AI_CHATBOT_PLUGIN_URL . 'assets/css/public.css', array(), SRS_AI_CHATBOT_VERSION);
            wp_enqueue_script('srs-ai-chatbot-public', SRS_AI_CHATBOT_PLUGIN_URL . 'assets/js/public.js', array('jquery'), SRS_AI_CHATBOT_VERSION, true);
            
            wp_localize_script('srs-ai-chatbot-public', 'srs_ai_chatbot_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('srs_ai_chatbot_nonce'),
                'strings' => array(
                    'typing' => __('AI is typing...', 'srs-ai-chatbot'),
                    'error' => __('Sorry, something went wrong. Please try again.', 'srs-ai-chatbot'),
                    'file_too_large' => __('File is too large.', 'srs-ai-chatbot'),
                    'file_type_not_allowed' => __('File type not allowed.', 'srs-ai-chatbot'),
                    'new_chat' => __('Start New Chat', 'srs-ai-chatbot'),
                    'send' => __('Send', 'srs-ai-chatbot'),
                    'upload' => __('Upload File', 'srs-ai-chatbot')
                )
            ));
        }

        // Start output buffering
        ob_start();
        
        // Render inline chatbot
        $public = new SRS_AI_ChatBot_Public();
        echo '<div class="srs-chatbot-shortcode-container" style="width: ' . esc_attr($atts['width']) . '; height: ' . esc_attr($atts['height']) . ';">';
        $public->render_chat_interface($chatbot, true);
        echo '</div>';
        
        return ob_get_clean();
    }
}
