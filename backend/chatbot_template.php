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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

add_action('wp_ajax_generate_audio', 'generate_audio_callback');
add_action('wp_ajax_nopriv_generate_audio', 'generate_audio_callback');

function generate_audio_callback() {
    // Use the user-provided GCP credentials filename placeholder
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . plugin_dir_path(__FILE__) . '{{GCP_CREDENTIALS_FILENAME}}');
    require_once 'vendor/autoload.php';

    $answer = $_POST['answer'] ?? '';
    $languageCode = $_POST['language'] ?? 'en-US';

    if (!empty($answer)) {
        $audioBase64 = generateAudio($answer, $languageCode);
        wp_send_json(['audio' => $audioBase64]);
    } else {
        wp_send_json(['error' => 'No answer provided.']);
    }
}

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
    $emailRequestMessage = $languageCode === 'fr-CA'
        ? 'Veuillez entrer votre email pour recevoir une copie de la transcription du chat à la fin. Vous pouvez également poser votre question sans fournir votre email.'
        : 'Please enter your email to receive a copy of the chat transcript at the end. You can also ask your question without providing your email.';
    $emailPlaceholder = $languageCode === 'fr-CA'
        ? 'Entrez votre email ici...'
        : 'Enter your email here...';
    $consentText = $languageCode === 'fr-CA'
        ? 'Je consens à recevoir une transcription du chat par email.'
        : 'I consent to receive a chat transcript via email.';
    $confirmationMessage = $languageCode === 'fr-CA'
        ? 'Merci ! Vous recevrez une transcription du chat par email à la fin de la conversation.'
        : 'Thank you! You will receive a chat transcript via email at the end of the conversation.';
    $inactivityMessage = $languageCode === 'fr-CA'
        ? 'Êtes-vous toujours là ? Y a-t-il autre chose avec laquelle je peux vous aider ?'
        : 'Are you still there? Is there anything else I can help you with?';
    $followUpMessage = $languageCode === 'fr-CA'
        ? 'J\'espère que cela vous a été utile. Si vous avez fourni votre email, vous recevrez une transcription du chat pour référence future.'
        : 'I hope this was helpful. If you provided your email, you will receive a chat transcript for future reference.';
    $placeholderText = $languageCode === 'fr-CA'
        ? 'Tapez votre message ici...'
        : 'Type your message here...';
    $buttonText = $languageCode === 'fr-CA' ? 'Envoyer' : 'Send';
    $submitEmailText = $languageCode === 'fr-CA' ? 'Soumettre' : 'Submit';
    // Avatars
    $speakingAvatarUrl = plugin_dir_url(__FILE__) . '{{SPEAKING_AVATAR_FILENAME}}';
    $idleAvatarUrl = plugin_dir_url(__FILE__) . '{{IDLE_AVATAR_FILENAME}}';
    $primaryColor = '{{PRIMARY_COLOR}}';
    $secondaryColor = '{{SECONDARY_COLOR}}';

    // Determine if speaking avatar is a video file
    $speakingAvatarIsVideo = preg_match('/\.(mp4|mov|webm)$/i', '{{SPEAKING_AVATAR_FILENAME}}');

    ob_start();
    ?>
    <div id="chatbot-popup">
        <div id="chat-avatar">
            <?php if ($speakingAvatarIsVideo): ?>
                <!-- Use video element if speaking avatar is a video -->
                <video id="avatar-video" src="<?php echo esc_url($speakingAvatarUrl); ?>" style="display:none;" muted loop></video>
                <img id="avatar-img" src="<?php echo esc_url($idleAvatarUrl); ?>" alt="ChatBot Avatar">
            <?php else: ?>
                <!-- Use image element for non-video avatars -->
                <img id="avatar-img" src="<?php echo esc_url($idleAvatarUrl); ?>" alt="ChatBot Avatar">
            <?php endif; ?>
        </div>
        <div id="faq-container">
            <?php
            // Predefined QA pairs passed as a JSON-encoded string for FAQs
            $predefinedFaqs = '{{PREDEFINED_QA_FAQS}}';
            $qaPairs = json_decode(html_entity_decode($predefinedFaqs), true);

            // Check if decoding was successful
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($qaPairs)) {
                echo '<p>Failed to load FAQs. Please check the predefined QA data.</p>';
            } else {
                $targetLanguage = $languageCode === 'en-US' ? 'en' : 'fr';
                // Generate the translated FAQ buttons
                echo translateFaqs($qaPairs, $targetLanguage);
            }
            ?>
        </div>
        <div id="chatbot-header">
            <strong>ChatBot</strong>
            <button id="chatbot-minimize">&minus;</button>
        </div>
        <div id="chat-output">
            <p><strong>ChatBot:</strong> <?php echo esc_html($greetingMessage); ?></p>
            <p><strong>ChatBot:</strong> <?php echo esc_html($emailRequestMessage); ?></p>
            <div id="email-consent-container">
                <input type="email" id="user-email" placeholder="<?php echo esc_html($emailPlaceholder); ?>">
                <div class="checkbox-group">
                    <input type="checkbox" id="email-consent">
                    <label for="email-consent"><?php echo esc_html($consentText); ?></label>
                </div>
                <button id="submit-email"><?php echo esc_html($submitEmailText); ?></button>
            </div>
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
            width: 350px;
            border: 1px solid <?php echo $primaryColor; ?>;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1), 0 2px 10px rgba(0, 0, 0, 0.08);
            background: <?php echo $secondaryColor; ?>;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        #chat-avatar {
            text-align: center;
            margin: 15px auto 5px;
            display: block;
            position: relative;
        }
        @keyframes hologramGlow {
            0% { box-shadow: 0 0 10px rgba(77, 213, 255, 0.5), 0 0 20px rgba(77, 213, 255, 0.2); }
            50% { box-shadow: 0 0 15px rgba(77, 213, 255, 0.6), 0 0 30px rgba(77, 213, 255, 0.3); }
            100% { box-shadow: 0 0 10px rgba(77, 213, 255, 0.5), 0 0 20px rgba(77, 213, 255, 0.2); }
        }
        #chat-avatar img, #chat-avatar video {
            width: 80px;
            height: 80px;
            object-fit: cover;
            background: transparent;
            box-shadow: 0 0 15px rgba(77, 213, 255, 0.5), 0 0 25px rgba(77, 213, 255, 0.3);
            filter: drop-shadow(0 0 5px rgba(77, 213, 255, 0.7));
            animation: hologramGlow 3s infinite;
            transform: perspective(500px) rotateX(5deg);
            clip-path: polygon(
                0% 5%,
                5% 0%,
                95% 0%,
                100% 5%,
                100% 95%,
                95% 100%,
                5% 100%,
                0% 95%
            );
        }
        #chat-avatar:before {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 15px;
            background: radial-gradient(ellipse at center, rgba(77, 213, 255, 0.3) 0%, rgba(77, 213, 255, 0) 70%);
            border-radius: 50%;
            filter: blur(5px);
        }
        #chatbot-header {
            padding: 12px 15px;
            background: <?php echo $primaryColor; ?>;
            color: white;
            border-top-left-radius: 11px;
            border-top-right-radius: 11px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: 500;
        }
        #chatbot-minimize {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 0 5px;
            margin: 0;
            width: auto;
            transition: transform 0.2s ease;
        }
        #chatbot-minimize:hover {
            transform: scale(1.2);
        }
        #chat-output {
            height: 220px;
            overflow-y: auto;
            padding: 12px 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
            background: #fff;
            font-size: 13px;
        }
        #chat-output p {
            margin: 0 0 10px;
            line-height: 1.5;
        }
        #chat-output strong {
            color: <?php echo $primaryColor; ?>;
            font-weight: 600;
        }
        #chat-input-container {
            display: flex;
            padding: 10px 12px;
            border-top: 1px solid rgba(0,0,0,0.05);
            background: #f9f9f9;
            border-bottom-left-radius: 11px;
            border-bottom-right-radius: 11px;
        }
        #chat-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 18px;
            font-size: 13px;
            transition: border 0.2s;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
        #chat-input:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        #chat-submit {
            padding: 8px 15px;
            margin-left: 8px;
            border: none;
            background: <?php echo $primaryColor; ?>;
            color: white;
            border-radius: 18px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background-color 0.2s, transform 0.1s;
            width: auto;
        }
        #chat-submit:hover {
            background: <?php echo $primaryColor; ?>ee;
            transform: translateY(-1px);
        }
        #chat-submit:active {
            transform: translateY(1px);
        }
        .faq-button {
            margin: 4px;
            padding: 8px 12px;
            border: 1px solid <?php echo $primaryColor; ?>;
            border-radius: 15px;
            background: <?php echo $secondaryColor; ?>;
            color: <?php echo $primaryColor; ?>;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .faq-button:hover {
            background: <?php echo $primaryColor; ?>;
            color: white;
            transform: translateY(-1px);
        }
        #faq-container {
            padding: 8px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: <?php echo $secondaryColor; ?>;
            text-align: center;
            max-height: 120px;
            overflow-y: auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        #email-consent-container {
            margin-bottom: 12px;
            padding: 10px;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
        }
        #user-email {
            width: 100%;
            margin-bottom: 8px;
            padding: 8px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 13px;
        }
        #user-email:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .checkbox-group input {
            margin-right: 5px;
        }
        .checkbox-group label {
            font-size: 12px;
            color: #555;
        }
        #submit-email {
            padding: 7px 14px;
            background: <?php echo $primaryColor; ?>;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: background-color 0.2s;
        }
        #submit-email:hover {
            background: <?php echo $primaryColor; ?>ee;
        }
        
        /* Responsive styles for mobile devices */
        @media (max-width: 480px) {
            #chatbot-popup {
                width: 100%;
                right: 0;
                bottom: 0;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
            #chat-avatar img, #chat-avatar video {
                width: 70px;
                height: 70px;
            }
        }
    </style>
    <script>
        function sendQuickReply(answer, buttonId) {
            const output = document.getElementById('chat-output');
            const inputContainer = document.getElementById('chat-input-container');
            const question = document.getElementById(buttonId).textContent;

            // Display the predefined answer in the chat
            output.innerHTML += `<p><strong>You:</strong> ${question}</p>`;
            output.innerHTML += `<p><strong>ChatBot:</strong> ${answer}</p>`;
            output.scrollTop = output.scrollHeight;

            // Record the predefined question and answer in the transcript
            chatTranscript += `You: ${question}\n`;
            chatTranscript += `ChatBot: ${answer}\n`;

            // Hide only the clicked button
            const clickedButton = document.getElementById(buttonId);
            if (clickedButton) {
                clickedButton.style.display = 'none';
            }

            // Show the input container after the quick reply
            inputContainer.style.display = 'flex';

            // Make an AJAX call to generate the audio
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'generate_audio',
                    answer: answer,
                    language: '<?php echo esc_js($languageCode); ?>'
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.audio) {
                        const audio = document.getElementById('chat-audio');
                        audio.src = 'data:audio/mp3;base64,' + data.audio;
                        audio.style.display = 'block';
                        audio.play();
                    } else {
                        console.error('Error generating audio:', data.error);
                    }
                })
                .catch(error => console.error('AJAX error:', error));
        }
        document.getElementById('chatbot-minimize').addEventListener('click', function () {
            const chatOutput = document.getElementById('chat-output');
            const chatAvatar = document.getElementById('chat-avatar');
            const chatInputContainer = document.getElementById('chat-input-container');
            const isVisible = chatOutput.style.display !== 'none';
            chatOutput.style.display = isVisible ? 'none' : 'block';
            chatInputContainer.style.display = isVisible ? 'none' : 'flex';
            chatAvatar.style.display = isVisible ? 'none' : 'block';
        });

        let userEmail = '';
        let emailConsent = false;
        let inactivityTimeout;
        let followUpTimeout;
        let chatTranscript = 'ChatBot: <?php echo esc_js($emailRequestMessage); ?>\n';

        document.getElementById('submit-email').addEventListener('click', function () {
            userEmail = document.getElementById('user-email').value;
            emailConsent = document.getElementById('email-consent').checked;
            document.getElementById('email-consent-container').style.display = 'none';

            const output = document.getElementById('chat-output');
            const confirmationMessage = '<?php echo esc_js($confirmationMessage); ?>';
            output.innerHTML += `<p><strong>ChatBot:</strong> ${confirmationMessage}</p>`;
            chatTranscript += `ChatBot: ${confirmationMessage}\n`;

            // Optionally play the confirmation message
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'generate_audio',
                    answer: confirmationMessage,
                    language: '<?php echo esc_js($languageCode); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.audio) {
                    const audio = document.getElementById('chat-audio');
                    audio.src = 'data:audio/mp3;base64,' + data.audio;
                    audio.style.display = 'block';
                    audio.play();
                } else {
                    console.error('Error generating audio:', data.error);
                }
            })
            .catch(error => console.error('AJAX error:', error));
        });

        document.getElementById('chat-submit').addEventListener('click', async function () {
            const input = document.getElementById('chat-input').value;
            const output = document.getElementById('chat-output');
            const audio = document.getElementById('chat-audio');

            if (input.trim() === '') return;

            output.innerHTML += `<p><strong>You:</strong> ${input}</p>`;
            chatTranscript += `You: ${input}\n`;
            document.getElementById('chat-input').value = '';

            clearTimeout(inactivityTimeout);
            clearTimeout(followUpTimeout);
            inactivityTimeout = setTimeout(showInactivityMessage, 120000); // 2 minute

            try {
                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=chatbot_avatar&message=' + encodeURIComponent(input) + '&language=<?php echo esc_js($languageCode); ?>' + '&userEmail=' + encodeURIComponent(userEmail) + '&emailConsent=' + emailConsent
                });
                const result = await response.json();
                output.innerHTML += `<p><strong>ChatBot:</strong> ${result.text}</p>`;
                chatTranscript += `ChatBot: ${result.text}\n`;
                if (result.audio) {
                    audio.src = 'data:audio/mp3;base64,' + result.audio;
                        audio.style.display = 'block';
                        audio.play();
                    }
            } catch (error) {
                    output.innerHTML += `<p><strong>Error:</strong> Unable to process the request.</p>`;
            }
        });

        // Add event listener for 'Enter' key to send message
        document.getElementById('chat-input').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('chat-submit').click();
            }
        });

        function showInactivityMessage() {
            const output = document.getElementById('chat-output');
            const inactivityMessage = '<?php echo esc_js($inactivityMessage); ?>';
            output.innerHTML += `<p><strong>ChatBot:</strong> ${inactivityMessage}</p>`;
            chatTranscript += `ChatBot: ${inactivityMessage}\n`;

            // Optionally play the inactivity message
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'generate_audio',
                    answer: inactivityMessage,
                    language: '<?php echo esc_js($languageCode); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.audio) {
                    const audio = document.getElementById('chat-audio');
                    audio.src = 'data:audio/mp3;base64,' + data.audio;
                    audio.style.display = 'block';
                    audio.play();
                } else {
                    console.error('Error generating audio:', data.error);
                }
            })
            .catch(error => console.error('AJAX error:', error));

            followUpTimeout = setTimeout(showFollowUpMessage, 60000); // 1 minute
        }

        function showFollowUpMessage() {
            if (emailConsent) {
                const output = document.getElementById('chat-output');
                const followUpMessage = '<?php echo esc_js($followUpMessage); ?>';
                output.innerHTML += `<p><strong>ChatBot:</strong> ${followUpMessage}</p>`;
                chatTranscript += `ChatBot: ${followUpMessage}\n`;

                // Optionally play the follow-up message
                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'generate_audio',
                        answer: followUpMessage,
                        language: '<?php echo esc_js($languageCode); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.audio) {
                        const audio = document.getElementById('chat-audio');
                        audio.src = 'data:audio/mp3;base64,' + data.audio;
                        audio.style.display = 'block';
                        audio.play();
                    } else {
                        console.error('Error generating audio:', data.error);
                    }
                })
                .catch(error => console.error('AJAX error:', error));

                // Send the chat transcript via email
                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'send_transcript_email',
                        email: userEmail,
                        transcript: chatTranscript,
                        language: '<?php echo esc_js($languageCode); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Transcript email sent successfully.');
                    } else {
                        console.error('Error sending transcript email:', data.error);
                    }
                })
                .catch(error => console.error('AJAX error:', error));
            }
        }

        inactivityTimeout = setTimeout(showInactivityMessage, 300000); // 5 minutes

        // Change avatar based on audio playback state
        const audioElement = document.getElementById('chat-audio');
        const avatarImg = document.getElementById('avatar-img');
        <?php if ($speakingAvatarIsVideo): ?>
        const avatarVideo = document.getElementById('avatar-video');
        
        audioElement.addEventListener('play', function () {
            avatarImg.style.display = 'none';
            avatarVideo.style.display = 'inline-block';
            avatarVideo.play();
        });

        audioElement.addEventListener('ended', function () {
            avatarVideo.style.display = 'none';
            avatarImg.style.display = 'inline-block';
            avatarVideo.pause();
            avatarVideo.currentTime = 0;
        });
        <?php else: ?>
        audioElement.addEventListener('play', function () {
            avatarImg.src = '<?php echo esc_url($speakingAvatarUrl); ?>';
        });

        audioElement.addEventListener('ended', function () {
            avatarImg.src = '<?php echo esc_url($idleAvatarUrl); ?>';
        });
        <?php endif; ?>
        
        // Initialize - ensure video is loaded
        <?php if ($speakingAvatarIsVideo): ?>
        document.addEventListener('DOMContentLoaded', function() {
            avatarVideo.load();
        });
        <?php endif; ?>
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('chatbot_avatar', 'chatbot_avatar_shortcode');

