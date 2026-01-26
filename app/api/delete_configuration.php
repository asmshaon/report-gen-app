<?php

// Include common utilities
require_once __DIR__ . '/common.php';

// Include the service
require_once __DIR__ . '/../services/ReportSettingService.php';

// Get JSON input
$input = getJsonInput();
$id = isset($input['id']) ? $input['id'] : null;

// Initialize the service
$service = new ReportSettingService();

// Delete configuration
$result = $service->deleteConfiguration($id);

// Send response with appropriate status code
if ($result['success']) {
    sendSuccess($result['message']);
} else {
    // Determine status code based on error message
    if (strpos($result['message'], 'not found') !== false) {
        sendError($result['message'], 404);
    } else {
        sendError($result['message'], 400);
    }
}
