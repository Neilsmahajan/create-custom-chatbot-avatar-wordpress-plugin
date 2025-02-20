<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $knowledgeBase = $_POST['knowledgeBase'] ?? '';
    $qaPairs = ($_POST['qaPairs'] ?? '[]');
    $speakingAvatar = $_POST['speakingAvatar'] ?? '';
    $speakingAvatarUpload = $_FILES['speakingAvatarUpload'] ?? null;
    $idleAvatar = $_POST['idleAvatar'] ?? '';
    $idleAvatarUpload = $_FILES['idleAvatarUpload'] ?? null;
    $escapedKnowledgeBase = addslashes($knowledgeBase);
    $escapedQAPairs = json_decode($qaPairs, true);

    $ownerEmail = $_POST['ownerEmail'] ?? '';
    $escapedOwnerEmail = addslashes($ownerEmail);

    // Format QA pairs for chatbot
    $formattedQAPairsChatbot = var_export($escapedQAPairs, true);
    $formattedQAPairsChatbot = str_replace("'", '"', $formattedQAPairsChatbot);

    // Format QA pairs for FAQ buttons
    $formattedQAPairsFaqs = htmlspecialchars(json_encode($escapedQAPairs, JSON_HEX_APOS | JSON_HEX_QUOT));

    $primaryColor = $_POST['primaryColor'] ?? '#007bff'; // Default primary color
    $secondaryColor = $_POST['secondaryColor'] ?? '#f4f4f9'; // Default secondary color
    $speakingAvatarFileName = '';
    $idleAvatarFileName = '';

    // Ensure the generated-plugins directory exists
    $uploadDir = '../generated-plugins/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle speaking avatar selection or upload
    if ($speakingAvatarUpload) {
        $speakingAvatarFileName = basename($speakingAvatarUpload['name']);
        $speakingAvatarUploadPath = sys_get_temp_dir() . '/' . $speakingAvatarFileName;

        if (!move_uploaded_file($speakingAvatarUpload['tmp_name'], $speakingAvatarUploadPath)) {
            echo json_encode(['message' => 'Error uploading custom speaking avatar.']);
            exit;
        }
    } elseif ($speakingAvatar) {
        $speakingAvatarFileName = basename($speakingAvatar);
        $frontendAvatarPath = '../frontend/images/' . $speakingAvatarFileName;
        $speakingAvatarUploadPath = sys_get_temp_dir() . '/' . $speakingAvatarFileName;

        if (!copy($frontendAvatarPath, $speakingAvatarUploadPath)) {
            echo json_encode(['message' => 'Error copying predefined speaking avatar.']);
            exit;
        }
    } else {
        echo json_encode(['message' => 'No speaking avatar selected or uploaded.']);
        exit;
    }

    // Handle idle avatar selection or upload
    if ($idleAvatarUpload) {
        $idleAvatarFileName = basename($idleAvatarUpload['name']);
        $idleAvatarUploadPath = sys_get_temp_dir() . '/' . $idleAvatarFileName;

        if (!move_uploaded_file($idleAvatarUpload['tmp_name'], $idleAvatarUploadPath)) {
            echo json_encode(['message' => 'Error uploading custom idle avatar.']);
            exit;
        }
    } elseif ($idleAvatar) {
        $idleAvatarFileName = basename($idleAvatar);
        $frontendAvatarPath = '../frontend/images/' . $idleAvatarFileName;
        $idleAvatarUploadPath = sys_get_temp_dir() . '/' . $idleAvatarFileName;

        if (!copy($frontendAvatarPath, $idleAvatarUploadPath)) {
            echo json_encode(['message' => 'Error copying predefined idle avatar.']);
            exit;
        }
    } else {
        echo json_encode(['message' => 'No idle avatar selected or uploaded.']);
        exit;
    }

    // Load the template and replace placeholders
    $pluginTemplate = file_get_contents('../backend/chatbot_template.php');

    $pluginTemplate = str_replace('{{KNOWLEDGE_BASE}}', $escapedKnowledgeBase, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA}}', $formattedQAPairsChatbot, $pluginTemplate);
    $pluginTemplate = str_replace('{{PREDEFINED_QA_FAQS}}', $formattedQAPairsFaqs, $pluginTemplate);
    $pluginTemplate = str_replace('{{SPEAKING_AVATAR_FILENAME}}', $speakingAvatarFileName, $pluginTemplate);
    $pluginTemplate = str_replace('{{IDLE_AVATAR_FILENAME}}', $idleAvatarFileName, $pluginTemplate);
    $pluginTemplate = str_replace('{{PRIMARY_COLOR}}', $primaryColor, $pluginTemplate);
    $pluginTemplate = str_replace('{{SECONDARY_COLOR}}', $secondaryColor, $pluginTemplate);
    $pluginTemplate = str_replace('{{OWNER_EMAIL}}', $escapedOwnerEmail, $pluginTemplate);

    // Retrieve the OpenAI API key from the environment variable
    $openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? '';
    $pluginTemplate = str_replace('{{OPENAI_API_KEY}}', $openaiApiKey, $pluginTemplate);

    // Save the generated plugin file
    $outputFilename = sys_get_temp_dir() . '/chatbot-' . uniqid() . '.php';
    if (file_put_contents($outputFilename, $pluginTemplate)) {
        // Create a zip file containing the generated plugin files
        $zip = new ZipArchive();
        $zipFilename = $uploadDir . 'chatbot.zip';

        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add the generated plugin file to the zip
            $zip->addFile($outputFilename, basename($outputFilename));

            // Add the speaking avatar image to the zip
            $zip->addFile($speakingAvatarUploadPath, basename($speakingAvatarUploadPath));

            // Add the idle avatar image to the zip
            $zip->addFile($idleAvatarUploadPath, basename($idleAvatarUploadPath));

            // Add the vendor folder to the zip from the backend directory
            $vendorDir = realpath('../backend/vendor/');
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

            // Add the service account key file to the zip from the backend directory
            $zip->addFile('../backend/gcp-text-to-speech-service-account-key.json', 'gcp-text-to-speech-service-account-key.json');

            // Close the zip file
            $zip->close();

            // Clean up temporary files
            unlink($outputFilename);
            unlink($speakingAvatarUploadPath);
            unlink($idleAvatarUploadPath);

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