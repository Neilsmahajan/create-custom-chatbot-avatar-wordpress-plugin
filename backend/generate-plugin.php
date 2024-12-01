<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'];
    $predefinedQuestions = $_POST['predefinedQuestions'];
    $language = $_POST['language'];
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

    // Read and customize template
    $pluginTemplate = file_get_contents('chatbot_template.php');
    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $knowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QUESTIONS}}', $predefinedQuestions, $pluginTemplate);
    $pluginTemplate = str_replace('{{LANGUAGE}}', $language, $pluginTemplate);
    $pluginTemplate = str_replace('{{AVATAR}}', $avatarPath, $pluginTemplate);

    // Save the plugin
    $outputPath = '../generated-plugins/chatbot-' . uniqid() . '.php';
    file_put_contents($outputPath, $pluginTemplate);

    echo json_encode(['message' => 'Plugin created successfully!', 'file' => $outputPath]);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>