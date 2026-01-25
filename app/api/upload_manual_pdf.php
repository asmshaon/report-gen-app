<?php

date_default_timezone_set('UTC');

header('Content-Type: application/json');

// Enable error logging but don't display to user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../debug.log');
ini_set('display_errors', 0);

$reportsPath = __DIR__ . '/../../reports';

// Ensure directory exists
if (!is_dir($reportsPath)) {
    mkdir($reportsPath, 0755, true);
}

// Get POST data
$fileName = isset($_POST['file_name']) ? $_POST['file_name'] : '';

if (empty($fileName)) {
    echo json_encode(array('success' => false, 'message' => 'File name is required'));
    exit;
}

// Validate file name (alphanumeric, underscore, hyphen only)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $fileName)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid file name. Use only letters, numbers, underscores, and hyphens'));
    exit;
}

// Handle PDF upload
if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array('success' => false, 'message' => 'PDF file is required'));
    exit;
}

$file = $_FILES['pdf_file'];

// Validate file type
if ($file['type'] !== 'application/pdf') {
    echo json_encode(array('success' => false, 'message' => 'Only PDF files are allowed'));
    exit;
}

// Validate file size (max 10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(array('success' => false, 'message' => 'File size must be less than 10MB'));
    exit;
}

// Build destination path
$destination = $reportsPath . '/' . $fileName . '.pdf';

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(array(
        'success' => true,
        'message' => 'PDF uploaded successfully as: ' . $fileName . '.pdf',
        'file' => $fileName . '.pdf'
    ));
} else {
    echo json_encode(array('success' => false, 'message' => 'Failed to upload PDF'));
}
