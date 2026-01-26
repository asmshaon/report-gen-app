<?php

// Include common utilities
require_once __DIR__ . '/common.php';

$reportsPath = __DIR__ . '/../../reports';

// Ensure directory exists
if (!is_dir($reportsPath)) {
    mkdir($reportsPath, 0755, true);
}

// Get POST data
$fileName = isset($_POST['file_name']) ? $_POST['file_name'] : '';

// Validate required field
if (empty($fileName)) {
    sendError('File name is required');
}

// Validate file name (alphanumeric, underscore, hyphen only)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $fileName)) {
    sendError('Invalid file name. Use only letters, numbers, underscores, and hyphens');
}

// Handle PDF upload
if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    sendError('PDF file is required');
}

$file = $_FILES['pdf_file'];

// Validate file type
if ($file['type'] !== 'application/pdf') {
    sendError('Only PDF files are allowed');
}

// Validate file size (max 10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    sendError('File size must be less than 10MB');
}

// Build destination path
$destination = $reportsPath . '/' . $fileName . '.pdf';

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $destination)) {
    sendSuccess('PDF uploaded successfully as: ' . $fileName . '.pdf', array('file' => $fileName . '.pdf'));
} else {
    sendError('Failed to upload PDF');
}
