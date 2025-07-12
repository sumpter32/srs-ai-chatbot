<?php
/**
 * API Handler for multiple AI providers
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_API {

    /**
     * API settings
     */
    private $api_settings;

    /**
     * Token pricing
     */
    private $token_pricing;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_settings = get_option('srs_ai_chatbot_api_settings', array());
        $this->token_pricing = get_option('srs_ai_chatbot_token_settings', array());
    }

    /**
     * Chat completion
     */
    public function chat_completion($chatbot, $messages) {
        $start_time = microtime(true);

        try {
            switch ($chatbot->api_provider) {
                case 'openai':
                    $response = $this->openai_chat_completion($chatbot, $messages);
                    break;
                case 'openrouter':
                    $response = $this->openrouter_chat_completion($chatbot, $messages);
                    break;
                case 'open_webui':
                    $response = $this->open_webui_chat_completion($chatbot, $messages);
                    break;
                default:
                    throw new Exception('Unsupported API provider: ' . $chatbot->api_provider);
            }

            $response['response_time'] = microtime(true) - $start_time;
            return $response;

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'response_time' => microtime(true) - $start_time
            );
        }
    }
    /**
     * OpenAI API chat completion
     */
    private function openai_chat_completion($chatbot, $messages) {
        $api_key = $this->api_settings['openai_api_key'] ?? '';
        
        if (empty($api_key)) {
            throw new Exception('OpenAI API key not configured');
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => $chatbot->model,
            'messages' => $messages,
            'temperature' => floatval($chatbot->temperature),
            'max_tokens' => intval($chatbot->max_tokens)
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );

        $response = $this->make_api_request($url, $data, $headers);
        
        if (!$response['success']) {
            throw new Exception($response['error']);
        }

        $result = $response['data'];
        
        if (isset($result['error'])) {
            throw new Exception($result['error']['message']);
        }

        $usage = array(
            'input_tokens' => $result['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $result['usage']['completion_tokens'] ?? 0,
            'total_tokens' => $result['usage']['total_tokens'] ?? 0
        );

        $usage['cost'] = $this->calculate_cost('openai', $chatbot->model, $usage);

        return array(
            'success' => true,
            'message' => $result['choices'][0]['message']['content'],
            'model' => $result['model'],
            'usage' => $usage
        );
    }
    /**
     * OpenRouter API chat completion
     */
    private function openrouter_chat_completion($chatbot, $messages) {
        $api_key = $this->api_settings['openrouter_api_key'] ?? '';
        
        if (empty($api_key)) {
            throw new Exception('OpenRouter API key not configured');
        }

        $url = 'https://openrouter.ai/api/v1/chat/completions';
        
        $data = array(
            'model' => $chatbot->model,
            'messages' => $messages,
            'temperature' => floatval($chatbot->temperature),
            'max_tokens' => intval($chatbot->max_tokens)
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => home_url(),
            'X-Title' => get_bloginfo('name') . ' - SRS AI ChatBot'
        );

        $response = $this->make_api_request($url, $data, $headers);
        
        if (!$response['success']) {
            throw new Exception($response['error']);
        }

        $result = $response['data'];
        
        if (isset($result['error'])) {
            throw new Exception($result['error']['message']);
        }

        $usage = array(
            'input_tokens' => $result['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $result['usage']['completion_tokens'] ?? 0,
            'total_tokens' => $result['usage']['total_tokens'] ?? 0
        );

        $usage['cost'] = $this->calculate_cost('openrouter', $chatbot->model, $usage);

        return array(
            'success' => true,
            'message' => $result['choices'][0]['message']['content'],
            'model' => $result['model'],
            'usage' => $usage
        );
    }
    /**
     * Open WebUI API chat completion
     */
    private function open_webui_chat_completion($chatbot, $messages) {
        $base_url = $this->api_settings['open_webui_url'] ?? '';
        $token = $this->api_settings['open_webui_token'] ?? '';
        
        if (empty($base_url)) {
            throw new Exception('Open WebUI URL not configured');
        }

        $url = rtrim($base_url, '/') . '/api/v1/chat/completions';
        
        $data = array(
            'model' => $chatbot->model,
            'messages' => $messages,
            'temperature' => floatval($chatbot->temperature),
            'max_tokens' => intval($chatbot->max_tokens),
            'stream' => false
        );

        $headers = array(
            'Content-Type' => 'application/json'
        );

        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $response = $this->make_api_request($url, $data, $headers);
        
        if (!$response['success']) {
            throw new Exception($response['error']);
        }

        $result = $response['data'];
        
        if (isset($result['error'])) {
            throw new Exception($result['error']['message']);
        }

        $usage = array(
            'input_tokens' => $result['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $result['usage']['completion_tokens'] ?? 0,
            'total_tokens' => $result['usage']['total_tokens'] ?? 0,
            'cost' => 0 // Local models typically don't have costs
        );

        return array(
            'success' => true,
            'message' => $result['choices'][0]['message']['content'],
            'model' => $result['model'] ?? $chatbot->model,
            'usage' => $usage
        );
    }
    /**
     * Make API request
     */
    private function make_api_request($url, $data, $headers) {
        $args = array(
            'method' => 'POST',
            'timeout' => 60,
            'headers' => $headers,
            'body' => json_encode($data)
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($http_code !== 200) {
            return array(
                'success' => false,
                'error' => 'HTTP ' . $http_code . ': ' . $body
            );
        }

        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Invalid JSON response'
            );
        }

        return array(
            'success' => true,
            'data' => $decoded
        );
    }

    /**
     * Calculate token cost
     */
    private function calculate_cost($provider, $model, $usage) {
        $pricing = $this->token_pricing[$provider . '_pricing'] ?? array();
        $model_pricing = $pricing[$model] ?? null;

        if (!$model_pricing) {
            return 0;
        }

        $input_cost = ($usage['input_tokens'] / 1000) * ($model_pricing['input'] ?? 0);
        $output_cost = ($usage['output_tokens'] / 1000) * ($model_pricing['output'] ?? 0);

        return round($input_cost + $output_cost, 6);
    }
    /**
     * Test API connection
     */
    public function test_connection($provider, $model = null) {
        try {
            $test_messages = array(
                array(
                    'role' => 'user',
                    'content' => 'Hello, please respond with "API connection successful"'
                )
            );

            $chatbot = (object) array(
                'model' => $model ?: 'gpt-3.5-turbo',
                'api_provider' => $provider,
                'temperature' => 0.1,
                'max_tokens' => 50
            );

            $response = $this->chat_completion($chatbot, $test_messages);

            if ($response['success']) {
                return array(
                    'success' => true,
                    'message' => 'Connection successful',
                    'response_time' => $response['response_time'],
                    'model' => $response['model']
                );
            } else {
                return array(
                    'success' => false,
                    'error' => $response['error']
                );
            }

        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get available models for provider
     */
    public function get_available_models($provider) {
        switch ($provider) {
            case 'openai':
                return array(
                    'gpt-4o' => 'GPT-4o',
                    'gpt-4o-mini' => 'GPT-4o Mini',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                );
            case 'openrouter':
                return array(
                    'anthropic/claude-3-5-sonnet' => 'Claude 3.5 Sonnet',
                    'anthropic/claude-3-haiku' => 'Claude 3 Haiku',
                    'meta-llama/llama-3.1-8b-instruct' => 'Llama 3.1 8B',
                    'mistralai/mistral-7b-instruct' => 'Mistral 7B',
                    'openai/gpt-4o' => 'GPT-4o (via OpenRouter)'
                );
            case 'open_webui':
                // For Open WebUI, models depend on the installation
                return array(
                    'llama3' => 'Llama 3',
                    'mistral' => 'Mistral',
                    'codellama' => 'Code Llama',
                    'custom' => 'Custom Model'
                );
            default:
                return array();
        }
    }
}
