<?php

// Include common utilities
require_once __DIR__ . '/common.php';

// Include required services
require_once __DIR__ . '/../services/ReportSettingService.php';
require_once __DIR__ . '/../services/ReportGeneratorService.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['ids'])) {
    sendError('Missing required field: ids (array of configuration IDs or "all")');
}

// Get report type (default to 'all')
$reportType = isset($input['report_type']) ? $input['report_type'] : 'all';

// Initialize services
$settingService = new ReportSettingService();
$generator = new ReportGeneratorService();

$ids = $input['ids'];

// Handle 'all' - get all configuration IDs
if ($ids === 'all' || $ids === ['all']) {
    $configResult = $settingService->getConfigurations();
    if (!$configResult['success']) {
        sendError('Failed to retrieve configurations');
    }
    $configs = $configResult['data'];
    $ids = array();
    foreach ($configs as $config) {
        if (isset($config['id'])) {
            $ids[] = $config['id'];
        }
    }
    if (empty($ids)) {
        sendError('No configurations found');
    }
} else {
    // Ensure ids is an array
    if (!is_array($ids)) {
        $ids = array($ids);
    }
    if (empty($ids)) {
        sendError('No configuration IDs provided');
    }
}

// Results tracking
$results = array(
    'generated' => array('html' => array(), 'pdf' => array(), 'flipbook' => array()),
    'failed' => array()
);

// Process each configuration
foreach ($ids as $id) {
    // Get configuration by ID
    $configResult = $settingService->getConfigurationById($id);

    if (!$configResult['success']) {
        $results['failed'][] = 'Config ID ' . $id . ' (not found)';
        continue;
    }

    $configData = $configResult['data'];

    // Build generator config from stored configuration
    $config = array(
        'file_name' => sanitizeFilename($configData['file_name']),
        'title' => isset($configData['title']) ? $configData['title'] : 'Stock Report',
        'author' => isset($configData['author']) ? $configData['author'] : '',
        'number_of_stocks' => isset($configData['number_of_stocks']) ? intval($configData['number_of_stocks']) : 6,
        'data_source' => isset($configData['data_source']) ? $configData['data_source'] : 'data.csv',
        'images' => isset($configData['images']) ? $configData['images'] : array(),
        'content_templates' => isset($configData['content_templates']) ? $configData['content_templates'] : array()
    );

    // Generate reports based on selected type
    switch ($reportType) {
        case 'html':
            $htmlResult = $generator->generateHtml($config);
            if ($htmlResult['success']) {
                $results['generated']['html'][] = $config['title'];
            } else {
                $results['failed'][] = $config['title'] . ' (HTML: ' . $htmlResult['message'] . ')';
            }
            break;

        case 'pdf':
            $pdfResult = $generator->generatePdf($config);
            if ($pdfResult['success']) {
                $results['generated']['pdf'][] = $config['title'];
            } else {
                $results['failed'][] = $config['title'] . ' (PDF: ' . $pdfResult['message'] . ')';
            }
            break;

        case 'flipbook':
            $flipbookResult = $generator->generateFlipbook($config);
            if ($flipbookResult['success']) {
                $results['generated']['flipbook'][] = $config['title'];
            } else {
                $results['failed'][] = $config['title'] . ' (Flipbook: ' . $flipbookResult['message'] . ')';
            }
            break;

        case 'all':
        default:
            // Generate all report types
            $htmlResult = $generator->generateHtml($config);
            if ($htmlResult['success']) {
                $results['generated']['html'][] = $config['title'];
            } else {
                $results['failed'][] = $config['title'] . ' (HTML: ' . $htmlResult['message'] . ')';
            }

            $pdfResult = $generator->generatePdf($config);
            if ($pdfResult['success']) {
                $results['generated']['pdf'][] = $config['title'];
            } else {
                $results['failed'][] = $config['title'] . ' (PDF: ' . $pdfResult['message'] . ')';
            }

            $flipbookResult = $generator->generateFlipbook($config);
            if ($flipbookResult['success']) {
                $results['generated']['flipbook'][] = $config['title'];
            } else {
                $results['failed'][] = $config['title'] . ' (Flipbook: ' . $flipbookResult['message'] . ')';
            }
            break;
    }
}

// Build success message
$message = 'Report generation completed.';

if (count($results['failed']) > 0) {
    $message .= ' Some reports failed to generate.';
}

sendSuccess($message, $results);
