<?php
/**
 * Plugin Name: Easy AI Chat Embed
 * Plugin URI: https://rankboost.pro/easy-ai-chat-embed
 * Description: A FREE easy to use plugin to embed an AI chatbot powered by Mistral AI.
 * Version: 1.0.1
 * Author: <a href="https://rankboost.pro/easy-ai-chat-embed-pro/">Rank Boost Pro</a>
 * Text Domain: Easy-Ai-Chat-MISTRAL-v1-FREE
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Include the core plugin class.
require_once plugin_dir_path(__FILE__) . 'includes/class-plugin-name.php';

// Run the plugin.
function run_easy_ai_chat_embed() {
    $plugin = new Easy_AI_Chat_Embed();
    $plugin->run();
}
run_easy_ai_chat_embed();
?>