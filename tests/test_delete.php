<?php

$id = 5;
$filePath = __DIR__ . '/../db/report_settings.json';
$content = file_get_contents($filePath);
$data = json_decode($content, true);

echo "Before: " . count($data['reports']) . " reports\n";
$initialCount = count($data['reports']);
$data['reports'] = array_values(array_filter($data['reports'], function($report) use ($id) {
    return !isset($report['id']) || $report['id'] != $id;
}));
echo "After: " . count($data['reports']) . " reports\n";
echo "Deleted: " . ($initialCount > count($data['reports']) ? "YES" : "NO") . "\n";
