/**
 * SRS AI ChatBot Public JavaScript
 */

(function($) {
    'use strict';

    class SRSChatBot {
        constructor(element) {
            this.element = $(element);
            this.chatbotId = this.element.data('chatbot-id');
            this.sessionId = localStorage.getItem('srs_chatbot_session_' + this.chatbotId) || null;
            this.isOpen = false;
            this.isTyping = false;
            this.uploadedFiles = [];

            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeChat();
        }

        bindEvents() {
            // Toggle button click
            this.element.find('.srs-chatbot-toggle-btn').on('click', (e) => {
                e.preventDefault();
                this.toggleChat();
            });

            // Minimize button click
            this.element.find('.srs-chatbot-minimize').on('click', (e) => {
                e.preventDefault();
                this.closeChat();
            });

            // New chat button click
            this.element.find('.srs-chatbot-new-chat').on('click', (e) => {
                e.preventDefault();
                this.startNewChat();
            });

            // Send button click
            this.element.find('.srs-chatbot-send-btn').on('click', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Enter key in input
            this.element.find('.srs-chatbot-input').on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // File input change
            this.element.find('.srs-chatbot-file-input').on('change', (e) => {
                this.handleFileSelect(e);
            });

            // File button click
            this.element.find('.srs-chatbot-file-btn').on('click', (e) => {
                e.preventDefault();
                this.element.find('.srs-chatbot-file-input').click();
            });

            // Auto-resize textarea
            this.element.find('.srs-chatbot-input').on('input', (e) => {
                this.autoResizeTextarea(e.target);
            });
        }

        initializeChat() {
            // Generate session ID if not exists
            if (!this.sessionId) {
                this.sessionId = 'srs_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('srs_chatbot_session_' + this.chatbotId, this.sessionId);
            }
        }
        toggleChat() {
            const window = this.element.find('.srs-chatbot-window');
            
            if (this.isOpen) {
                window.fadeOut(300);
                this.isOpen = false;
            } else {
                window.fadeIn(300);
                this.isOpen = true;
                this.element.find('.srs-chatbot-input').focus();
            }
        }

        closeChat() {
            this.element.find('.srs-chatbot-window').fadeOut(300);
            this.isOpen = false;
        }

        startNewChat() {
            // Clear session
            localStorage.removeItem('srs_chatbot_session_' + this.chatbotId);
            this.sessionId = null;
            
            // Clear messages except greeting
            const messagesContainer = this.element.find('.srs-chatbot-messages');
            messagesContainer.find('.srs-chatbot-message:not(:first)').remove();
            
            // Clear input
            this.element.find('.srs-chatbot-input').val('');
            this.clearFilePreview();
            
            // Initialize new session
            this.initializeChat();
        }

        sendMessage() {
            const input = this.element.find('.srs-chatbot-input');
            const message = input.val().trim();

            if (!message && this.uploadedFiles.length === 0) {
                return;
            }

            if (this.isTyping) {
                return;
            }

            // Add user message to chat
            this.addMessage(message, 'user');
            
            // Clear input
            input.val('');
            this.autoResizeTextarea(input[0]);
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'srs_ai_chatbot_send_message');
            formData.append('nonce', srs_ai_chatbot_ajax.nonce);
            formData.append('chatbot_id', this.chatbotId);
            formData.append('session_id', this.sessionId);
            formData.append('message', message);

            // Add uploaded files
            this.uploadedFiles.forEach((file, index) => {
                formData.append('files[]', file);
            });

            // Send AJAX request
            $.ajax({
                url: srs_ai_chatbot_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideTypingIndicator();
                    
                    if (response.success) {
                        this.addMessage(response.data.message, 'assistant');
                        this.sessionId = response.data.session_id;
                        localStorage.setItem('srs_chatbot_session_' + this.chatbotId, this.sessionId);
                    } else {
                        this.addMessage(srs_ai_chatbot_ajax.strings.error, 'assistant');
                    }
                    
                    this.clearFilePreview();
                },
                error: () => {
                    this.hideTypingIndicator();
                    this.addMessage(srs_ai_chatbot_ajax.strings.error, 'assistant');
                }
            });
        }
        addMessage(message, type) {
            const messagesContainer = this.element.find('.srs-chatbot-messages');
            const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            const messageHtml = `
                <div class="srs-chatbot-message srs-chatbot-message-${type}">
                    <div class="srs-chatbot-message-content">${this.escapeHtml(message)}</div>
                    <div class="srs-chatbot-message-time">${time}</div>
                </div>
            `;
            
            messagesContainer.append(messageHtml);
            this.scrollToBottom();
        }

        showTypingIndicator() {
            if (this.isTyping) return;
            
            this.isTyping = true;
            const messagesContainer = this.element.find('.srs-chatbot-messages');
            
            const typingHtml = `
                <div class="srs-chatbot-typing">
                    <div class="srs-chatbot-typing-dots">
                        <div class="srs-chatbot-typing-dot"></div>
                        <div class="srs-chatbot-typing-dot"></div>
                        <div class="srs-chatbot-typing-dot"></div>
                    </div>
                    <span>${srs_ai_chatbot_ajax.strings.typing}</span>
                </div>
            `;
            
            messagesContainer.append(typingHtml);
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            this.isTyping = false;
            this.element.find('.srs-chatbot-typing').remove();
        }

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            const preview = this.element.find('.srs-chatbot-file-preview');
            
            files.forEach(file => {
                if (this.validateFile(file)) {
                    this.uploadedFiles.push(file);
                    this.addFileToPreview(file);
                }
            });
            
            if (this.uploadedFiles.length > 0) {
                preview.show();
            }
            
            // Clear file input
            event.target.value = '';
        }

        validateFile(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (file.size > maxSize) {
                alert(srs_ai_chatbot_ajax.strings.file_too_large);
                return false;
            }
            
            if (!allowedTypes.includes(fileExtension)) {
                alert(srs_ai_chatbot_ajax.strings.file_type_not_allowed);
                return false;
            }
            
            return true;
        }
        addFileToPreview(file) {
            const preview = this.element.find('.srs-chatbot-file-preview');
            const fileIndex = this.uploadedFiles.length - 1;
            
            const fileHtml = `
                <div class="srs-chatbot-file-item" data-index="${fileIndex}">
                    <span>${file.name} (${this.formatFileSize(file.size)})</span>
                    <button class="srs-chatbot-file-remove" data-index="${fileIndex}">×</button>
                </div>
            `;
            
            preview.append(fileHtml);
            
            // Bind remove event
            preview.find(`[data-index="${fileIndex}"] .srs-chatbot-file-remove`).on('click', (e) => {
                this.removeFile($(e.target).data('index'));
            });
        }

        removeFile(index) {
            this.uploadedFiles.splice(index, 1);
            this.element.find(`[data-index="${index}"]`).remove();
            
            if (this.uploadedFiles.length === 0) {
                this.clearFilePreview();
            }
        }

        clearFilePreview() {
            this.uploadedFiles = [];
            const preview = this.element.find('.srs-chatbot-file-preview');
            preview.empty().hide();
        }

        autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        scrollToBottom() {
            const messagesContainer = this.element.find('.srs-chatbot-messages');
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        }

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, (m) => map[m]);
        }
    }

    // Initialize chatbots when document is ready
    $(document).ready(function() {
        $('[id^="srs-ai-chatbot-"]').each(function() {
            new SRSChatBot(this);
        });
    });

})(jQuery);
