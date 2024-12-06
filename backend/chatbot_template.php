<?php
/*
Plugin Name: Custom Multilingual Chatbot ChatBot Popup with Avatar and Google TTS
Description: A chatbot plugin with integrated Google Text-to-Speech and a popup interface with an avatar supporting English and French (Canadian) based on shortcode attributes.
Version: 4.5
Author: Neil Mahajan
*/

require 'vendor/autoload.php'; // Google Cloud SDK autoloader

use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;





// Define the chatbot shortcode
function chatbot_avatar_shortcode($atts)
{
    // Extract language attribute (default to English)
    $attributes = shortcode_atts([
        'language' => 'en-US', // Default language
    ], $atts);

    // Set up language-specific settings
    $languageCode = $attributes['language'];
    $greetingMessage = $languageCode === 'fr-CA'
        ? 'Bonjour ! Comment puis-je vous aider aujourd’hui ?'
        : 'Hello! How can I assist you today?';
    $placeholderText = $languageCode === 'fr-CA'
        ? 'Tapez votre message ici...'
        : 'Type your message here...';
    $buttonText = $languageCode === 'fr-CA' ? 'Envoyer' : 'Send';

    // Avatar
    $avatar_url = plugin_dir_url(__FILE__) . '{{AVATAR_FILENAME}}';

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
            <p><strong>ChatBot:</strong> <?php echo esc_html($greetingMessage); ?></p>
        </div>
        <div id="chat-input-container">
            <input type="text" id="chat-input" placeholder="<?php echo esc_html($placeholderText); ?>">
            <button id="chat-submit"><?php echo esc_html($buttonText); ?></button>
        </div>
        <audio id="chat-audio" style="display:none;"></audio>
    </div>
    <style>
        #chatbot-popup {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: #fff;
            font-family: Arial, sans-serif;
            z-index: 1000;
        }
        #chat-avatar {
            text-align: center;
            margin-top: 10px;
            display: block;
        }
        #chat-avatar img {
            width: 80px;
            height: auto;
            border-radius: 50%;
        }
        #chatbot-header {
            padding: 10px;
            background: #007bff;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #chat-output {
            height: 150px;
            overflow-y: auto;
            padding: 10px;
            border-top: 1px solid #ccc;
        }
        #chat-input-container {
            display: flex;
            padding: 10px;
        }
        #chat-input {
            flex: 1;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        #chat-submit {
            padding: 5px 10px;
            margin-left: 5px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        #chat-submit:hover {
            background: #0056b3;
        }
    </style>
    <script>
        document.getElementById('chatbot-minimize').addEventListener('click', function () {
            const chatOutput = document.getElementById('chat-output');
            const chatAvatar = document.getElementById('chat-avatar');
            const chatInputContainer = document.getElementById('chat-input-container');
            const isVisible = chatOutput.style.display !== 'none';
            chatOutput.style.display = isVisible ? 'none' : 'block';
            chatInputContainer.style.display = isVisible ? 'none' : 'flex';
            chatAvatar.style.display = isVisible ? 'none' : 'block';
        });

        document.getElementById('chat-submit').addEventListener('click', async function () {
            const input = document.getElementById('chat-input').value;
            const output = document.getElementById('chat-output');
            const audio = document.getElementById('chat-audio');
            if (input.trim() === '') return;

            output.innerHTML += `<p><strong>You:</strong> ${input}</p>`;
            document.getElementById('chat-input').value = '';

            try {
                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=chatbot_avatar&message=' + encodeURIComponent(input) + '&language=<?php echo esc_js($languageCode); ?>'
                });
                const result = await response.json();
                output.innerHTML += `<p><strong>ChatBot:</strong> ${result.text}</p>`;
                if (result.audio) {
                    audio.src = 'data:audio/mp3;base64,' + result.audio;
                        audio.style.display = 'block';
                        audio.play();
                    }
            } catch (error) {
                    output.innerHTML += `<p><strong>Error:</strong> Unable to process the request.</p>`;
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('chatbot_avatar', 'chatbot_avatar_shortcode');

// AJAX handler for chatbot responses
function chatbot_avatar_ajax_handler()
{
    $apiKey = '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';

    // Load predefined Q&A and language preference
    $predefinedQA = {{PREDEFINED_QA}};
    $language = sanitize_text_field($_POST['language']);;

    $chatbot = new ChatBotWithAvatar($apiKey, $predefinedQA, $language);

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $response = $chatbot->sendMessage($message);

    echo json_encode($response);
    wp_die();
}

add_action('wp_ajax_chatbot_avatar', 'chatbot_avatar_ajax_handler');
add_action('wp_ajax_nopriv_chatbot_avatar', 'chatbot_avatar_ajax_handler');

class ChatBotWithAvatar
{
    private $authorization;
    private $endpoint;
    private $conversationHistory;
    private $predefinedQA;
    private $languageCode;

    public function __construct($apiKey, $predefinedQA, $languageCode)
    {
        $this->authorization = $apiKey;
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->conversationHistory = [];
        $this->predefinedQA = $predefinedQA; // Array of predefined Q&A pairs
        $this->languageCode = $languageCode; // 'en-US' or 'fr-CA'
    }

    public function sendMessage($message)
    {
        $this->conversationHistory[] = ['role' => 'user', 'content' => $message];

        // Add predefined Q&A to system message
        $systemMessage = '{{KNOWLEDGE_BASE}}';
        if (!empty($this->predefinedQA)) {
            $systemMessage .= "\nHere are some predefined questions and answers:\n";
            foreach ($this->predefinedQA as $qa) {
                $systemMessage .= "Q: {$qa['question']}\nA: {$qa['answer']}\n";
            }
        }

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
            $voice->setLanguageCode($this->languageCode);
            $voice->setName($this->languageCode === 'fr-CA' ? 'fr-CA-Journey-D' : 'en-US-Wavenet-D');

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
?>