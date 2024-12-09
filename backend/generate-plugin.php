<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = ($_POST['qaPairs'] ?? '[]');
    $faqButtonsHtml = '';
    $avatar = $_POST['avatar'] ?? '';
    $avatarUpload = $_FILES['avatarUpload'] ?? null;
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);
    $formattedQAPairs = var_export($escapedQAPairs, true);
    $formattedQAPairs = str_replace("'", '"', $formattedQAPairs); // Convert single quotes to double quotes
    $primaryColor = $_POST['primaryColor'] ?? '#007bff'; // Default primary color
    $secondaryColor = $_POST['secondaryColor'] ?? '#f4f4f9'; // Default secondary color
    $avatarFilename = '';
    foreach ($escapedQAPairs as $qaPair) {
        $question = htmlspecialchars($qaPair['question']);
        $answer = htmlspecialchars($qaPair['answer']);
        $faqButtonsHtml .= '<button class="faq-button" onclick="sendQuickReply(`' . addslashes($answer) . '`)">' . $question . '</button>';
    }

    // Determine translations for French (Canadian)
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
    $pluginTemplate = str_replace('{{AVATAR_FILENAME}}', $avatarFileName, $pluginTemplate);
    $pluginTemplate = str_replace('{{PRIMARY_COLOR}}', $primaryColor, $pluginTemplate);
    $pluginTemplate = str_replace('{{SECONDARY_COLOR}}', $secondaryColor, $pluginTemplate);
    $pluginTemplate = str_replace('{{FAQ_BUTTONS_HTML}}', $faqButtonsHtml, $pluginTemplate);

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