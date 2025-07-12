                        <div class="srs-form-group">
                            <label>
                                <input type="checkbox" 
                                       name="training_settings[include_custom_fields]" 
                                       value="1" 
                                       <?php checked($training_settings['include_custom_fields'] ?? false); ?>>
                                <?php _e('Include Custom Fields', 'srs-ai-chatbot'); ?>
                            </label>
                            <p class="description"><?php _e('Include post meta fields in training data', 'srs-ai-chatbot'); ?></p>
                        </div>

                        <div class="srs-form-group">
                            <button type="button" class="srs-btn srs-reindex-content">
                                <?php _e('Reindex All Content', 'srs-ai-chatbot'); ?>
                            </button>
                            <p class="description"><?php _e('Manually trigger content reindexing', 'srs-ai-chatbot'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (class_exists('WooCommerce')): ?>
        <!-- WooCommerce Settings Tab -->
        <div id="wc-settings" class="srs-tab-content">
            <div class="srs-admin-section">
                <div class="srs-section-header">
                    <h2><?php _e('WooCommerce Integration', 'srs-ai-chatbot'); ?></h2>
                </div>
                <div class="srs-section-content">
                    <div class="srs-form-grid">
                        <div class="srs-form-group">
                            <label>
                                <input type="checkbox" 
                                       name="wc_settings[require_email]" 
                                       value="1" 
                                       <?php checked($wc_settings['require_email'] ?? true); ?>>
                                <?php _e('Require email for order lookup', 'srs-ai-chatbot'); ?>
                            </label>
                        </div>

                        <div class="srs-form-group">
                            <label><?php _e('Max Days Back for Orders', 'srs-ai-chatbot'); ?></label>
                            <input type="number" 
                                   name="wc_settings[max_days_back]" 
                                   value="<?php echo esc_attr($wc_settings['max_days_back'] ?? 365); ?>"
                                   min="30" max="1095">
                            <p class="description"><?php _e('Maximum age of orders to display', 'srs-ai-chatbot'); ?></p>
                        </div>

                        <div class="srs-form-group">
                            <label><?php _e('Order Response Template', 'srs-ai-chatbot'); ?></label>
                            <textarea name="wc_settings[response_template]" rows="10"><?php 
                                echo esc_textarea($wc_settings['response_template'] ?? 
                                    __('Here\'s your order information:\n\nOrder #[order_number]\nStatus: [order_status]\nOrder Date: [order_date]\nTotal: [order_total]\n\nItems: [order_items]\n\nShipping Method: [shipping_method]\nTracking Number: [tracking_number]\nEstimated Delivery: [estimated_delivery]', 'srs-ai-chatbot')
                                ); 
                            ?></textarea>
                            <p class="description"><?php _e('Available placeholders: [order_number], [order_status], [order_date], [order_total], [order_items], [shipping_method], [tracking_number], [estimated_delivery]', 'srs-ai-chatbot'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Token Pricing Settings Tab -->
        <div id="token-settings" class="srs-tab-content">
            <div class="srs-admin-section">
                <div class="srs-section-header">
                    <h2><?php _e('Token Pricing Configuration', 'srs-ai-chatbot'); ?></h2>
                </div>
                <div class="srs-section-content">
                    <div class="srs-form-grid">
                        <div class="srs-form-group">
                            <label><?php _e('Currency', 'srs-ai-chatbot'); ?></label>
                            <select name="token_settings[currency]">
                                <option value="USD" <?php selected($token_settings['currency'] ?? 'USD', 'USD'); ?>>USD</option>
                                <option value="EUR" <?php selected($token_settings['currency'] ?? 'USD', 'EUR'); ?>>EUR</option>
                                <option value="GBP" <?php selected($token_settings['currency'] ?? 'USD', 'GBP'); ?>>GBP</option>
                                <option value="JPY" <?php selected($token_settings['currency'] ?? 'USD', 'JPY'); ?>>JPY</option>
                            </select>
                        </div>
                    </div>

                    <h3><?php _e('OpenAI Pricing (per 1K tokens)', 'srs-ai-chatbot'); ?></h3>
                    <table class="srs-table">
                        <thead>
                            <tr>
                                <th><?php _e('Model', 'srs-ai-chatbot'); ?></th>
                                <th><?php _e('Input Price', 'srs-ai-chatbot'); ?></th>
                                <th><?php _e('Output Price', 'srs-ai-chatbot'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $openai_pricing = $token_settings['openai_pricing'] ?? array();
                            $default_openai = array(
                                'gpt-3.5-turbo' => array('input' => 0.0015, 'output' => 0.002),
                                'gpt-4' => array('input' => 0.03, 'output' => 0.06),
                                'gpt-4o' => array('input' => 0.005, 'output' => 0.015)
                            );
                            ?>
                            <?php foreach ($default_openai as $model => $prices): ?>
                            <tr>
                                <td><?php echo esc_html($model); ?></td>
                                <td>
                                    <input type="number" 
                                           step="0.000001" 
                                           name="token_settings[openai_pricing][<?php echo esc_attr($model); ?>][input]" 
                                           value="<?php echo esc_attr($openai_pricing[$model]['input'] ?? $prices['input']); ?>">
                                </td>
                                <td>
                                    <input type="number" 
                                           step="0.000001" 
                                           name="token_settings[openai_pricing][<?php echo esc_attr($model); ?>][output]" 
                                           value="<?php echo esc_attr($openai_pricing[$model]['output'] ?? $prices['output']); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3><?php _e('OpenRouter Pricing (per 1K tokens)', 'srs-ai-chatbot'); ?></h3>
                    <table class="srs-table">
                        <thead>
                            <tr>
                                <th><?php _e('Model', 'srs-ai-chatbot'); ?></th>
                                <th><?php _e('Input Price', 'srs-ai-chatbot'); ?></th>
                                <th><?php _e('Output Price', 'srs-ai-chatbot'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $openrouter_pricing = $token_settings['openrouter_pricing'] ?? array();
                            $default_openrouter = array(
                                'anthropic/claude-3-haiku' => array('input' => 0.00025, 'output' => 0.00125),
                                'anthropic/claude-3-sonnet' => array('input' => 0.003, 'output' => 0.015)
                            );
                            ?>
                            <?php foreach ($default_openrouter as $model => $prices): ?>
                            <tr>
                                <td><?php echo esc_html($model); ?></td>
                                <td>
                                    <input type="number" 
                                           step="0.000001" 
                                           name="token_settings[openrouter_pricing][<?php echo esc_attr($model); ?>][input]" 
                                           value="<?php echo esc_attr($openrouter_pricing[$model]['input'] ?? $prices['input']); ?>">
                                </td>
                                <td>
                                    <input type="number" 
                                           step="0.000001" 
                                           name="token_settings[openrouter_pricing][<?php echo esc_attr($model); ?>][output]" 
                                           value="<?php echo esc_attr($openrouter_pricing[$model]['output'] ?? $prices['output']); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <?php submit_button(__('Save Settings', 'srs-ai-chatbot'), 'primary', 'submit'); ?>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.srs-tab-nav a').on('click', function(e) {
        e.preventDefault();
        
        var tabId = $(this).attr('href');
        
        // Update active tab
        $('.srs-tab-nav a').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding content
        $('.srs-tab-content').removeClass('active');
        $(tabId).addClass('active');
    });

    // Reindex content button
    $('.srs-reindex-content').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e("Reindexing...", "srs-ai-chatbot"); ?>');
        
        $.post(ajaxurl, {
            action: 'srs_ai_chatbot_reindex_content',
            nonce: '<?php echo wp_create_nonce("srs_ai_chatbot_admin_nonce"); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e("Content reindexed successfully!", "srs-ai-chatbot"); ?>');
            } else {
                alert('<?php _e("Error reindexing content.", "srs-ai-chatbot"); ?>');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e("Reindex All Content", "srs-ai-chatbot"); ?>');
        });
    });
});
</script>
