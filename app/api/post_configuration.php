<?php

date_default_timezone_set('UTC');

header('Content-Type: application/json');

// Enable error logging but don't display to user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../debug.log');
ini_set('display_errors', 0);

// Handle both JSON and multipart form data
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    // JSON input
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    // Form data input
    $input = array();

    // Get regular POST fields
    $fields = array('id', 'file_name', 'report_title', 'author_name', 'stock_count',
                    'data_source', 'report_intro_html', 'stock_block_html', 'disclaimer_html');

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $input[$field] = $_POST[$field];
        }
    }
}

$filePath = __DIR__ . '/../../db/report_settings.json';
$imagesPath = __DIR__ . '/../../images';
$reportsPath = __DIR__ . '/../../reports';

// Ensure directories exist
if (!is_dir($imagesPath)) {
    mkdir($imagesPath, 0755, true);
}
if (!is_dir($reportsPath)) {
    mkdir($reportsPath, 0755, true);
}

// Read current data
if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
} else {
    $data = array('last_id' => 0, 'reports' => array());
}

if ($data === null) {
    $data = array('last_id' => 0, 'reports' => array());
}

// Determine if this is an update or create
$isUpdate = isset($input['id']) && !empty($input['id']);

// Handle article image upload
$articleImage = null;
if (isset($_FILES['article_image']) && $_FILES['article_image']['error'] == 0) {
    $ext = pathinfo($_FILES['article_image']['name'], PATHINFO_EXTENSION);
    $filename = 'article_' . $input['file_name'] . '.' . $ext;
    if (move_uploaded_file($_FILES['article_image']['tmp_name'], $imagesPath . '/' . $filename)) {
        $articleImage = $filename;
    }
}

// Handle PDF cover image upload
$coverImage = null;
if (isset($_FILES['pdf_cover']) && $_FILES['pdf_cover']['error'] == 0) {
    $ext = pathinfo($_FILES['pdf_cover']['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . $input['file_name'] . '.' . $ext;
    if (move_uploaded_file($_FILES['pdf_cover']['tmp_name'], $imagesPath . '/' . $filename)) {
        $coverImage = $filename;
    }
}

// Handle manual PDF upload
$manualPdf = null;
if (isset($_FILES['manual_pdf']) && $_FILES['manual_pdf']['error'] == 0) {
    $filename = $input['file_name'] . '.pdf';
    if (move_uploaded_file($_FILES['manual_pdf']['tmp_name'], $reportsPath . '/' . $filename)) {
        $manualPdf = $filename;
    }
}

// Build report object
$report = array(
    'id' => $isUpdate ? $input['id'] : ($data['last_id'] + 1),
    'file_name' => isset($input['file_name']) ? $input['file_name'] : '',
    'title' => isset($input['report_title']) ? $input['report_title'] : '',
    'author' => isset($input['author_name']) ? $input['author_name'] : '',
    'number_of_stocks' => isset($input['stock_count']) ? intval($input['stock_count']) : 6,
    'data_source' => isset($input['data_source']) ? $input['data_source'] : 'data.csv',
    'images' => array(
        'article_image' => $articleImage,
        'pdf_cover_image' => $coverImage
    ),
    'content_templates' => array(
        'intro_html' => isset($input['report_intro_html']) ? $input['report_intro_html'] : '',
        'stock_block_html' => isset($input['stock_block_html']) ? $input['stock_block_html'] : '',
        'disclaimer_html' => isset($input['disclaimer_html']) ? $input['disclaimer_html'] : ''
    ),
    'manual_pdf_override' => $manualPdf,
    'created_at' => date('c'),
    'updated_at' => date('c')
);

if ($isUpdate) {
    // Update existing report - preserve images if not updated
    $updated = false;
    foreach ($data['reports'] as $index => $r) {
        if ($r['id'] == $input['id']) {
            // Preserve existing images if new ones not uploaded
            if ($report['images']['article_image'] === null && isset($r['images']['article_image'])) {
                $report['images']['article_image'] = $r['images']['article_image'];
            }
            if ($report['images']['pdf_cover_image'] === null && isset($r['images']['pdf_cover_image'])) {
                $report['images']['pdf_cover_image'] = $r['images']['pdf_cover_image'];
            }
            if ($report['manual_pdf_override'] === null && isset($r['manual_pdf_override'])) {
                $report['manual_pdf_override'] = $r['manual_pdf_override'];
            }
            $report['created_at'] = $r['created_at'];
            $data['reports'][$index] = $report;
            $updated = true;
            break;
        }
    }
    if (!$updated) {
        echo json_encode(array('success' => false, 'message' => 'Report not found'));
        exit;
    }
} else {
    // Create new report
    $data['last_id'] = $report['id'];
    $data['reports'][] = $report;
}

// Write to file
$json = json_encode($data, JSON_PRETTY_PRINT);
if (file_put_contents($filePath, $json) !== false) {
    echo json_encode(array(
        'success' => true,
        'message' => $isUpdate ? 'Configuration updated' : 'Configuration created',
        'data' => $report
    ));
} else {
    echo json_encode(array('success' => false, 'message' => 'Failed to save configuration'));
}
