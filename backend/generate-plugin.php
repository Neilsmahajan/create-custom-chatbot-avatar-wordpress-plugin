<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = ($_POST['qaPairs'] ?? '[]');
    $avatar = $_POST['avatar'] ?? '';
    $avatarUpload = $_FILES['avatarUpload'] ?? null;
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);

    // Format QA pairs for chatbot
    $formattedQAPairsChatbot = var_export($escapedQAPairs, true);
    $formattedQAPairsChatbot = str_replace("'", '"', $formattedQAPairsChatbot);

    // Format QA pairs for FAQ buttons
    $formattedQAPairsFaqs = htmlspecialchars(json_encode($escapedQAPairs, JSON_HEX_APOS | JSON_HEX_QUOT));

    $primaryColor = $_POST['primaryColor'] ?? '#007bff'; // Default primary color
    $secondaryColor = $_POST['secondaryColor'] ?? '#f4f4f9'; // Default secondary color
    $avatarFilename = '';

    // Handle avatar selection or upload
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

    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA}}', $formattedQAPairsChatbot, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA_FAQS}}', $formattedQAPairsFaqs, $pluginTemplate);
    $pluginTemplate = str_replace('{{AVATAR_FILENAME}}', $avatarFileName, $pluginTemplate);
    $pluginTemplate = str_replace('{{PRIMARY_COLOR}}', $primaryColor, $pluginTemplate);
    $pluginTemplate = str_replace('{{SECONDARY_COLOR}}', $secondaryColor, $pluginTemplate);

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