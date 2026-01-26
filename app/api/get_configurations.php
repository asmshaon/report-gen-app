<?php

// Include common utilities
require_once __DIR__ . '/common.php';

// Include the service
require_once __DIR__ . '/../services/ReportSettingService.php';

// Initialize the service
$service = new ReportSettingService();

// Get all configurations
$result = $service->getConfigurations();

// Send response
if ($result['success']) {
    sendSuccess(null, $result['data']);
} else {
    sendError($result['message'], 500, $result['data']);
}
