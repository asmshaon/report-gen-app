<?php

/**
 * Common utilities for API endpoints
 * Provide shared functionality for all API files
 */

// Set timezone
date_default_timezone_set('UTC');

// Set common headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error logging but don't display to the user
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('error_log', __DIR__ . '/../../logs/debug.log');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

global $CONFIG_FIELDS;
$CONFIG_FIELDS = array(
    'id', 'file_name', 'report_title', 'author_name', 'stock_count',
    'data_source', 'report_intro_html', 'stock_block_html', 'disclaimer_html'
);

global $GENERATE_FIELDS;
$GENERATE_FIELDS = array(
    'id', 'file_name', 'report_title', 'author_name', 'stock_count',
    'data_source', 'report_intro_html', 'stock_block_html', 'disclaimer_html',
    'article_image_existing', 'pdf_cover_existing', 'report_type'
);


/**
 * Send a JSON response
 *
 * @param bool $success Whether the request was successful
 * @param string|null $message Optional message
 * @param mixed $data Optional data to include
 * @param int $statusCode HTTP status code (default 200)
 */
function sendResponse($success, $message = null, $data = null, $statusCode = 200)
{
    $response = array('success' => $success);

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        $response['data'] = $data;
    }

    http_response_code($statusCode);
    echo json_encode($response);
    exit;
}

/**
 * Send a success response
 *
 * @param string|null $message Optional message
 * @param mixed $data Optional data to include
 */
function sendSuccess($message = null, $data = null)
{
    sendResponse(true, $message, $data, 200);
}

/**
 * Send an error response
 *
 * @param string $message Error message
 * @param int $statusCode HTTP status code (default 400)
 * @param mixed $data Optional data to include
 */
function sendError($message, $statusCode = 400, $data = null)
{
    sendResponse(false, $message, $data, $statusCode);
}

/**
 * Get JSON input from request body
 *
 * @return array Decoded JSON data
 */
function getJsonInput()
{
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (json_last_error() !== JSON_ERROR_NONE && json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON input', 400);
    }

    return $input ?: array();
}

/**
 * Get form input from POST data
 *
 * @param array $fields Array of field names to extract
 * @return array Form data
 */
function getFormInput($fields = array())
{
    $input = array();

    if (empty($fields)) {
        // Get all POST fields
        $input = $_POST;
    } else {
        // Get specific fields from $_POST
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $input[$field] = $_POST[$field];
            }
        }

        // Also check for empty string values (isset returns true for empty strings)
        foreach ($fields as $field) {
            if (!isset($input[$field]) && array_key_exists($field, $_POST)) {
                $input[$field] = $_POST[$field];
            }
        }
    }

    // Debug logging
    if (!empty($_POST)) {
        logDebug('POST data received: ' . json_encode(array_keys($_POST)));
    } else {
        logDebug('WARNING: $_POST is empty in getFormInput');
    }

    return $input;
}

/**
 * Validate required fields
 *
 * @param array $input Input data
 * @param array $requiredFields Array of required field names
 * @return array Array with 'valid' boolean and 'missing' array of missing fields
 */
function validateRequired($input, $requiredFields)
{
    $missing = array();

    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            $missing[] = $field;
        }
    }

    return array(
        'valid' => empty($missing),
        'missing' => $missing
    );
}

/**
 * Log a debug message
 *
 * @param string $message Message to log
 */
function logDebug($message)
{
    $logFile = __DIR__ . '/../../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, $timestamp . ' ' . $message . "\n", FILE_APPEND);
}

/**
 * Sanitize filename - lowercase, remove special chars, only underscores allowed
 * Converts "My File-Name!" to "my_file_name"
 *
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeFilename($filename)
{
    // Convert to lowercase
    $filename = strtolower($filename);

    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);

    // Keep only letters, numbers, and underscores
    $filename = preg_replace('/[^a-z0-9]+/', '_', $filename);

    // Remove leading/trailing underscores
    $filename = trim($filename, '_');

    // Don't allow empty filenames
    if (empty($filename)) {
        $filename = 'unnamed_' . time();
    }

    return $filename;
}

/**
 * Build image filename from the report name and type
 * Example: "My Report" + "article" → "my_report_article.jpg"
 *
 * @param string $reportName Report file name
 * @param string $type Image type (article or cover)
 * @param string $extension File extension
 * @return string Generated filename
 */
function buildImageFilename($reportName, $type, $extension)
{
    $sanitized = sanitizeFilename($reportName);

    return $sanitized . '_' . $type . '.' . $extension;
}

/**
 * Handle image upload with naming convention
 * Filename format: {sanitized_report_name}_{type}.{ext}
 *
 * @param array $file Uploaded file data from $_FILES
 * @param string $targetDir Target directory path
 * @param string $reportName Report file name (will be sanitized)
 * @param string $type Image type (article or cover)
 * @return string|null Generated filename or null on failure
 */
function handleImageUpload($file, $targetDir, $reportName, $type)
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return null;
    }

    // Validate file type
    $allowedTypes = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }

    // Get extension
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    // Build filename: {report_name}_{type}.{ext}
    $filename = buildImageFilename($reportName, $type, $extension);
    $targetPath = $targetDir . '/' . $filename;

    // Move the uploaded file (will overwrite if exists)
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }

    return null;
}

