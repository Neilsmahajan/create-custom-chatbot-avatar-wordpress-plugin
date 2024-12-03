<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = $_POST['qaPairs'] ?? '[]';
    $language = $_POST['language'] ?? 'en-US';
    $avatar = $_POST['avatar'] ?? '';
    $avatarUpload = $_FILES['avatarUpload'] ?? null;
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);
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
    if ($avatarUpload) {
        $uploadDir = '../generated-plugins/';
        $uploadedFileName = basename($avatarUpload['name']);
        $uploadPath = $uploadDir . $uploadedFileName;

        if (move_uploaded_file($avatarUpload['tmp_name'], $uploadPath)) {
            $avatarFileName = $uploadedFileName;
        } else {
            echo json_encode(['message' => 'Error uploading custom avatar.']);
            exit;
        }
    } elseif ($avatar) {
        $avatarFileName = basename($avatar);
        $frontendAvatarPath = '../frontend/' . $avatarFileName;
        $generatedPluginsAvatarPath = '../generated-plugins/' . $avatarFileName;

        if (!copy($frontendAvatarPath, $generatedPluginsAvatarPath)) {
            echo json_encode(['message' => 'Error copying predefined avatar.']);
            exit;
        }
    } else {
        echo json_encode(['message' => 'No avatar selected or uploaded.']);
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
    $pluginTemplate = str_replace('{{AVATAR_FILENAME}}', $avatarFileName, $pluginTemplate);

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