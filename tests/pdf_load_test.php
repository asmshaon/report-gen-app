<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>mPDF Test</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

// Check required extensions
$required = array('mbstring', 'gd');
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>" . $ext . ": " . ($loaded ? '<span style="color:green">LOADED</span>' : '<span style="color:red">MISSING</span>') . "</p>";
}

// Try to load mPDF
require_once __DIR__ . '/../lib/mpdf/mpdf.php';

try {
    echo "<p>mPDF Loaded: " . (class_exists('mPDF') ? 'YES' : 'NO') . "</p>";

    // Try to create PDF
    echo "<p>Creating mPDF instance...</p>";
    $mpdf = new mPDF('utf-8', 'A4', 0, '', 15, 15, 16, 16, 9, 9, 'P');
    echo "<p style='color:green'>mPDF instance created successfully!</p>";

    // Try to write HTML
    echo "<p>Writing HTML...</p>";
    $mpdf->WriteHTML('<h1>Test PDF</h1><p>This is a test PDF generated with mPDF.</p>');
    echo "<p style='color:green'>HTML written successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p style='color:red'>FATAL ERROR: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
