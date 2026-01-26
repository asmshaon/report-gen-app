<?php

// Include common utilities
require_once __DIR__ . '/common.php';

// Include required services
require_once __DIR__ . '/../services/ReportGeneratorService.php';

// Ensure reports and images directories exist
$reportsDir = __DIR__ . '/../../reports';
$imagesDir = __DIR__ . '/../../images';
$logsDir = __DIR__ . '/../../logs';

if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}
if (!is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

/**
 * Handle image upload
 */
function handleImageUpload($fileInput, $targetDir, $prefix = 'img')
{
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fileInput];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        logDebug("Upload error for $fileInput: " . $file['error']);
        return null;
    }

    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        logDebug("File too large for $fileInput: " . $file['size']);
        return null;
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        logDebug("Invalid file type for $fileInput: " . $mimeType);
        return null;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }

    logDebug("Failed to move uploaded file for $fileInput");
    return null;
}

// Get form data
global $GENERATE_FIELDS;
$input = getFormInput($GENERATE_FIELDS);

// Validate required fields
$validation = validateRequired($input, array('file_name', 'stock_count'));
if (!$validation['valid']) {
    sendError('Missing required fields: ' . implode(', ', $validation['missing']));
}

// Trim file_name to avoid whitespace issues
$input['file_name'] = trim($input['file_name']);

// Handle image uploads
logDebug('FILES: ' . json_encode(array_keys($_FILES)));
logDebug('article_image set: ' . (isset($_FILES['article_image']) ? 'YES' : 'NO'));
if (isset($_FILES['article_image'])) {
    logDebug('article_image error: ' . $_FILES['article_image']['error']);
}
$articleImage = handleImageUpload('article_image', $imagesDir, 'article');
$pdfCoverImage = handleImageUpload('pdf_cover', $imagesDir, 'cover');
logDebug('Upload results - article: ' . ($articleImage ?: 'null') . ', cover: ' . ($pdfCoverImage ?: 'null'));

// Use existing images if no new upload
if ($articleImage === null && !empty($input['article_image_existing'])) {
    $articleImage = $input['article_image_existing'];
}

if ($pdfCoverImage === null && !empty($input['pdf_cover_existing'])) {
    $pdfCoverImage = $input['pdf_cover_existing'];
}

// Build config from form data
$config = array(
    'file_name' => $input['file_name'],
    'title' => isset($input['report_title']) ? $input['report_title'] : 'Stock Report',
    'author' => isset($input['author_name']) ? $input['author_name'] : '',
    'number_of_stocks' => intval($input['stock_count']),
    'data_source' => isset($input['data_source']) ? $input['data_source'] : 'data.csv',
    'images' => array(
        'article_image' => $articleImage,
        'pdf_cover_image' => $pdfCoverImage
    ),
    'content_templates' => array(
        'intro_html' => isset($input['report_intro_html']) ? $input['report_intro_html'] : '',
        'stock_block_html' => isset($input['stock_block_html']) ? $input['stock_block_html'] : '',
        'disclaimer_html' => isset($input['disclaimer_html']) ? $input['disclaimer_html'] : ''
    ),
    'created_at' => date('c'),
    'updated_at' => date('c')
);

$reportType = isset($input['report_type']) ? $input['report_type'] : 'html';

// Initialize the generator
$generator = new ReportGeneratorService();

// Generate report based on type
$result = array();

switch ($reportType) {
    case 'html':
        $result = $generator->generateHtml($config);
        break;
    case 'pdf':
        $result = $generator->generatePdf($config);
        break;
    case 'flipbook':
        $result = $generator->generateFlipbook($config);
        break;
    case 'all':
        $result = $generator->generateAll($config);
        break;
    default:
        sendError('Unknown report type: ' . $reportType);
        break;
}

// Log generation result
logDebug("Generated $reportType report for: " . $input['file_name'] . " - Success: " . ($result['success'] ? 'yes' : 'no'));

// Send response
if ($result['success']) {
    // Build clean response data
    $responseData = array();

    if (isset($result['file'])) {
        // Single file generated
        $responseData['file'] = $result['file'];
        $responseData['type'] = $reportType;
    } elseif (isset($result['generated'])) {
        // Multiple files generated (report type: all)
        $responseData = $result['generated'];
        $responseData['failed'] = isset($result['failed']) ? $result['failed'] : array();
    }

    sendSuccess($result['message'], $responseData);
} else {
    sendError($result['message']);
}
