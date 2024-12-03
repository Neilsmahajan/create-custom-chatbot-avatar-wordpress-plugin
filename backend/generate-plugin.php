<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = $_POST['qaPairs'] ?? '[]';
    $language = $_POST['language'] ?? 'en-US';
    $avatar = $_POST['avatar'] ?? '';

    // Escape special characters for PHP string
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);

    // Format Q&A pairs into a valid PHP array syntax
    $formattedQAPairs = var_export($escapedQAPairs, true);
    $formattedQAPairs = str_replace("'", '"', $formattedQAPairs); // Convert single quotes to double quotes

    // Determine translations for French (Canadian)
    $translations = [];
    if ($language === 'fr-CA') {
        $translations = [
            'chatbotTitle' => 'ChatBot (Assistant Virtuel)',
            'welcomeMessage' => 'Bonjour! Comment puis-je vous aider aujourd\'hui?',
            'placeholderText' => 'Tapez votre message ici...',
            'sendButton' => 'Envoyer',
        ];
    } else {
        $translations = [
            'chatbotTitle' => 'ChatBot',
            'welcomeMessage' => 'Hello! How can I assist you today?',
            'placeholderText' => 'Type your message here...',
            'sendButton' => 'Send',
        ];
    }
    if ($avatar) {
        $frontendAvatarPath = '../frontend/' . basename($avatar);
        $generatedPluginsAvatarPath = '../generated-plugins/' . basename($avatar);

        if (!copy($frontendAvatarPath, $generatedPluginsAvatarPath)) {
            echo json_encode(['message' => 'Error copying avatar image.']);
            exit;
        }
    } else {
        echo json_encode(['message' => 'No avatar selected.']);
        exit;
    }

    // Load the template and replace placeholders
    $pluginTemplate = file_get_contents('../backend/chatbot_template.php');

    // Replace placeholders
    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA}}', $formattedQAPairs, $pluginTemplate);
    $pluginTemplate = str_replace('{{LANGUAGE}}', $language, $pluginTemplate);
    $pluginTemplate = str_replace('{{CHATBOT_TITLE}}', $translations['chatbotTitle'], $pluginTemplate);
    $pluginTemplate = str_replace('{{WELCOME_MESSAGE}}', $translations['welcomeMessage'], $pluginTemplate);
    $pluginTemplate = str_replace('{{PLACEHOLDER_TEXT}}', $translations['placeholderText'], $pluginTemplate);
    $pluginTemplate = str_replace('{{SEND_BUTTON}}', $translations['sendButton'], $pluginTemplate);
    $pluginTemplate = str_replace('{{AVATAR_FILENAME}}', basename($avatar), $pluginTemplate);

    // Save the generated plugin file
    $outputFilename = '../generated-plugins/chatbot-' . uniqid() . '.php';
    if (file_put_contents($outputFilename, $pluginTemplate)) {
    echo json_encode(['message' => 'Plugin created successfully!', 'file' => $outputFilename]);
    } else {
        echo json_encode(['message' => 'Error saving plugin file.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>