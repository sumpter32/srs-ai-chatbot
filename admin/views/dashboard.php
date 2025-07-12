<?php
/**
 * Admin Dashboard View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap srs-ai-chatbot-admin">
    <div class="srs-admin-header">
        <h1><?php _e('SRS AI ChatBot Dashboard', 'srs-ai-chatbot'); ?></h1>
        <p><?php _e('Welcome to your AI ChatBot control panel. Monitor performance, manage conversations, and optimize your chatbot experience.', 'srs-ai-chatbot'); ?></p>
    </div>

    <!-- Stats Grid -->
    <div class="srs-stats-grid">
        <div class="srs-stat-card">
            <span class="srs-stat-number"><?php echo number_format($stats['total_sessions']); ?></span>
            <span class="srs-stat-label"><?php _e('Total Conversations', 'srs-ai-chatbot'); ?></span>
        </div>
        
        <div class="srs-stat-card">
            <span class="srs-stat-number"><?php echo number_format($stats['total_messages']); ?></span>
            <span class="srs-stat-label"><?php _e('Messages Exchanged', 'srs-ai-chatbot'); ?></span>
        </div>
        
        <div class="srs-stat-card">
            <span class="srs-stat-number"><?php echo number_format($stats['total_tokens']); ?></span>
            <span class="srs-stat-label"><?php _e('Tokens Used', 'srs-ai-chatbot'); ?></span>
        </div>
        
        <div class="srs-stat-card">
            <span class="srs-stat-number">$<?php echo number_format($stats['total_cost'], 2); ?></span>
            <span class="srs-stat-label"><?php _e('Total Cost', 'srs-ai-chatbot'); ?></span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="srs-admin-section">
        <div class="srs-section-header">
            <h2><?php _e('Quick Actions', 'srs-ai-chatbot'); ?></h2>
        </div>
        <div class="srs-section-content">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-bots&action=add'); ?>" class="srs-btn">
                    <?php _e('Create New ChatBot', 'srs-ai-chatbot'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-settings'); ?>" class="srs-btn srs-btn-secondary">
                    <?php _e('Configure Settings', 'srs-ai-chatbot'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-history'); ?>" class="srs-btn srs-btn-secondary">
                    <?php _e('View Chat History', 'srs-ai-chatbot'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-analytics'); ?>" class="srs-btn srs-btn-secondary">
                    <?php _e('Analytics Report', 'srs-ai-chatbot'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Active Chatbots -->
    <div class="srs-admin-section">
        <div class="srs-section-header">
            <h2><?php _e('Your ChatBots', 'srs-ai-chatbot'); ?></h2>
        </div>
        <div class="srs-section-content">
            <?php if (!empty($chatbots)): ?>
                <table class="srs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'srs-ai-chatbot'); ?></th>
                            <th><?php _e('Model', 'srs-ai-chatbot'); ?></th>
                            <th><?php _e('Status', 'srs-ai-chatbot'); ?></th>
                            <th><?php _e('Shortcode', 'srs-ai-chatbot'); ?></th>
                            <th><?php _e('Actions', 'srs-ai-chatbot'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chatbots as $chatbot): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($chatbot->name); ?></strong>
                            </td>
                            <td><?php echo esc_html($chatbot->model); ?></td>
                            <td>
                                <span class="srs-status-badge srs-status-<?php echo esc_attr($chatbot->status); ?>">
                                    <?php echo esc_html(ucfirst($chatbot->status)); ?>
                                </span>
                            </td>
                            <td>
                                <code>[srs_ai_chatbot id="<?php echo esc_attr($chatbot->id); ?>"]</code>
                            </td>
                            <td>
                                <div class="srs-actions">
                                    <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-bots&action=edit&id=' . $chatbot->id); ?>" class="srs-action-link">
                                        <?php _e('Edit', 'srs-ai-chatbot'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-history&chatbot_id=' . $chatbot->id); ?>" class="srs-action-link">
                                        <?php _e('History', 'srs-ai-chatbot'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="srs-alert srs-alert-info">
                    <p><?php _e('No chatbots found. Create your first chatbot to get started!', 'srs-ai-chatbot'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-bots&action=add'); ?>" class="srs-btn">
                        <?php _e('Create Your First ChatBot', 'srs-ai-chatbot'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="srs-admin-section">
        <div class="srs-section-header">
            <h2><?php _e('Recent Activity', 'srs-ai-chatbot'); ?></h2>
        </div>
        <div class="srs-section-content">
            <p><?php _e('Last 30 days performance overview', 'srs-ai-chatbot'); ?></p>
            <ul>
                <li><?php printf(__('Average response time: %.2f seconds', 'srs-ai-chatbot'), $stats['avg_response_time']); ?></li>
                <li><?php printf(__('Contacts captured: %d', 'srs-ai-chatbot'), $stats['total_contacts']); ?></li>
                <li><?php printf(__('Data period: %s to %s', 'srs-ai-chatbot'), $stats['date_from'], $stats['date_to']); ?></li>
            </ul>
        </div>
    </div>

    <!-- Getting Started -->
    <div class="srs-admin-section">
        <div class="srs-section-header">
            <h2><?php _e('Getting Started', 'srs-ai-chatbot'); ?></h2>
        </div>
        <div class="srs-section-content">
            <p><?php _e('New to SRS AI ChatBot? Here are some quick tips:', 'srs-ai-chatbot'); ?></p>
            <ol>
                <li><?php _e('Configure your API keys in Settings', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('Create your first chatbot with a custom system prompt', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('Add the chatbot to your site using shortcodes or Elementor', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('Monitor performance in Analytics', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('Capture leads through the Contacts section', 'srs-ai-chatbot'); ?></li>
            </ol>
        </div>
    </div>
</div>
