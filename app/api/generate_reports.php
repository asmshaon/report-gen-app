<?php

// Include common utilities
require_once __DIR__ . '/common.php';

// Include required services
require_once __DIR__ . '/../services/ReportGeneratorService.php';

// Get form data
global $GENERATE_FIELDS;
$input = getFormInput($GENERATE_FIELDS);

// Validate required fields
$validation = validateRequired($input, array('file_name', 'stock_count'));
if (!$validation['valid']) {
    sendError('Missing required fields: ' . implode(', ', $validation['missing']));
}

// Initialize the generator (ensures directories exist)
$generator = new ReportGeneratorService();

// Sanitize file name
$sanitizedFileName = sanitizeFilename($input['file_name']);
$input['file_name'] = $sanitizedFileName;

// Handle image uploads with naming convention
$articleImage = isset($_FILES['article_image']) && $_FILES['article_image']['error'] !== UPLOAD_ERR_NO_FILE
    ? handleImageUpload($_FILES['article_image'], $generator->imagesPath, $sanitizedFileName, 'article')
    : null;
$pdfCoverImage = isset($_FILES['pdf_cover']) && $_FILES['pdf_cover']['error'] !== UPLOAD_ERR_NO_FILE
    ? handleImageUpload($_FILES['pdf_cover'], $generator->imagesPath, $sanitizedFileName, 'cover')
    : null;

// Use existing images if no new upload (and match the current report name)
if ($articleImage === null && !empty($input['article_image_existing'])) {
    $existingImage = $input['article_image_existing'];
    // Check if existing image matches current report name
    $expectedArticleImage = buildImageFilename($sanitizedFileName, 'article', pathinfo($existingImage, PATHINFO_EXTENSION));
    if ($existingImage === $expectedArticleImage) {
        $articleImage = $existingImage;
    } else {
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
