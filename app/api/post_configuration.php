<?php

// Include common utilities
require_once __DIR__ . '/common.php';

// Include the service
require_once __DIR__ . '/../services/ReportSettingService.php';

// Handle both JSON and multipart form data
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    // JSON input
    $input = getJsonInput();
    $files = array();
} else {
    // Form data input
    global $CONFIG_FIELDS;
    $input = getFormInput($CONFIG_FIELDS);
    $files = $_FILES;
}

// Initialize the service
$service = new ReportSettingService();

// Save configuration
$result = $service->saveConfiguration($input, $files);

// Send response
if ($result['success']) {
    sendSuccess($result['message'], $result['data']);
} else {
    sendError($result['message']);
}
