<?php

date_default_timezone_set('UTC');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../debug.log');
error_log('delete_configuration.php called - Method: ' . $_SERVER['REQUEST_METHOD']);

// Get JSON input
$inputJSON = file_get_contents('php://input');
error_log('Raw input: ' . $inputJSON);

$input = json_decode($inputJSON, true);

$id = isset($input['id']) ? $input['id'] : null;

if ($id != null) {
    error_log('Parsed ID: ' . $id);
} else {
    error_log('Parsed ID: null');
}

if ($id === null) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'ID is required'));
    exit;
}

$filePath = __DIR__ . '/../../db/report_settings.json';
error_log('File path: ' . $filePath);

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(array('success' => false, 'message' => 'Settings file not found'));
    exit;
}

// Read current data
$content = file_get_contents($filePath);
$data = json_decode($content, true);

if ($data === null || !isset($data['reports'])) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'message' => 'Invalid data format'));
    exit;
}

// Find and remove the report with matching ID
$initialCount = count($data['reports']);
$data['reports'] = array_values(array_filter($data['reports'], function($report) use ($id) {
    return !isset($report['id']) || $report['id'] != $id;
}));

if (count($data['reports']) < $initialCount) {
    // Write updated data back to file
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $result = file_put_contents($filePath, $json);
    error_log('Write result: ' . ($result !== false ? 'success' : 'failed'));

    if ($result !== false) {
        echo json_encode(array('success' => true, 'message' => 'Configuration deleted'));
    } else {
        http_response_code(500);
        echo json_encode(array('success' => false, 'message' => 'Failed to write file'));
    }
} else {
    http_response_code(404);
    echo json_encode(array('success' => false, 'message' => 'Configuration not found'));
}
