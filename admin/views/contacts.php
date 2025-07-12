<?php
/**
 * Admin Contacts View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap srs-ai-chatbot-admin">
    <div class="srs-admin-header">
        <h1><?php _e('Contact Management', 'srs-ai-chatbot'); ?></h1>
        <p><?php _e('View and manage contacts captured through your chatbot conversations.', 'srs-ai-chatbot'); ?></p>
    </div>

    <!-- Filter Bar -->
    <div class="srs-filter-bar">
        <form method="get" class="srs-filter-form">
            <input type="hidden" name="page" value="srs-ai-chatbot-contacts">
            
            <div class="srs-filter-group">
                <label><?php _e('ChatBot', 'srs-ai-chatbot'); ?></label>
                <select name="chatbot_id">
                    <option value=""><?php _e('All ChatBots', 'srs-ai-chatbot'); ?></option>
                    <?php foreach ($chatbots as $chatbot): ?>
                    <option value="<?php echo esc_attr($chatbot->id); ?>" <?php selected($filters['chatbot_id'], $chatbot->id); ?>>
                        <?php echo esc_html($chatbot->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="srs-filter-group">
                <label><?php _e('Status', 'srs-ai-chatbot'); ?></label>
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'srs-ai-chatbot'); ?></option>
                    <option value="new" <?php selected($filters['status'], 'new'); ?>><?php _e('New', 'srs-ai-chatbot'); ?></option>
                    <option value="contacted" <?php selected($filters['status'], 'contacted'); ?>><?php _e('Contacted', 'srs-ai-chatbot'); ?></option>
                    <option value="converted" <?php selected($filters['status'], 'converted'); ?>><?php _e('Converted', 'srs-ai-chatbot'); ?></option>
                    <option value="archived" <?php selected($filters['status'], 'archived'); ?>><?php _e('Archived', 'srs-ai-chatbot'); ?></option>
                </select>
            </div>

            <div class="srs-filter-group">
                <label><?php _e('Date From', 'srs-ai-chatbot'); ?></label>
                <input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>">
            </div>

            <div class="srs-filter-group">
                <label><?php _e('Date To', 'srs-ai-chatbot'); ?></label>
                <input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>">
            </div>

            <button type="submit" class="srs-btn"><?php _e('Apply Filters', 'srs-ai-chatbot'); ?></button>
            <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-contacts'); ?>" class="srs-btn srs-btn-secondary">
                <?php _e('Clear', 'srs-ai-chatbot'); ?>
            </a>
        </form>
    </div>

    <!-- Export Options -->
    <div style="margin-bottom: 20px;">
        <button class="srs-btn srs-export-btn" data-export="contacts" data-format="csv">
            <?php _e('Export Contacts (CSV)', 'srs-ai-chatbot'); ?>
        </button>
    </div>

    <!-- Contacts Table -->
    <div class="srs-admin-section">
        <div class="srs-section-content">
            <?php if (!empty($contacts)): ?>
            <table class="srs-table">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Email', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Phone', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Company', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('ChatBot', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Status', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Captured', 'srs-ai-chatbot'); ?></th>
                        <th><?php _e('Actions', 'srs-ai-chatbot'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($contact->name ?: __('N/A', 'srs-ai-chatbot')); ?></strong>
                        </td>
                        <td>
                            <?php if ($contact->email): ?>
                                <a href="mailto:<?php echo esc_attr($contact->email); ?>">
                                    <?php echo esc_html($contact->email); ?>
                                </a>
                            <?php else: ?>
                                <?php _e('N/A', 'srs-ai-chatbot'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contact->phone): ?>
                                <a href="tel:<?php echo esc_attr($contact->phone); ?>">
                                    <?php echo esc_html($contact->phone); ?>
                                </a>
                            <?php else: ?>
                                <?php _e('N/A', 'srs-ai-chatbot'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($contact->company ?: __('N/A', 'srs-ai-chatbot')); ?></td>
                        <td>
                            <?php
                            $chatbot = array_filter($chatbots, function($cb) use ($contact) {
                                return $cb->id == $contact->chatbot_id;
                            });
                            $chatbot = reset($chatbot);
                            echo esc_html($chatbot ? $chatbot->name : __('Unknown', 'srs-ai-chatbot'));
                            ?>
                        </td>
                        <td>
                            <span class="srs-status-badge srs-status-<?php echo esc_attr($contact->status); ?>">
                                <?php echo esc_html(ucfirst($contact->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('M j, Y H:i', strtotime($contact->created_at))); ?></td>
                        <td>
                            <div class="srs-actions">
                                <a href="<?php echo admin_url('admin.php?page=srs-ai-chatbot-history&session_id=' . urlencode($contact->session_id)); ?>" 
                                   class="srs-action-link">
                                    <?php _e('View Chat', 'srs-ai-chatbot'); ?>
                                </a>
                                
                                <select class="srs-contact-status" data-contact-id="<?php echo esc_attr($contact->id); ?>">
                                    <option value="new" <?php selected($contact->status, 'new'); ?>><?php _e('New', 'srs-ai-chatbot'); ?></option>
                                    <option value="contacted" <?php selected($contact->status, 'contacted'); ?>><?php _e('Contacted', 'srs-ai-chatbot'); ?></option>
                                    <option value="converted" <?php selected($contact->status, 'converted'); ?>><?php _e('Converted', 'srs-ai-chatbot'); ?></option>
                                    <option value="archived" <?php selected($contact->status, 'archived'); ?>><?php _e('Archived', 'srs-ai-chatbot'); ?></option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="srs-alert srs-alert-info">
                <h3><?php _e('No Contacts Found', 'srs-ai-chatbot'); ?></h3>
                <p><?php _e('No contacts have been captured yet. Contacts are automatically detected when users share their information in chat.', 'srs-ai-chatbot'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contact Capture Tips -->
    <div class="srs-admin-section">
        <div class="srs-section-header">
            <h2><?php _e('Contact Capture Tips', 'srs-ai-chatbot'); ?></h2>
        </div>
        <div class="srs-section-content">
            <p><?php _e('To improve contact capture rates:', 'srs-ai-chatbot'); ?></p>
            <ul>
                <li><?php _e('Configure your chatbot to ask for contact information when appropriate', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('Use prompts like "What\'s your email so I can send you more information?"', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('The system automatically detects emails, phone numbers, and names in conversations', 'srs-ai-chatbot'); ?></li>
                <li><?php _e('Enable email notifications to get alerts when new contacts are captured', 'srs-ai-chatbot'); ?></li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle contact status changes
    $('.srs-contact-status').on('change', function() {
        var contactId = $(this).data('contact-id');
        var newStatus = $(this).val();
        var $select = $(this);
        
        $.post(ajaxurl, {
            action: 'srs_ai_chatbot_update_contact_status',
            nonce: '<?php echo wp_create_nonce("srs_ai_chatbot_admin_nonce"); ?>',
            contact_id: contactId,
            status: newStatus
        }, function(response) {
            if (response.success) {
                // Update status badge
                var $badge = $select.closest('tr').find('.srs-status-badge');
                $badge.removeClass('srs-status-new srs-status-contacted srs-status-converted srs-status-archived')
                      .addClass('srs-status-' + newStatus)
                      .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
            } else {
                alert('<?php _e("Error updating contact status.", "srs-ai-chatbot"); ?>');
                // Reset select to previous value
                $select.val($select.find('option:selected').siblings().first().val());
            }
        }).fail(function() {
            alert('<?php _e("Error updating contact status.", "srs-ai-chatbot"); ?>');
        });
    });
});
</script>
