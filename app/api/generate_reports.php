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
 * Handle image upload with new naming convention
 * Filename format: {sanitized_report_name}_{type}.{ext}
 */
function handleImageUpload($fileInput, $reportName, $type)
{
    global $imagesDir;

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

    // Get extension
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    // Build filename: {report_name}_{type}.{ext}
    $filename = buildImageFilename($reportName, $type, $extension);
    $targetPath = $imagesDir . '/' . $filename;

    // Move uploaded file (will overwrite if exists)
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        logDebug("Uploaded image: $filename");
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

// Sanitize file name
$sanitizedFileName = sanitizeFilename($input['file_name']);
$input['file_name'] = $sanitizedFileName;

// Handle image uploads with new naming convention
$articleImage = handleImageUpload('article_image', $sanitizedFileName, 'article');
$pdfCoverImage = handleImageUpload('pdf_cover', $sanitizedFileName, 'cover');

// Use existing images if no new upload (and match the current report name)
if ($articleImage === null && !empty($input['article_image_existing'])) {
    $existingImage = $input['article_image_existing'];
    // Check if existing image matches current report name
    $expectedArticleImage = buildImageFilename($sanitizedFileName, 'article', pathinfo($existingImage, PATHINFO_EXTENSION));
    if ($existingImage === $expectedArticleImage) {
        $articleImage = $existingImage;
    } else {
        // Old image from different report name - don't use it
        $articleImage = null;
    }
}

if ($pdfCoverImage === null && !empty($input['pdf_cover_existing'])) {
    $existingImage = $input['pdf_cover_existing'];
    // Check if existing image matches current report name
    $expectedCoverImage = buildImageFilename($sanitizedFileName, 'cover', pathinfo($existingImage, PATHINFO_EXTENSION));
    if ($existingImage === $expectedCoverImage) {
        $pdfCoverImage = $existingImage;
    } else {
        // Old image from different report name - don't use it
        $pdfCoverImage = null;
    }
}

// Build config from form data
$config = array(
    'file_name' => $sanitizedFileName,
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
logDebug("Generated $reportType report for: " . $sanitizedFileName . " - Success: " . ($result['success'] ? 'yes' : 'no'));

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
