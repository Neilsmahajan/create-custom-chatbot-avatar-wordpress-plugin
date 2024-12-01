<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $predefinedQuestions = $_POST['predefinedQuestions'] ?? '';
    $language = $_POST['language'] ?? 'en-US';
    $avatarPath = '';

    // Handle uploaded avatar
    if (!empty($_FILES['avatarUpload']['tmp_name'])) {
        $uploadDir = '../uploads/';
        $uploadedFile = $uploadDir . basename($_FILES['avatarUpload']['name']);
        move_uploaded_file($_FILES['avatarUpload']['tmp_name'], $uploadedFile);
        $avatarPath = $uploadedFile;
    } elseif (!empty($_POST['avatar'])) {
        $avatarPath = $_POST['avatar'];
    }

    // Escape special characters to prevent syntax errors
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedPredefinedQuestions = addslashes($predefinedQuestions);

    // Read and customize the template
    $templatePath = '../backend/chatbot_template.php';
    $pluginTemplate = file_get_contents($templatePath);
    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QUESTIONS}}', $escapedPredefinedQuestions, $pluginTemplate);
    $pluginTemplate = str_replace('{{LANGUAGE}}', $language, $pluginTemplate);
    $pluginTemplate = str_replace('{{AVATAR}}', $avatarPath, $pluginTemplate);

    // Generate unique plugin filename
    $outputFilename = '../generated-plugins/chatbot-' . uniqid() . '.php';
    file_put_contents($outputFilename, $pluginTemplate);

    echo json_encode(['message' => 'Plugin created successfully!', 'file' => $outputFilename]);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>