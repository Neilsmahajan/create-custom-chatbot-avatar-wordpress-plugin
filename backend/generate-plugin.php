<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = $_POST['qaPairs'] ?? '[]';

    // Escape special characters for PHP string
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);

    // Format Q&A pairs into a valid PHP array syntax
    $formattedQAPairs = var_export($escapedQAPairs, true);

    // Correct single quotes in the Q&A array output
    $formattedQAPairs = preg_replace('/\'([^\']+?)\'\s*=>/', '"$1" =>', $formattedQAPairs);
    $formattedQAPairs = preg_replace('/=>\s*\'([^\']+?)\'/', '=> "$1"', $formattedQAPairs);

    // Load the template
    $templatePath = '../backend/chatbot_template.php';
    $pluginTemplate = file_get_contents($templatePath);

    // Replace placeholders with the escaped Knowledge Base and QA pairs
    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA}}', $formattedQAPairs, $pluginTemplate);

    // Save the generated plugin file
    $outputFilename = '../generated-plugins/chatbot-' . uniqid() . '.php';
    file_put_contents($outputFilename, $pluginTemplate);

    echo json_encode(['message' => 'Plugin created successfully!', 'file' => $outputFilename]);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>