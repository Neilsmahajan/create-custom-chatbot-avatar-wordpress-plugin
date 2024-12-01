<?php
/*
Plugin Name: ChatBot Popup with Avatar and Google TTS
Description: A chatbot plugin with integrated Google Text-to-Speech and a popup interface with an avatar.
Version: 4.1
Author: Neil Mahajan
*/

require 'vendor/autoload.php'; // Google Cloud SDK autoloader

use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;

class ChatBotWithAvatar
{
    private $authorization;
    private $endpoint;
    private $conversationHistory;

    public function __construct($apiKey)
    {
        $this->authorization = $apiKey;
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->conversationHistory = [];
    }

    public function sendMessage($message)
    {
        $this->conversationHistory[] = ['role' => 'user', 'content' => $message];

        $systemMessage = '{{KNOWLEDGE_BASE}}';

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemMessage]],
                $this->conversationHistory
            )
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->authorization
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return ['text' => 'Error: ' . curl_error($ch), 'audio' => ''];
        }

        curl_close($ch);

        $arrResult = json_decode($response, true);
        if (!isset($arrResult["choices"][0]["message"]["content"])) {
            return ['text' => 'Error: Unexpected API response format.', 'audio' => ''];
        }

        $resultMessage = $arrResult["choices"][0]["message"]["content"];
        $this->conversationHistory[] = ['role' => 'assistant', 'content' => $resultMessage];

        return [
            'text' => $resultMessage,
            'audio' => $this->generateAudio($resultMessage)
        ];
    }

    private function generateAudio($text)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . plugin_dir_path(__FILE__) . 'gcp-text-to-speech-demo-service-account.json');

        try {
            $client = new TextToSpeechClient();

            $synthesisInput = new SynthesisInput();
            $synthesisInput->setText($text);

            $voice = new VoiceSelectionParams();
            $voice->setLanguageCode('en-US');
            $voice->setName('en-US-Wavenet-D');

            $audioConfig = new AudioConfig();
            $audioConfig->setAudioEncoding(AudioEncoding::MP3);

            $response = $client->synthesizeSpeech($synthesisInput, $voice, $audioConfig);
            $client->close();

            return base64_encode($response->getAudioContent());
        } catch (Exception $e) {
            return ''; // Return empty audio if TTS fails
        }
    }
}

// AJAX handler for chatbot responses
function chatbot_avatar_ajax_handler()
{
    $apiKey = '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';
    $chatbot = new ChatBotWithAvatar($apiKey);

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $response = $chatbot->sendMessage($message);

    echo json_encode($response);
    wp_die();
}

// Register AJAX actions
add_action('wp_ajax_chatbot_avatar', 'chatbot_avatar_ajax_handler');
add_action('wp_ajax_nopriv_chatbot_avatar', 'chatbot_avatar_ajax_handler');

// Chatbot popup frontend with avatar
function chatbot_avatar_shortcode()
{
    $avatar_url = plugin_dir_url(__FILE__) . 'avatar.jpg';
    ob_start();
    ?>
    <div id="chatbot-popup">
        <div id="chat-avatar">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="ChatBot Avatar">
        </div>
        <div id="chatbot-header">
            <strong>ChatBot</strong>
            <button id="chatbot-minimize">&minus;</button>
        </div>
        <div id="chat-output">
            <p><strong>ChatBot:</strong> Hello! How can I assist you today?</p>
        </div>
        <div id="chat-input-container">
            <input type="text" id="chat-input" placeholder="Type your message here...">
            <button id="chat-submit">Send</button>
        </div>
        <audio id="chat-audio" style="display:none;"></audio>
    </div>
    <script>
        document.getElementById('chat-submit').addEventListener('click', function () {
            const input = document.getElementById('chat-input');
            const output = document.getElementById('chat-output');
            const audio = document.getElementById('chat-audio');
            const userMessage = input.value.trim();
            if (userMessage === '') return;

            output.innerHTML += `<p><strong>You:</strong> ${userMessage}</p>`;
            input.value = '';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=chatbot_avatar&message=' + encodeURIComponent(userMessage)
            })
                .then(response => response.json())
                .then(data => {
                    output.innerHTML += `<p><strong>ChatBot:</strong> ${data.text}</p>`;
                    if (data.audio) {
                        audio.src = 'data:audio/mp3;base64,' + data.audio;
                        audio.style.display = 'block';
                        audio.play();
                    }
                    output.scrollTop = output.scrollHeight;
                })
                .catch(error => {
                    output.innerHTML += `<p><strong>Error:</strong> Unable to process the request.</p>`;
                });
        });
    </script>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('chatbot_avatar', 'chatbot_avatar_shortcode');

// Add chatbot to footer
add_action('wp_footer', function () {
    echo do_shortcode('[chatbot_avatar]');
});
?>