// AJAX handler for chatbot responses
function chatbot_avatar_ajax_handler()
{
    $apiKey = '{{OPENAI_API_KEY}}';

    // Load predefined Q&A and language preference
    $predefinedQA = {{PREDEFINED_QA}};
    $language = sanitize_text_field($_POST['language']);;

    $chatbot = new ChatBotWithAvatar($apiKey, $predefinedQA, $language);

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $response = $chatbot->sendMessage($message);

    echo json_encode($response);
    wp_die();
}

// Translate FAQs
function translateFaqs($qaPairs, $targetLanguage) {
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . plugin_dir_path(__FILE__) . '{{GCP_CREDENTIALS_FILENAME}}');
    $translateClient = new Google\Cloud\Translate\V2\TranslateClient();

    $translatedHtml = '';

    // Ensure $qaPairs is a valid array
    if (!is_array($qaPairs)) {
        $translatedHtml = '<p>No FAQs available.</p>'; // Fallback message
    }
    $counter = 0; // To create unique button IDs
    foreach ($qaPairs as $qaPair) {
        $question = $qaPair['question'];
        $answer = $qaPair['answer'];

        $question = $translateClient->translate($question, ['target' => $targetLanguage])['text'];
        $answer = $translateClient->translate($answer, ['target' => $targetLanguage])['text'];

        // Decode HTML entities in the translated text
        $question = html_entity_decode($question, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $answer = html_entity_decode($answer, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Create a unique ID for the button
        $buttonId = "faq-button-" . $counter;
        $counter++;

        // Build the translated FAQ button
        $translatedHtml .= '<button id="' . $buttonId . '" class="faq-button" onclick="sendQuickReply(`' . addslashes($answer) . '`, `' . $buttonId . '`)">' . htmlspecialchars($question) . '</button>';
    }

    return $translatedHtml;
}

function generateAudio($text, $languageCode = 'en-US')
{
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . plugin_dir_path(__FILE__) . '{{GCP_CREDENTIALS_FILENAME}}');
    
    try {
        $client = new TextToSpeechClient();

        $synthesisInput = new SynthesisInput();
        $synthesisInput->setText($text);

        $voice = new VoiceSelectionParams();
        $voice->setLanguageCode($languageCode);
        $voice->setName($languageCode === 'fr-CA' ? 'fr-CA-Journey-D' : 'en-US-Wavenet-D');

        $audioConfig = new AudioConfig();
        $audioConfig->setAudioEncoding(AudioEncoding::MP3);

        $response = $client->synthesizeSpeech($synthesisInput, $voice, $audioConfig);
        $client->close();

        return base64_encode($response->getAudioContent());
    } catch (Exception $e) {
        return ''; // Return empty audio if TTS fails
    }
}

