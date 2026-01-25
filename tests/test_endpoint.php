<?php

// Simulate POST input
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Set the input stream for php://input
$testInput = json_encode(['id' => 5]);
file_put_contents('php://memory', $testInput);

// Override php://input for testing
stream_wrapper_register('test', 'TestStream')
    or require_once(__DIR__ . '/../app/api/delete_configuration.php');

// Or test directly
$filePath = __DIR__ . '/../db/report_settings.json';
$id = 5;

$content = file_get_contents($filePath);
$data = json_decode($content, true);

echo "Testing delete_configuration.php logic:\n";
echo "File path: $filePath\n";
echo "ID to delete: $id\n";
echo "Reports before: " . count($data['reports']) . "\n";

$initialCount = count($data['reports']);
$data['reports'] = array_values(array_filter($data['reports'], function($report) use ($id) {
    return !isset($report['id']) || $report['id'] != $id;
}));

echo "Reports after: " . count($data['reports']) . "\n";
echo "Success: " . (count($data['reports']) < $initialCount ? "YES" : "NO") . "\n";

// Check file permissions
clearstatcache(true, $filePath);
$perms = substr(sprintf('%o', fileperms($filePath)), -4);
echo "File permissions: $perms\n";
echo "File writable: " . (is_writable($filePath) ? "YES" : "NO") . "\n";
