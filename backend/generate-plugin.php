<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
        $frontendAvatarPath = '../frontend/images/' . $avatarFileName;
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

    // Retrieve the OpenAI API key from the environment variable
    $openaiApiKey = getenv('OPENAI_API_KEY');
    $pluginTemplate = str_replace('{{OPENAI_API_KEY}}', $openaiApiKey, $pluginTemplate);

    // Save the generated plugin file
    $outputFilename = '../generated-plugins/chatbot-' . uniqid() . '.php';
    if (file_put_contents($outputFilename, $pluginTemplate)) {
        // Create a zip file containing the generated plugin files
        $zip = new ZipArchive();
        $zipFilename = '../generated-plugins/chatbot.zip';

        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add the generated plugin file to the zip
            $zip->addFile($outputFilename, basename($outputFilename));

            // Add the avatar image to the zip
            $zip->addFile($generatedPluginsAvatarPath, basename($generatedPluginsAvatarPath));

            // Add the vendor folder to the zip
            $vendorDir = realpath('../generated-plugins/vendor/');
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($vendorDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'vendor/' . substr($filePath, strlen($vendorDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            // Add the service account key file to the zip
            $zip->addFile('../generated-plugins/gcp-text-to-speech-service-account-key.json', 'gcp-text-to-speech-service-account-key.json');

            // Close the zip file
            $zip->close();

            // Return the zip file for download
            echo json_encode(['message' => 'Plugin created successfully!', 'file' => $zipFilename]);
        } else {
            echo json_encode(['message' => 'Error creating zip file.']);
        }
    } else {
        echo json_encode(['message' => 'Error saving plugin file.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>