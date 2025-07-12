# SRS AI ChatBot WordPress Plugin

A comprehensive, intelligent AI chatbot plugin for WordPress that integrates with multiple AI providers including OpenAI, OpenRouter, and Open WebUI.

## Features

### 🤖 Multi-Provider AI Support
- **OpenAI** - GPT-3.5 Turbo, GPT-4, GPT-4o
- **OpenRouter** - Access to Claude, Llama, Mistral, and more
- **Open WebUI** - Self-hosted local models

### 💬 Intelligent Conversation Management
- Persistent chat sessions across pages
- Conversation memory with configurable message limits
- Session management and analytics
- Real-time typing indicators

### 📁 File Upload & Processing
- Support for PDF, DOCX, TXT, JPG, PNG files
- Automatic text extraction from documents
- Image analysis with vision-capable models
- Secure file handling with expiration dates

### 🛒 WooCommerce Integration
- Order status lookup by order number
- Product information queries
- Customer order history
- Configurable order response templates

### 📊 Advanced Analytics
- Token usage tracking and cost calculation
- Daily usage charts and statistics
- Session analytics and user engagement metrics
- Export capabilities for reporting

### 🎯 Contact Management
- Automatic contact information extraction
- Lead capture and notification system
- Contact status management
- Export contacts to CSV

### 🎨 Flexible Display Options
- **Widget Mode** - Floating chat button
- **Inline Mode** - Embedded chat interface
- **Shortcode** - `[srs_ai_chatbot id="1"]`
- **Elementor Widget** - Drag-and-drop integration

### 🔧 Content Training
- Automatic site content indexing
- WordPress posts and pages integration
- WooCommerce product information
- Custom post type support

### 🔒 Security & Privacy
- Secure file uploads with validation
- Session-based user tracking
- Configurable data retention policies
- GDPR-friendly design

## Installation

1. Upload the plugin files to `/wp-content/plugins/srs-ai-chatbot/`
2. Activate the plugin through WordPress admin
3. Configure your AI API credentials in Settings
4. Create your first chatbot
5. Add the chat widget to your site

## Configuration

### API Setup

#### OpenAI
1. Get your API key from [OpenAI Platform](https://platform.openai.com/api-keys)
2. Go to AI ChatBot > Settings > API Settings
3. Enter your OpenAI API key
4. Test the connection

#### OpenRouter
1. Sign up at [OpenRouter](https://openrouter.ai/)
2. Get your API key from the dashboard
3. Configure in AI ChatBot > Settings > API Settings
4. Choose from 100+ available models

#### Open WebUI
1. Set up your Open WebUI instance
2. Configure the base URL and authentication token
3. Test connection to ensure proper setup

### Chatbot Creation

1. Go to AI ChatBot > Chatbots > Add New
2. Configure:
   - **Name** - Display name for your chatbot
   - **System Prompt** - Instructions for AI behavior
   - **Greeting Message** - First message users see
   - **Model** - Choose AI model to use
   - **Temperature** - Control response creativity (0.0-1.0)
   - **Max Tokens** - Limit response length
   - **Avatar** - Upload chatbot image

### Display Options

#### Widget Mode (Default)
Automatically appears as floating button on all pages.

#### Shortcode
```
[srs_ai_chatbot id="1"]
[srs_ai_chatbot slug="support-bot" width="100%" height="600px"]
```

#### Elementor Widget
1. Edit page with Elementor
2. Search for "SRS AI ChatBot"
3. Drag widget to desired location
4. Configure appearance settings

## File Upload Configuration

### Supported File Types
- **PDF** - Automatic text extraction
- **DOCX** - Microsoft Word documents
- **TXT** - Plain text files
- **JPG/PNG** - Images for vision models

### Security Settings
- Maximum file size limit
- Allowed file types
- Automatic file cleanup
- Virus scanning (if available)

## WooCommerce Integration

### Order Lookup
Customers can ask about their orders using:
- "Where is my order #12345?"
- "What's the status of order 12345?"
- "Track my order 12345 with email john@example.com"

### Product Queries
- "Tell me about Product Name"
- "What's the price of SKU123?"
- "Is Product XYZ in stock?"

## Analytics & Reporting

### Usage Metrics
- Total conversations and messages
- Token usage and costs by model
- Average response times
- Contact conversion rates

### Export Options
- Chat history CSV/JSON export
- Contact list exports
- Usage analytics reports
- Token usage summaries

## Advanced Features

### Content Training
The plugin automatically indexes your WordPress content to provide relevant responses:

1. **Post Types** - Configure which post types to include
2. **Automatic Updates** - Content reindexed when posts are updated
3. **Search Integration** - AI uses site content to answer questions
4. **Vector Search** - Optional for enhanced content matching

### Session Management
- Persistent sessions across page loads
- Configurable session timeouts
- Session analytics and tracking
- User journey analysis

### Cost Management
- Real-time token usage tracking
- Cost estimation by model
- Usage alerts and limits
- Monthly/daily spending reports

## Customization

### Styling
The plugin includes comprehensive CSS classes for customization:

```css
.srs-chatbot-widget { /* Main widget container */ }
.srs-chatbot-window { /* Chat window */ }
.srs-chatbot-message-user { /* User messages */ }
.srs-chatbot-message-bot { /* Bot messages */ }
```

### Hooks & Filters

#### Actions
- `srs_ai_chatbot_message_sent` - After message is sent
- `srs_ai_chatbot_contact_captured` - When contact info is found
- `srs_ai_chatbot_session_started` - New session created

#### Filters
- `srs_ai_chatbot_display_widget` - Control widget visibility
- `srs_ai_chatbot_system_prompt` - Modify system prompts
- `srs_ai_chatbot_response` - Filter AI responses

## Requirements

- **WordPress** 6.0 or higher
- **PHP** 8.0 or higher
- **MySQL** 5.7 or higher
- **cURL** extension enabled
- **OpenSSL** for secure API calls

## Optional Dependencies

- **WooCommerce** 6.0+ for e-commerce features
- **Elementor** 3.0+ for visual page building
- **PDF Parser** library for enhanced PDF processing

## Support

### Documentation
Full documentation available at [srswebsolutions.com/docs/ai-chatbot](https://srswebsolutions.com/docs/ai-chatbot)

### Support Channels
- **Email**: support@srswebsolutions.com
- **GitHub**: [Issues and feature requests](https://github.com/srswebsolutions/srs-ai-chatbot)
- **WordPress Forum**: Plugin support forum

## Changelog

### Version 1.0.0
- Initial release
- Multi-provider AI support (OpenAI, OpenRouter, Open WebUI)
- File upload and processing
- WooCommerce integration
- Analytics and reporting
- Contact management
- Elementor integration
- Responsive design

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [SRS Web Solutions](https://srswebsolutions.com)

Built with modern WordPress development practices and the latest AI technologies.
