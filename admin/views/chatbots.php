<?php
/**
 * Admin Chatbots View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap srs-ai-chatbot-admin">
    <div class="srs-admin-header">
        <h1><?php _e('Manage ChatBots', 'srs-ai-chatbot'); ?></h1>
        <p><?php _e('Create, edit, and manage your AI chatbots. Each chatbot can have its own personality, model, and settings.', 'srs-ai-chatbot'); ?></p>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-bots&action=add'); ?>" class="srs-btn">
            <?php _e('Add New ChatBot', 'srs-ai-chatbot'); ?>
        </a>
    </div>

    <?php if (!empty($chatbots)): ?>
    <div class="srs-admin-section">
        <div class="srs-section-content">
            <table class="srs-table">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Model', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('API Provider', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Status', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Created', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Shortcode', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Actions', 'srs-ai-chatbot'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chatbots as $chatbot): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($chatbot->name); ?></strong>
                            <?php if ($chatbot->avatar_url): ?>
                                <br><small><?php _e('Has avatar', 'srs-ai-chatbot'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($chatbot->model); ?></td>
                        <td><?php echo esc_html(ucfirst($chatbot->api_provider)); ?></td>
                        <td>
                            <span class="srs-status-badge srs-status-<?php echo esc_attr($chatbot->status); ?>">
                                <?php echo esc_html(ucfirst($chatbot->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($chatbot->created_at))); ?></td>
                        <td>
                            <code onclick="navigator.clipboard.writeText(this.textContent)" style="cursor: pointer;" title="<?php _e('Click to copy', 'srs-ai-chatbot'); ?>">
                                [srs_ai_chatbot id="<?php echo esc_attr($chatbot->id); ?>"]
                            </code>
                        </td>
                        <td>
                            <div class="srs-actions">
                                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-bots&action=edit&id=' . $chatbot->id); ?>" 
                                   class="srs-action-link">
                                    <?php _e('Edit', 'srs-ai-chatbot'); ?>
                                </a>
                                
                                <?php if ($chatbot->status === 'active'): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=srs-ai-chatbot-bots&action=deactivate&id=' . $chatbot->id), 'chatbot_action_deactivate_' . $chatbot->id); ?>" 
                                   class="srs-action-link">
                                    <?php _e('Deactivate', 'srs-ai-chatbot'); ?>
                                </a>
                                <?php else: ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=srs-ai-chatbot-bots&action=activate&id=' . $chatbot->id), 'chatbot_action_activate_' . $chatbot->id); ?>" 
                                   class="srs-action-link">
                                    <?php _e('Activate', 'srs-ai-chatbot'); ?>
                                </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-history&chatbot_id=' . $chatbot->id); ?>" 
                                   class="srs-action-link">
                                    <?php _e('History', 'srs-ai-chatbot'); ?>
                                </a>
                                
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=srs-ai-chatbot-bots&action=delete&id=' . $chatbot->id), 'chatbot_action_delete_' . $chatbot->id); ?>" 
                                   class="srs-action-link srs-action-delete" 
                                   style="color: #dc3232;">
                                    <?php _e('Delete', 'srs-ai-chatbot'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="srs-alert srs-alert-info">
        <h3><?php _e('No ChatBots Found', 'srs-ai-chatbot'); ?></h3>
        <p><?php _e('You haven\'t created any chatbots yet. Create your first chatbot to get started!', 'srs-ai-chatbot'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-bots&action=add'); ?>" class="srs-btn">
            <?php _e('Create Your First ChatBot', 'srs-ai-chatbot'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Usage Instructions -->
    <div class="srs-admin-section">
        <div class="srs-section-header">
            <h2><?php _e('How to Display Your ChatBot', 'srs-ai-chatbot'); ?></h2>
        </div>
        <div class="srs-section-content">
            <h4><?php _e('Method 1: Shortcode', 'srs-ai-chatbot'); ?></h4>
            <p><?php _e('Copy the shortcode from the table above and paste it into any post, page, or widget.', 'srs-ai-chatbot'); ?></p>
            <div class="srs-code">[srs_ai_chatbot id="1"]</div>
            
            <h4><?php _e('Method 2: Floating Widget', 'srs-ai-chatbot'); ?></h4>
            <p><?php _e('The chatbot will automatically appear as a floating button on your site if you have active chatbots.', 'srs-ai-chatbot'); ?></p>
            
            <h4><?php _e('Method 3: Elementor Widget', 'srs-ai-chatbot'); ?></h4>
            <p><?php _e('If you use Elementor, search for "SRS AI ChatBot" in the widget panel and drag it to your page.', 'srs-ai-chatbot'); ?></p>
            
            <h4><?php _e('Shortcode Parameters', 'srs-ai-chatbot'); ?></h4>
            <ul>
                <li><code>id</code> - <?php _e('ChatBot ID (required)', 'srs-ai-chatbot'); ?></li>
                <li><code>width</code> - <?php _e('Container width (default: 100%)', 'srs-ai-chatbot'); ?></li>
                <li><code>height</code> - <?php _e('Container height (default: 500px)', 'srs-ai-chatbot'); ?></li>
            </ul>
            <div class="srs-code">[srs_ai_chatbot id="1" width="400px" height="600px"]</div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode to clipboard
    $('.srs-table code').on('click', function() {
        navigator.clipboard.writeText($(this).text()).then(function() {
            // Show temporary success message
            var $code = $(this);
            var originalText = $code.text();
            $code.text('<?php _e("Copied!", "srs-ai-chatbot"); ?>').css('background', '#d4edda');
            setTimeout(function() {
                $code.text(originalText).css('background', '');
            }, 1000);
        }.bind(this));
    });
});
</script>
