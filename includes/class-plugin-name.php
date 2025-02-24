<?php
class Easy_AI_Chat_Embed {
    public function run() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('easy_ai_chat', array($this, 'render_chatbot'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    /**
     * Enqueue the JavaScript that will render the chatbot.
     */
    public function enqueue_scripts() {
        // Check if the API key is set before enqueuing the script.
        $settings = get_option('easy_ai_chat_settings');
        if (empty($settings) || empty($settings['api_key'])) {
            return;
        }

        // Ensure the script path is correct.
        wp_enqueue_script('easy-ai-chat', plugins_url('assets/js/chatbot-embed.js', dirname(__FILE__)), array(), '1.0.0', true);

        // Localize script to pass PHP variables to JavaScript.
        wp_localize_script('easy-ai-chat', 'easyAIChatSettings', array(
            'plugin_url' => plugins_url('/', dirname(__FILE__)),
            'button_color' => $settings['button_color'] ?? '#238a9d',
            'api_key' => $settings['api_key'] ?? '',
        ));
    }

    /**
     * Render the chatbot by including the aiscript.html template.
     *
     * @return string The rendered HTML of the chatbot.
     */
    public function render_chatbot() {
        $file_path = plugin_dir_path(dirname(__FILE__)) . 'assets/js/aiscript.html';
        if (!file_exists($file_path)) {
            return '<p>Chatbot template not found. Path: ' . esc_html($file_path) . '</p>';
        }
        ob_start();
        include $file_path;
        return ob_get_clean();
    }

    public function add_admin_menu() {
        add_options_page(
            'Easy AI Chat Settings',
            'Easy AI Chat',
            'manage_options',
            'easy_ai_chat',
            array($this, 'settings_page')
        );
    }

    public function settings_init() {
        // Properly register the setting with comprehensive arguments
        register_setting(
            'easyAIChat', // Option group
            'easy_ai_chat_settings', // Option name
            array(
                'type' => 'array', // Specify the type of data
                'description' => __('Easy AI Chat Mistral Settings', 'Easy-Ai-Chat-MISTRAL-v1-FREE'),
                'sanitize_callback' => array($this, 'easy_ai_chat_sanitize_settings'), // Use class method
                'show_in_rest' => array(
                    'schema' => array(
                        'type' => 'object',
                        'properties' => array(
                            'api_key' => array(
                                'type' => 'string',
                                'sanitize' => 'sanitize_text_field',
                            ),
                            'button_color' => array(
                                'type' => 'string',
                                'sanitize' => 'sanitize_hex_color',
                            ),
                            'mistral_model' => array(
                                'type' => 'string',
                                'enum' => array(
                                    // Free Models
                                    'mistral-small-latest', 
                                    'pixtral-12b-2409', 
                                    'open-mistral-nemo', 
                                    'open-codestral-mamba',
                                    
                                    // Premier Models
                                    'codestral-latest', 
                                    'mistral-large-latest', 
                                    'pixtral-large-latest', 
                                    'mistral-saba-latest',
                                    'ministral-3b-latest', 
                                    'ministral-8b-latest', 
                                    'mistral-embed', 
                                    'mistral-moderation-latest'
                                ),
                            ),
                        ),
                    ),
                ),
                'default' => array(
                    'api_key' => '',
                    'button_color' => '#238a9d',
                    'mistral_model' => 'mistral-small-latest'
                )
            )
        );

        add_settings_section(
            'easy_ai_chat_section',
            __('Chatbot Settings', 'Easy-Ai-Chat-MISTRAL-v1-FREE'),
            null,
            'easyAIChat'
        );

        add_settings_field(
            'button_color',
            __('Button Color', 'Easy-Ai-Chat-MISTRAL-v1-FREE'),
            array($this, 'render_color_field'),
            'easyAIChat',
            'easy_ai_chat_section',
            array(
                'label_for' => 'button_color',
                'class' => 'easy_ai_chat_row',
                'easy_ai_chat_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'api_key',
            __('Mistral API Key', 'Easy-Ai-Chat-MISTRAL-v1-FREE'),
            array($this, 'render_api_key_field'),
            'easyAIChat',
            'easy_ai_chat_section',
            array(
                'label_for' => 'api_key',
                'class' => 'easy_ai_chat_row',
                'easy_ai_chat_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'mistral_model',
            __('Mistral AI Model', 'Easy-Ai-Chat-MISTRAL-v1-FREE'),
            array($this, 'render_mistral_model_field'),
            'easyAIChat',
            'easy_ai_chat_section',
            array(
                'label_for' => 'mistral_model',
                'class' => 'easy_ai_chat_row',
                'easy_ai_chat_custom_data' => 'custom',
            )
        );
    }

    /**
     * Sanitize settings before saving to the database.
     *
     * @param array $input The unsanitized input.
     * @return array The sanitized input.
     */
    public function easy_ai_chat_sanitize_settings($input) {
        // Ensure input is an array
        if (!is_array($input)) {
            return array();
        }

        // Initialize the sanitized input array with default values
        $sanitized_input = array(
            'api_key' => '',
            'button_color' => '#238a9d',
            'mistral_model' => 'mistral-small-latest'
        );

        // Sanitize API Key (remove any non-alphanumeric characters except some special ones like '-' and '_')
        if (isset($input['api_key'])) {
            $sanitized_input['api_key'] = preg_replace('/[^a-zA-Z0-9\-_]/', '', $input['api_key']);
        }

        // Sanitize Button Color (must be a valid hex color)
        if (isset($input['button_color'])) {
            $sanitized_color = sanitize_hex_color($input['button_color']);
            $sanitized_input['button_color'] = $sanitized_color ?: '#238a9d';
        }

        // Sanitize Mistral model selection
        $allowed_models = array(
            // Free Models
            'mistral-small-latest', 
            'pixtral-12b-2409', 
            'open-mistral-nemo', 
            'open-codestral-mamba',
            
            // Premier Models
            'codestral-latest', 
            'mistral-large-latest', 
            'pixtral-large-latest', 
            'mistral-saba-latest',
            'ministral-3b-latest', 
            'ministral-8b-latest', 
            'mistral-embed', 
            'mistral-moderation-latest'
        );
        if (isset($input['mistral_model']) && in_array($input['mistral_model'], $allowed_models)) {
            $sanitized_input['mistral_model'] = $input['mistral_model'];
        }

        return $sanitized_input;
    }

    public function render_color_field($args) {
        $options = get_option('easy_ai_chat_settings');
        ?>
        <input type="color" id="<?php echo esc_attr($args['label_for']); ?>" name="easy_ai_chat_settings[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($options[$args['label_for']] ?? ($args['label_for'] == 'button_color' ? '#238a9d' : '')); ?>" class="regular-text">
        <?php
    }

    public function render_api_key_field($args) {
        $options = get_option('easy_ai_chat_settings');
        ?>
        <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="easy_ai_chat_settings[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($options[$args['label_for']] ?? ''); ?>" class="regular-text">
        <a href="https://mistral.ai/docs/getting-started" target="_blank">Get API Key</a>
        <?php
    }

    public function render_mistral_model_field($args) {
        $options = get_option('easy_ai_chat_settings');
        $models = array(
            // Free Models
            'mistral-small-latest' => 'Mistral Small (Latest)',
            'pixtral-12b-2409' => 'Pixtral 12B',
            'open-mistral-nemo' => 'Open Mistral Nemo',
            'open-codestral-mamba' => 'Open Codestral Mamba',
            
            // Premier Models
            'codestral-latest' => 'Codestral (Latest)',
            'mistral-large-latest' => 'Mistral Large (Latest)',
            'pixtral-large-latest' => 'Pixtral Large',
            'mistral-saba-latest' => 'Mistral Saba',
            'ministral-3b-latest' => 'Ministral 3B',
            'ministral-8b-latest' => 'Ministral 8B',
            'mistral-embed' => 'Mistral Embed',
            'mistral-moderation-latest' => 'Mistral Moderation'
        );
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>" name="easy_ai_chat_settings[<?php echo esc_attr($args['label_for']); ?>]" class="regular-text">
            <?php foreach ($models as $model_key => $model_name): ?>
                <option value="<?php echo esc_attr($model_key); ?>" <?php selected($options[$args['label_for']] ?? 'mistral-small-latest', $model_key); ?>>
                    <?php echo esc_html($model_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select the Mistral AI model to use for chat completions.</p>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('Use the following shortcode to embed the chat: [easy_ai_chat]', 'Easy-Ai-Chat-MISTRAL-v1-FREE'); ?></p>
        </div>
        <form action="options.php" method="post">
            <?php
            settings_fields('easyAIChat');
            do_settings_sections('easyAIChat');
            submit_button('Save Settings');
            ?>
        </form>
        <a href="https://rankboost.pro/easy-ai-chat-embed-pro/" target="_blank" class="button button-primary">
            <?php esc_html_e('Get More Features - GO PRO', 'Easy-Ai-Chat-MISTRAL-v1-FREE'); ?>
        </a>
        <?php
    }

    public function register_rest_api() {
        register_rest_route('mistral-chat/v1', '/query', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat_query'),
            'permission_callback' => '__return_true',
        ));
    }

    public function handle_chat_query(WP_REST_Request $request) {
        // Get your API key and selected model from WordPress options
        $settings = get_option('easy_ai_chat_settings');
        $api_key = $settings['api_key'];
        $selected_model = $settings['mistral_model'] ?? 'mistral-small-latest';
        
        // Get the user's message from the request
        $parameters = $request->get_json_params();
        $user_message = sanitize_text_field($parameters['message']);
        
        // Optional: Add rate limiting based on IP address
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? wp_unslash(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))) : '';
        $rate_limit_key = 'mistral_rate_limit_' . $ip_address;
        $current_count = get_transient($rate_limit_key) ?: 0;
        
        if ($current_count > 50) { // 50 requests per hour
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.', array('status' => 429));
        }
        
        set_transient($rate_limit_key, $current_count + 1, HOUR_IN_SECONDS);
        
        // Prepare the request to Mistral API
        $request_body = array(
            'model' => $selected_model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $user_message
                )
            ),
            'temperature' => 0.7,  // Add a default temperature
            'max_tokens' => 300,   // Add a reasonable max tokens
            'stream' => false,
            'safe_prompt' => false
        );
        
        // Prepare the request to Mistral API
        $response = wp_remote_post('https://api.mistral.ai/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($request_body),
            'timeout' => 30  // Increase timeout
        ));
        
        if (is_wp_error($response)) {
            // Properly escape the error message
            $error_message = esc_html($response->get_error_message());
            
            // Log using WordPress error logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('Easy AI Chat - Mistral API Connection Error: %s', $error_message));
            }
            
            return new WP_Error('api_error', sprintf('Failed to connect to Mistral API: %s', $error_message), array('status' => 500));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Validate JSON decoding
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Properly escape the JSON error message
            $json_error = esc_html(json_last_error_msg());
            
            // Log using WordPress error logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('Easy AI Chat - Mistral API JSON Decode Error: %s', $json_error));
            }
            
            return new WP_Error('api_error', sprintf('Failed to parse Mistral API response: %s', $json_error), array('status' => 500));
        }
        
        // Extract the AI's response based on Mistral API response structure
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'candidates' => array(
                    array(
                        'content' => array(
                            'parts' => array(
                                array(
                                    'text' => $data['choices'][0]['message']['content']
                                )
                            )
                        )
                    )
                )
            );
        }
        
        // If no response is found, log the issue
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Easy AI Chat - No valid response from Mistral API');
        }
        
        return new WP_Error('api_error', 'No response from Mistral API', array('status' => 500));
    }
}
