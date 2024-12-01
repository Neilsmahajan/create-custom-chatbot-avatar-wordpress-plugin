<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';

    // Escape special characters for PHP string
    $escapedKnowledgeBase = addslashes($knowledgeBase);

    // Load the template
    $templatePath = '../backend/chatbot_template.php';
    $pluginTemplate = file_get_contents($templatePath);

    // Replace the placeholder with the escaped Knowledge Base
    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);

    // Save the generated plugin file
    $outputFilename = '../generated-plugins/chatbot-' . uniqid() . '.php';
    file_put_contents($outputFilename, $pluginTemplate);

    echo json_encode(['message' => 'Plugin created successfully!', 'file' => $outputFilename]);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>