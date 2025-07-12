/**
 * SRS AI ChatBot Admin JavaScript
 */

(function($) {
    'use strict';

    const SRSChatBotAdmin = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initConnectionTests();
        },

        bindEvents: function() {
            // Confirm delete actions
            $(document).on('click', '.srs-action-delete', function(e) {
                if (!confirm(srs_ai_chatbot_admin.strings.confirm_delete)) {
                    e.preventDefault();
                    return false;
                }
            });

            // API connection test buttons
            $(document).on('click', '.srs-test-connection', function(e) {
                e.preventDefault();
                SRSChatBotAdmin.testConnection($(this));
            });

            // Form validation
            $('form').on('submit', function(e) {
                return SRSChatBotAdmin.validateForm($(this));
            });

            // Auto-save settings
            $('.srs-auto-save').on('change', function() {
                SRSChatBotAdmin.autoSave($(this));
            });

            // Filter forms
            $('.srs-filter-form').on('submit', function(e) {
                e.preventDefault();
                SRSChatBotAdmin.applyFilters($(this));
            });

            // Export buttons
            $('.srs-export-btn').on('click', function(e) {
                e.preventDefault();
                SRSChatBotAdmin.exportData($(this));
            });
        },

        initTabs: function() {
            $('.srs-tab-nav a').on('click', function(e) {
                e.preventDefault();
                
                const tabId = $(this).attr('href');
                const tabContainer = $(this).closest('.srs-tabs').parent();
                
                // Update active tab
                $(this).closest('.srs-tab-nav').find('a').removeClass('active');
                $(this).addClass('active');
                
                // Show corresponding content
                tabContainer.find('.srs-tab-content').removeClass('active');
                tabContainer.find(tabId).addClass('active');
            });
        },

        initConnectionTests: function() {
            // Auto-test connections on page load if API keys are present
            $('.srs-api-key').each(function() {
                if ($(this).val().length > 10) {
                    const testBtn = $(this).closest('.srs-form-group').find('.srs-test-connection');
                    if (testBtn.length && !testBtn.hasClass('tested')) {
                        // Auto-test after short delay
                        setTimeout(() => {
                            SRSChatBotAdmin.testConnection(testBtn);
                        }, 1000);
                    }
                }
            });
        },

        testConnection: function(button) {
            const provider = button.data('provider');
            const model = button.data('model') || null;
            const statusEl = button.siblings('.srs-connection-status');
            
            // Show loading state
            button.prop('disabled', true).text(srs_ai_chatbot_admin.strings.testing_connection);
            statusEl.removeClass('srs-connection-success srs-connection-error').text('');
            
            $.ajax({
                url: srs_ai_chatbot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'srs_ai_chatbot_test_connection',
                    nonce: srs_ai_chatbot_admin.nonce,
                    provider: provider,
                    model: model
                },
                success: function(response) {
                    if (response.success) {
                        statusEl.addClass('srs-connection-success')
                               .text(srs_ai_chatbot_admin.strings.connection_successful);
                        button.addClass('tested');
                    } else {
                        statusEl.addClass('srs-connection-error')
                               .text(srs_ai_chatbot_admin.strings.connection_failed + ' ' + response.data);
                    }
                },
                error: function() {
                    statusEl.addClass('srs-connection-error')
                           .text(srs_ai_chatbot_admin.strings.connection_failed + ' Network error');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        },
        validateForm: function(form) {
            let isValid = true;
            
            // Check required fields
            form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            // API key validation
            form.find('.srs-api-key').each(function() {
                const value = $(this).val().trim();
                if (value && value.length < 10) {
                    $(this).addClass('error');
                    alert('API key appears to be too short.');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            return isValid;
        },

        autoSave: function(element) {
            const form = element.closest('form');
            const formData = new FormData(form[0]);
            formData.append('action', 'srs_ai_chatbot_auto_save');
            formData.append('nonce', srs_ai_chatbot_admin.nonce);
            
            // Show saving indicator
            const indicator = element.siblings('.srs-save-indicator');
            indicator.text('Saving...').show();
            
            $.ajax({
                url: srs_ai_chatbot_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        indicator.text('Saved').addClass('success');
                    } else {
                        indicator.text('Error saving').addClass('error');
                    }
                },
                error: function() {
                    indicator.text('Error saving').addClass('error');
                },
                complete: function() {
                    setTimeout(() => {
                        indicator.fadeOut();
                    }, 2000);
                }
            });
        },

        applyFilters: function(form) {
            const formData = form.serialize();
            const currentUrl = new URL(window.location);
            
            // Update URL parameters
            const params = new URLSearchParams(formData);
            params.forEach((value, key) => {
                if (value) {
                    currentUrl.searchParams.set(key, value);
                } else {
                    currentUrl.searchParams.delete(key);
                }
            });
            
            // Reload page with filters
            window.location.href = currentUrl.toString();
        },

        exportData: function(button) {
            const exportType = button.data('export');
            const format = button.data('format') || 'csv';
            
            // Build export URL
            const url = new URL(srs_ai_chatbot_admin.ajax_url);
            url.searchParams.set('action', 'srs_ai_chatbot_export');
            url.searchParams.set('type', exportType);
            url.searchParams.set('format', format);
            url.searchParams.set('nonce', srs_ai_chatbot_admin.nonce);
            
            // Add current filters
            $('.srs-filter-form input, .srs-filter-form select').each(function() {
                if ($(this).val()) {
                    url.searchParams.set($(this).attr('name'), $(this).val());
                }
            });
            
            // Trigger download
            window.open(url.toString());
        },

        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="srs-notification srs-notification-${type}">
                    ${message}
                    <button class="srs-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
            
            // Manual close
            notification.find('.srs-notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        },

        initCharts: function() {
            // Initialize Chart.js charts if available
            if (typeof Chart !== 'undefined') {
                $('.srs-chart').each(function() {
                    const chartData = JSON.parse($(this).attr('data-chart'));
                    const ctx = this.getContext('2d');
                    
                    new Chart(ctx, {
                        type: chartData.type || 'line',
                        data: chartData.data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            ...chartData.options
                        }
                    });
                });
            }
        },

        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        formatCurrency: function(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SRSChatBotAdmin.init();
        SRSChatBotAdmin.initCharts();
    });

    // Make SRSChatBotAdmin globally available
    window.SRSChatBotAdmin = SRSChatBotAdmin;

})(jQuery);
