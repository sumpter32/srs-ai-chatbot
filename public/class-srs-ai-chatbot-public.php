<?php
/**
 * Public-facing functionality
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Public {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_chat_widget'));
        add_action('wp_ajax_srs_ai_chatbot_upload_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_nopriv_srs_ai_chatbot_upload_file', array($this, 'handle_file_upload'));
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'srs-ai-chatbot-public',
            SRS_AI_CHATBOT_PLUGIN_URL . 'assets/css/public.css',
            array(),
            SRS_AI_CHATBOT_VERSION
        );

        wp_enqueue_script(
            'srs-ai-chatbot-public',
            SRS_AI_CHATBOT_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            SRS_AI_CHATBOT_VERSION,
            true
        );

        // Localize script
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
    /**
     * Render chat widget in footer
     */
    public function render_chat_widget() {
        // Check if chat widget should be displayed
        $display_widget = apply_filters('srs_ai_chatbot_display_widget', true);
        
        if (!$display_widget) {
            return;
        }

        // Get default chatbot
        $database = new SRS_AI_ChatBot_Database();
        $chatbots = $database->get_active_chatbots();
        
        if (empty($chatbots)) {
            return;
        }

        $chatbot = $chatbots[0]; // Use first active chatbot as default
        
        $this->render_chat_interface($chatbot);
    }

    /**
     * Render chat interface
     */
    public function render_chat_interface($chatbot, $inline = false) {
        $widget_class = $inline ? 'srs-chatbot-inline' : 'srs-chatbot-widget';
        ?>
        <div id="srs-ai-chatbot-<?php echo esc_attr($chatbot->id); ?>" 
             class="<?php echo esc_attr($widget_class); ?>" 
             data-chatbot-id="<?php echo esc_attr($chatbot->id); ?>">
            
            <?php if (!$inline): ?>
            <!-- Chat Toggle Button -->
            <div class="srs-chatbot-toggle">
                <button class="srs-chatbot-toggle-btn" aria-label="<?php _e('Open chat', 'srs-ai-chatbot'); ?>">
                    <?php if ($chatbot->avatar_url): ?>
                        <img src="<?php echo esc_url($chatbot->avatar_url); ?>" alt="<?php echo esc_attr($chatbot->name); ?>" class="srs-chatbot-avatar">
                    <?php else: ?>
                        <svg class="srs-chatbot-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                        </svg>
                    <?php endif; ?>
                    <span class="srs-chatbot-notification" style="display: none;"></span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Chat Window -->
            <div class="srs-chatbot-window" style="<?php echo $inline ? '' : 'display: none;'; ?>">
                <!-- Header -->
                <div class="srs-chatbot-header">
                    <div class="srs-chatbot-header-info">
                        <?php if ($chatbot->avatar_url): ?>
                            <img src="<?php echo esc_url($chatbot->avatar_url); ?>" alt="<?php echo esc_attr($chatbot->name); ?>" class="srs-chatbot-header-avatar">
                        <?php endif; ?>
                        <div class="srs-chatbot-header-text">
                            <h4><?php echo esc_html($chatbot->name); ?></h4>
                            <span class="srs-chatbot-status"><?php _e('Online', 'srs-ai-chatbot'); ?></span>
                        </div>
                    </div>
                    <div class="srs-chatbot-header-actions">
                        <button class="srs-chatbot-new-chat" title="<?php _e('Start new chat', 'srs-ai-chatbot'); ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                            </svg>
                        </button>
                        <?php if (!$inline): ?>
                        <button class="srs-chatbot-minimize" title="<?php _e('Minimize', 'srs-ai-chatbot'); ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 13H5v-2h14v2z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Messages Container -->
                <div class="srs-chatbot-messages">
                    <div class="srs-chatbot-message srs-chatbot-message-bot">
                        <div class="srs-chatbot-message-content">
                            <?php echo wp_kses_post($chatbot->greeting_message); ?>
                        </div>
                        <div class="srs-chatbot-message-time">
                            <?php echo current_time('H:i'); ?>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="srs-chatbot-input-area">
                    <div class="srs-chatbot-input-container">
                        <input type="file" 
                               id="srs-chatbot-file-input-<?php echo esc_attr($chatbot->id); ?>" 
                               class="srs-chatbot-file-input" 
                               accept=".pdf,.docx,.txt,.jpg,.jpeg,.png" 
                               style="display: none;" 
                               multiple>
                        
                        <button class="srs-chatbot-file-btn" 
                                title="<?php _e('Attach file', 'srs-ai-chatbot'); ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/>
                            </svg>
                        </button>
                        
                        <textarea class="srs-chatbot-input" 
                                  placeholder="<?php _e('Type your message...', 'srs-ai-chatbot'); ?>" 
                                  rows="1"></textarea>
                        
                        <button class="srs-chatbot-send-btn" 
                                title="<?php _e('Send message', 'srs-ai-chatbot'); ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="srs-chatbot-file-preview" style="display: none;">
                        <!-- File previews will be shown here -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'srs_ai_chatbot_nonce')) {
            wp_die('Security check failed');
        }

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $chatbot_id = intval($_POST['chatbot_id'] ?? 1);

        try {
            $file_handler = new SRS_AI_ChatBot_File_Handler();
            $result = $file_handler->handle_upload($_FILES['file'], $session_id, $chatbot_id);
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
