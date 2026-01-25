<?php

date_default_timezone_set('UTC');

header('Content-Type: application/json');

// Enable error logging but don't display to user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../debug.log');
ini_set('display_errors', 0);

// Include required services
require_once __DIR__ . '/../services/ReportGeneratorService.php';

// Get form data from POST
$input = array();

// Get all POST fields
$fields = array(
    'file_name', 'report_title', 'author_name', 'stock_count',
    'data_source', 'report_intro_html', 'stock_block_html', 'disclaimer_html'
);

foreach ($fields as $field) {
    if (isset($_POST[$field])) {
        $input[$field] = $_POST[$field];
    }
}

// Validate required fields
if (empty($input['file_name'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Report File Name is required'
    ));
    exit;
}

if (empty($input['stock_count'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Number of Stocks is required'
    ));
    exit;
}

// Build config from form data
$config = array(
    'file_name' => $input['file_name'],
    'title' => isset($input['report_title']) ? $input['report_title'] : '',
    'author' => isset($input['author_name']) ? $input['author_name'] : '',
    'number_of_stocks' => intval($input['stock_count']),
    'data_source' => isset($input['data_source']) ? $input['data_source'] : 'data.csv',
    'images' => array(
        'article_image' => null,
        'pdf_cover_image' => null
    ),
    'content_templates' => array(
        'intro_html' => isset($input['report_intro_html']) ? $input['report_intro_html'] : '',
        'stock_block_html' => isset($input['stock_block_html']) ? $input['stock_block_html'] : '',
        'disclaimer_html' => isset($input['disclaimer_html']) ? $input['disclaimer_html'] : ''
    ),
    'manual_pdf_override' => null,
    'created_at' => date('c'),
    'updated_at' => date('c')
);

// Get report type (default: html)
$reportType = isset($_POST['report_type']) ? $_POST['report_type'] : 'html';

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
        $result = array(
            'success' => false,
            'message' => 'Unknown report type: ' . $reportType
        );
        break;
}

// Return result
echo json_encode($result);
