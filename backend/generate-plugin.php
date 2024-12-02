<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = $_POST['qaPairs'] ?? '[]';
    $language = $_POST['language'] ?? 'en-US';

    // Escape special characters for PHP string
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);

    // Format Q&A pairs into a valid PHP array syntax
    $formattedQAPairs = var_export($escapedQAPairs, true);
    $formattedQAPairs = str_replace("'", '"', $formattedQAPairs); // Convert single quotes to double quotes

    // Load the template
    $templatePath = '../backend/chatbot_template.php';
    $pluginTemplate = file_get_contents($templatePath);

    // Replace placeholders
    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA}}', $formattedQAPairs, $pluginTemplate);
    $pluginTemplate = str_replace('{{LANGUAGE}}', $language, $pluginTemplate);

    // Save the generated plugin file
    $outputFilename = '../generated-plugins/chatbot-' . uniqid() . '.php';
    file_put_contents($outputFilename, $pluginTemplate);

    echo json_encode(['message' => 'Plugin created successfully!', 'file' => $outputFilename]);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>