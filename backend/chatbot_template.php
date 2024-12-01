<?php
/*
Plugin Name: Custom AI Chatbot
Description: A customizable WordPress plugin for AI chatbot with text-to-speech and avatar functionalities.
Version: 4.0
Author: Neil Mahajan
*/

require 'vendor/autoload.php';

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;

// Plugin shortcode
add_shortcode('chatbot', 'chatbot_html');

function chatbot_html() {
    $avatar_url = plugins_url('{{AVATAR}}', __FILE__);
    $language = '{{LANGUAGE}}'; // 'en-US' or 'fr-CA'
    $placeholder_text = $language === 'fr-CA' ? 'Écrivez votre message ici...' : 'Type your message here...';

    echo <<<HTML
    <div id="chatbot-container" style="text-align: center; max-width: 400px; margin: 0 auto;">
        <img src="{$avatar_url}" alt="Chatbot Avatar" style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 20px;">
        <div id="chat-output" style="height: 300px; border: 1px solid #ccc; overflow-y: scroll; padding: 10px; margin-bottom: 10px;"></div>
        <input id="chat-input" type="text" placeholder="{$placeholder_text}" style="width: 100%; padding: 10px; margin-bottom: 10px;">
        <button onclick="sendMessage()" style="padding: 10px 20px;">Send</button>
    </div>
    <script>
        async function sendMessage() {
            const input = document.getElementById('chat-input').value;
            if (!input) return;

            const output = document.getElementById('chat-output');
            output.innerHTML += `<div><strong>You:</strong> ${input}</div>`;
            document.getElementById('chat-input').value = '';

            const response = await fetch('/wp-json/chatbot/v1/message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: input })
            });

            const data = await response.json();
            output.innerHTML += `<div><strong>Bot:</strong> ${data.response}</div>`;
            output.scrollTop = output.scrollHeight;

            const audio = new Audio(data.audioUrl);
            audio.play();
        }
    </script>
HTML;
}

// Register REST API route
add_action('rest_api_init', function () {
    register_rest_route('chatbot/v1', '/message', [
        'methods' => 'POST',
        'callback' => 'process_message',
        'permission_callback' => '__return_true',
    ]);
});

function process_message(WP_REST_Request $request) {
    $message = $request->get_param('message');
    $systemMessage = '{{KNOWLEDGE_BASE}}';
    $predefinedQuestions = '{{PREDEFINED_QUESTIONS}}';
    $language = '{{LANGUAGE}}'; // 'en-US' or 'fr-CA'
    $voiceName = $language === 'fr-CA' ? 'fr-CA-Journey-D' : 'en-US-Wavenet-D';

    // OpenAI ChatGPT API
    $openai_api_key = 'sk-YOUR_OPENAI_API_KEY';
    $openai_url = 'https://api.openai.com/v1/chat/completions';

    $response = wp_remote_post($openai_url, [
        'headers' => [
            'Authorization' => "Bearer $openai_api_key",
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $message],
            ],
        ]),
    ]);

    $responseBody = json_decode(wp_remote_retrieve_body($response), true);
    $reply = $responseBody['choices'][0]['message']['content'] ?? 'I am unable to respond at the moment.';

    // Google Cloud Text-to-Speech API
    $client = new TextToSpeechClient([
        'credentials' => __DIR__ . '/gcp-text-to-speech-demo-service-account.json',
    ]);

    $input = (new SynthesisInput())->setText($reply);
    $voice = (new VoiceSelectionParams())
        ->setLanguageCode($language)
        ->setName($voiceName);
    $audioConfig = (new AudioConfig())->setAudioEncoding(AudioConfig::MP3);

    $ttsResponse = $client->synthesizeSpeech($input, $voice, $audioConfig);
    $audioContent = $ttsResponse->getAudioContent();
    $audioUrl = 'data:audio/mp3;base64,' . base64_encode($audioContent);

    $client->close();

    return new WP_REST_Response([
        'response' => $reply,
        'audioUrl' => $audioUrl,
    ]);
}
?>