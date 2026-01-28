<?php
/**
 * Directory Permission Checker
 * Helps debug why PDF generation and image uploads may not be working
 */

// Prevent directory listing attacks
$baseDir = realpath(__DIR__ . '/..');
$allowedPaths = [$baseDir];

function checkDirectory($path, $indent = 0) {
    $perms = fileperms($path);
    $owner = fileowner($path);
    $group = filegroup($path);

    // Format permissions
    $info = sprintf(
        "%s %-50s [Permissions: %s] [Owner: %s] [Group: %s]",
        str_repeat('  ', $indent),
        basename($path) . '/',
        substr(sprintf('%o', $perms), -4),
        function_exists('posix_getpwuid') ? posix_getpwuid($owner)['name'] : $owner,
        function_exists('posix_getgrgid') ? posix_getgrgid($group)['name'] : $group
    );

    // Check writability
    $isWritable = is_writable($path);
    $isReadable = is_readable($path);

    if ($isWritable && $isReadable) {
        echo "<span style='color: green;'>$info ✓ WRITABLE</span><br>";
    } elseif ($isReadable) {
        echo "<span style='color: orange;'>$info ⚠ READ-ONLY</span><br>";
    } else {
        echo "<span style='color: red;'>$info ✗ NO ACCESS</span><br>";
    }

    return $isWritable;
}

function scanDirectory($path, $indent = 0, $maxDepth = 3) {
    if ($indent >= $maxDepth) return;

    $items = @scandir($path);
    if ($items === false) {
        echo "<span style='color: red;'>" . str_repeat('  ', $indent) . "Cannot read directory: $path</span><br>";
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $path . '/' . $item;

        if (is_dir($fullPath)) {
            $isWritable = checkDirectory($fullPath, $indent);
            scanDirectory($fullPath, $indent + 1, $maxDepth);
        }
    }
}

function checkCriticalPaths() {
    global $baseDir;
    echo "<h2 style='color: #333;'>Critical Paths Check</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th style='background: #f0f0f0;'>Path</th><th style='background: #f0f0f0;'>Exists</th><th style='background: #f0f0f0;'>Readable</th><th style='background: #f0f0f0;'>Writable</th><th style='background: #f0f0f0;'>Permissions</th></tr>";

    $criticalPaths = [
        'Base Directory' => $baseDir,
        'reports/' => $baseDir . '/reports',
        'images/' => $baseDir . '/images',
    ];

    foreach ($criticalPaths as $name => $path) {
        $exists = file_exists($path);
        $readable = $exists && is_readable($path);
        $writable = $exists && is_writable($path);
        $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';

        $color = $writable ? '#d4edda' : ($readable ? '#fff3cd' : '#f8d7da');

        echo "<tr style='background: $color;'>";
        echo "<td><strong>$name</strong><br><small>$path</small></td>";
        echo "<td style='text-align: center;'>" . ($exists ? '✓' : '✗') . "</td>";
        echo "<td style='text-align: center;'>" . ($readable ? '✓' : '✗') . "</td>";
        echo "<td style='text-align: center;'>" . ($writable ? '✓' : '✗') . "</td>";
        echo "<td>$perms</td>";
        echo "</tr>";
    }

    echo "</table>";
}

function checkPHPSettings() {
    echo "<h2 style='color: #333; margin-top: 30px;'>PHP Settings</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th style='background: #f0f0f0;'>Setting</th><th style='background: #f0f0f0;'>Value</th></tr>";

    $settings = [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
        'safe_mode' => ini_get('safe_mode') ? 'Enabled' : 'Disabled',
        'disable_functions' => ini_get('disable_functions') ?: 'None',
        'user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user(),
        'group' => function_exists('posix_getgrgid') ? posix_getgrgid(posix_getegid())['name'] : 'N/A',
    ];

    foreach ($settings as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }

    echo "</table>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory Permission Checker</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { margin-top: 10px; }
        th, td { padding: 8px 12px; text-align: left; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Directory Permission Checker</h1>
        <p><strong>Base Directory:</strong> <?php echo htmlspecialchars($baseDir); ?></p>
        <p><strong>Current User:</strong> <?php echo function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user(); ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>

        <?php checkCriticalPaths(); ?>
        <?php checkPHPSettings(); ?>

        <h2 style='color: #333; margin-top: 30px;'>Full Directory Structure (<?php echo $baseDir; ?>)</h2>
        <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;">
        <?php
            checkDirectory($baseDir);
            scanDirectory($baseDir, 0, 3);
        ?>
        </pre>

        <?php
        // Test write capability
        $testFile = $baseDir . '/reports/.write_test_' . time();
        $testDir = $baseDir . '/reports';
        if (is_dir($testDir)) {
            if (@file_put_contents($testFile, 'test')) {
                @unlink($testFile);
                echo "<div class='success'>✓ <strong>reports/</strong> directory is WRITABLE - PDF generation should work!</div>";
            } else {
                echo "<div class='error'>✗ <strong>reports/</strong> directory is NOT WRITABLE - This is why PDF generation fails!</div>";
            }
        } else {
            echo "<div class='error'>✗ <strong>reports/</strong> directory does NOT exist!</div>";
        }

        $testFile = $baseDir . '/images/.write_test_' . time();
        $testDir = $baseDir . '/images';
        if (is_dir($testDir)) {
            if (@file_put_contents($testFile, 'test')) {
                @unlink($testFile);
                echo "<div class='success'>✓ <strong>images/</strong> directory is WRITABLE - image upload should work!</div>";
            } else {
                echo "<div class='error'>✗ <strong>images/</strong> directory is NOT WRITABLE - This is why image upload fails!</div>";
            }
        } else {
            echo "<div class='error'>✗ <strong>images/</strong> directory does NOT exist!</div>";
        }
        ?>
    </div>
</body>
</html>
