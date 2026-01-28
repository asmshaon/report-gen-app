<?php
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require_once __DIR__ . '/../lib/mpdf/mpdf.php';

    $mpdf = new mPDF('utf-8', 'A4', 0, '', 15, 15, 16, 16, 9, 9, 'P');
    $mpdf->WriteHTML('<h1>Test PDF</h1><p>mPDF is working!</p>');
    $mpdf->Output();



