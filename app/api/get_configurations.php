<?php

date_default_timezone_set('UTC');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$filePath = __DIR__ . '/../../db/report_settings.json';

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(array('success' => false, 'message' => 'Settings file not found', 'data' => array()));
    exit;
}

$content = file_get_contents($filePath);
$data = json_decode($content, true);

if ($data === null) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'message' => 'Invalid JSON format', 'data' => array()));
    exit;
}

echo json_encode(array(
    'success' => true,
    'data' => isset($data['reports']) ? $data['reports'] : array()
));