add_action('wp_ajax_chatbot_avatar', 'chatbot_avatar_ajax_handler');
add_action('wp_ajax_nopriv_chatbot_avatar', 'chatbot_avatar_ajax_handler');

function sendTranscriptEmail($email, $transcript, $languageCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'neilsmahajan@gmail.com';
        $mail->Password = 'obihktdvplmjgywn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('neilsmahajan@gmail.com', 'Chatbot');
        $mail->addAddress($email);

        // Format the transcript for better readability
        $formattedTranscript = nl2br(htmlspecialchars_decode($transcript));
        $formattedTranscript = str_replace("\n", "<br><br>", $formattedTranscript);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $subject = $languageCode === 'fr-CA' ? 'Transcription du Chat' : 'Chat Transcript';
        $mail->Subject = $subject;
        $transcript = str_replace("\\'", "'", $transcript); // Fix backslashes before single quotes
        $mail->Body    = '<h1>' . $subject . '</h1><p>' . '<p>User email: ' . $email . '</p>' . $formattedTranscript . '</p>';

        $mail->send();

        // Send the same email to the site owner
        $mail->clearAddresses();
        $mail->addAddress('{{OWNER_EMAIL}}');
        $mail->send();
        
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// AJAX handler for sending transcript email
function send_transcript_email_handler() {
    $email = sanitize_email($_POST['email']);
    $transcript = sanitize_textarea_field($_POST['transcript']);
    $languageCode = sanitize_text_field($_POST['language']);

    if (!empty($email) && !empty($transcript)) {
        sendTranscriptEmail($email, $transcript, $languageCode);
        wp_send_json(['success' => true]);
    } else {
        wp_send_json(['success' => false, 'error' => 'Invalid email or transcript.']);
    }
}

add_action('wp_ajax_send_transcript_email', 'send_transcript_email_handler');
add_action('wp_ajax_nopriv_send_transcript_email', 'send_transcript_email_handler');

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
            'audio' => generateAudio($resultMessage, $this->languageCode)
        ];
    }
}
?>