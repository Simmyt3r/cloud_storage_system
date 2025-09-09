<?php
/**
 * File Viewer Page (Upgraded)
 *
 * Displays a file directly in the browser. For file types not supported
 * by browsers (like .docx), it uses an embedded viewer with a download fallback.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/File.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    die("Access Denied: You must be logged in to view this file.");
}

// --- 2. DATA VALIDATION & PERMISSIONS ---
$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
if ($file_id <= 0) {
    die("Invalid file specified.");
}

try {
    $file_model = new File($pdo);
    $file = $file_model->findById($file_id);

    if (!$file || $file['organization_id'] !== get_user_organization_id()) {
        die("File not found or you do not have permission to access it.");
    }

    if (!file_exists($file['file_path'])) {
        die("File does not exist on the server.");
    }

    // --- 3. LOGIC TO DISPLAY OR EMBED THE FILE ---
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_url = BASE_URL . '/controllers/file_controller.php?download=1&file_id=' . $file_id;
    
    // List of file types to be handled by the external viewer
    $embeddable_extensions = ['docx', 'doc', 'ppt', 'pptx', 'xls', 'xlsx'];

    if (in_array($file_extension, $embeddable_extensions)) {
        // For Office documents, provide an embedded viewer and a clear download link
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>" . htmlspecialchars($file['name']) . "</title>
            <style>
                body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: sans-serif; }
                .viewer-container { display: flex; flex-direction: column; height: 100%; }
                .top-bar { background-color: #f1f1f1; padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }
                .top-bar a { font-weight: bold; color: #333; text-decoration: none; }
                iframe { flex-grow: 1; border: none; }
            </style>
        </head>
        <body>
            <div class='viewer-container'>
                <div class='top-bar'>
                    If the preview does not load, you can 
                    <a href='" . htmlspecialchars($file_url) . "'>download the file directly</a>.
                    <br><small>(Note: Previews may not be available on local servers.)</small>
                </div>
                <iframe src='https://docs.google.com/gview?url=" . urlencode($file_url) . "&embedded=true'></iframe>
            </div>
        </body>
        </html>";
    } else {
        // For other file types (PDFs, images, etc.), display them directly
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($file['name']) . '"');
        header('Content-Length: ' . filesize($file['file_path']));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        ob_clean();
        flush();
        readfile($file['file_path']);
    }
    exit;

} catch (Exception $e) {
    die("An error occurred: " . $e->getMessage());
}
?>