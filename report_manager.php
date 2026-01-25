<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Manager | Stock Report Service</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body>

<div class="container">
    <?php include __DIR__ . '/partials/filter.php'; ?>
    <?php include __DIR__ . '/partials/form-manager.php'; ?>
    <?php include __DIR__ . '/partials/manual-pdf-uploader.php'; ?>
    <?php include __DIR__ . '/partials/config-list.php'; ?>
</div>

<footer class="text-center mt-5 mb-5 text-muted" x-data="{ systemDate: (() => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0') + ' ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0'); })() }">
    <hr>
    <small x-text="'System Date: ' + systemDate + ' | PHP 5.5 Compatibility Mode'"></small>
</footer>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="public/js/api.js"></script>
</body>
</html>
