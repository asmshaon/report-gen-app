<?php

/**
 * ReportGeneratorService - Service for generating HTML, PDF, and Flipbook reports
 */

date_default_timezone_set('UTC');

class ReportGeneratorService
{
    /**
     * @var string Path to data directory
     */
    private $dataPath;

    /**
     * @var string Path to reports directory
     */
    private $reportsPath;

    /**
     * @var string Path to images directory
     */
    private $imagesPath;

    /**
     * @var string Path to logs directory
     */
    private $logsPath;

    /**
     * @var array CSV data cache
     */
    private $csvData = array();

    /**
     * ReportGeneratorService constructor
     */
    public function __construct()
    {
        $this->dataPath = __DIR__ . '/../../db';
        $this->reportsPath = __DIR__ . '/../../reports';
        $this->imagesPath = __DIR__ . '/../../images';
        $this->logsPath = __DIR__ . '/../../logs';

        // Ensure reports and logs directories exist
        if (!is_dir($this->reportsPath)) {
            mkdir($this->reportsPath, 0755, true);
        }
        if (!is_dir($this->logsPath)) {
            mkdir($this->logsPath, 0755, true);
        }
    }

    /**
     * Generate HTML report from configuration
     *
     * @param array $config Report configuration
     * @return array Result with success status and message
     */
    public function generateHtml(array $config)
    {
        // Load stock data
        $stocks = $this->loadStockData($config);
        if (!$stocks['success']) {
            return $stocks;
        }

        // Start building HTML - use article-body wrapper
        $html = '<div id="article-body">';

        // Add main article wrapper
        $html .= '<div class="mainarticle" style="width: 100%"><div><title>' . $config['title'] . '</title>';

        // Add article image if exists
        if (!empty($config['images']['article_image'])) {
            $imagePath = '../images/' . $config['images']['article_image'];
            $html .= '<p><img alt="" src="' . $imagePath . '" style="float: left; width: 200px; height: 200px; margin: 14px;"></p>';
        }

        // Add intro content from the form template
        if (!empty($config['content_templates']['intro_html'])) {
            $introHtml = $this->replaceShortcodes(
                $config['content_templates']['intro_html'],
                null,
                $config
            );
            $html .= $introHtml;
        }

        $html .= '</div><br>';

        // Add stock blocks using form template
        $stockBlockTemplate = !empty($config['content_templates']['stock_block_html'])
            ? $config['content_templates']['stock_block_html']
            : $this->getDefaultStockBlockTemplate();

        foreach ($stocks['data'] as $stock) {
            $stockHtml = $this->replaceShortcodes(
                $stockBlockTemplate,
                $stock,
                $config
            );
            $html .= $stockHtml . "<br>";
        }

        // Add disclaimer from form template
        if (!empty($config['content_templates']['disclaimer_html'])) {
            $disclaimerHtml = $config['content_templates']['disclaimer_html'];
            $html .= $disclaimerHtml;
        }

        // Close article-body
        $html .= '</div>';

        // Write to file
        $outputFile = $this->getReportPath($config['file_name'] . '.html');
        if (file_put_contents($outputFile, $html) !== false) {
            return array(
                'success' => true,
                'message' => 'HTML report generated successfully',
                'file' => $config['file_name'] . '.html',
                'path' => $outputFile
            );
        }

        return array(
            'success' => false,
            'message' => 'Failed to write HTML file'
        );
    }

    /**
     * Generate PDF report from configuration
     *
     * @param array $config Report configuration
     * @return array Result with success status and message
     */
    public function generatePdf(array $config)
    {
        // Load stock data
        $stocks = $this->loadStockData($config);
        if (!$stocks['success']) {
            return $stocks;
        }

        // Build a complete HTML document for PDF
        $html = $this->buildPdfHtml($config, $stocks['data']);

        // Output PDF file path
        $pdfFile = $this->getReportPath($config['file_name'] . '.pdf');

        // Generate PDF using mPDF
        $result = $this->convertHtmlToPdfWithMpdf($html, $pdfFile);

        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'PDF report generated successfully',
                'file' => $config['file_name'] . '.pdf',
                'path' => $pdfFile
            );
        }

        return $result;
    }

    /**
     * Generate Flipbook report from configuration
     *
     * @param array $config Report configuration
     * @return array Result with success status and message
     */
    public function generateFlipbook(array $config)
    {
        // Load stock data
        $stocks = $this->loadStockData($config);
        if (!$stocks['success']) {
            return $stocks;
        }

        // Build flipbook HTML
        $html = $this->buildFlipbookHtml($config, $stocks['data']);

        // Write to file
        $flipbookFile = $this->getReportPath($config['file_name'] . '_flipbook.html');
        if (file_put_contents($flipbookFile, $html) !== false) {
            return array(
                'success' => true,
                'message' => 'Flipbook report generated successfully',
                'file' => $config['file_name'] . '_flipbook.html',
                'path' => $flipbookFile
            );
        }

        return array(
            'success' => false,
            'message' => 'Failed to generate flipbook report'
        );
    }

    /**
     * Generate all report types for a configuration
     *
     * @param array $config Report configuration
     * @return array Result with success status and generated files
     */
    public function generateAll(array $config)
    {
        $results = array(
            'success' => true,
            'generated' => array(),
            'failed' => array()
        );

        // Generate HTML
        $htmlResult = $this->generateHtml($config);
        if ($htmlResult['success']) {
            $results['generated']['html'] = $htmlResult['file'];
        } else {
            $results['success'] = false;
            $results['failed']['html'] = $htmlResult['message'];
        }

        // Generate PDF
        $pdfResult = $this->generatePdf($config);
        if ($pdfResult['success']) {
            $results['generated']['pdf'] = $pdfResult['file'];
        } else {
            $results['failed']['pdf'] = $pdfResult['message'];
        }

        // Generate Flipbook
        $flipbookResult = $this->generateFlipbook($config);
        if ($flipbookResult['success']) {
            $results['generated']['flipbook'] = $flipbookResult['file'];
        } else {
            $results['failed']['flipbook'] = $flipbookResult['message'];
        }

        return $results;
    }

    /**
     * Load stock data from CSV file
     *
     * @param array $config Report configuration
     * @return array Result with success status and data
     */
    private function loadStockData(array $config)
    {
        $csvFile = $this->dataPath . '/' . $config['data_source'];
        if (!file_exists($csvFile)) {
            return array(
                'success' => false,
                'message' => 'Data source file not found: ' . $config['data_source']
            );
        }

        $stocks = $this->parseCsv($csvFile, $config['number_of_stocks']);
        return array(
            'success' => true,
            'data' => $stocks
        );
    }

    /**
     * Get full path for a report file
     *
     * @param string $filename Report filename
     * @return string Full path to report file
     */
    private function getReportPath($filename)
    {
        return $this->reportsPath . '/' . $filename;
    }

    /**
     * Parse CSV file and return stock data
     *
     * @param string $csvFile Path to CSV file
     * @param int $limit Maximum number of stocks to return
     * @return array Stock data
     */
    private function parseCsv($csvFile, $limit = null)
    {
        if (isset($this->csvData[$csvFile])) {
            $data = $this->csvData[$csvFile];
        } else {
            $handle = fopen($csvFile, 'r');
            if ($handle === false) {
                return array();
            }

            // Get header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                return array();
            }

            // Clean headers (remove BOM if present and trim spaces)
            $headers = array_map(function($h) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                return trim($h);
            }, $headers);

            $data = array();
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) == count($headers)) {
                    $stock = array_combine($headers, $row);
                    $data[] = $stock;
                }
            }
            fclose($handle);

            $this->csvData[$csvFile] = $data;
        }

        if ($limit !== null) {
            return array_slice($data, 0, $limit);
        }

        return $data;
    }

    /**
     * Replace shortcodes in template with actual values
     *
     * @param string $template Template with shortcodes
     * @param array|null $stock Stock data (null for general templates)
     * @param array $config Report configuration
     * @return string Processed template
     */
    private function replaceShortcodes($template, $stock = null, $config = array())
    {
        $replacements = array(
            '[Current Date]' => date('Y-m-d H:i:s'),
            '[Report Title]' => isset($config['title']) ? $config['title'] : '',
            '[Author]' => isset($config['author']) ? $config['author'] : '',
        );

        // Add stock-specific replacements
        if ($stock !== null) {
            // Map CSV columns to shortcodes
            foreach ($stock as $key => $value) {
                // Handle both formatted key [Key] and raw key
                $replacements['[' . $key . ']'] = $value;
            }

            // TradingView chart widget - use the ticker symbol
            $ticker = isset($stock['Ticker']) ? $stock['Ticker'] : '';
            $replacements['[Chart]'] = $this->generateTradingViewWidget($ticker);
            $replacements['[Target Price]'] = isset($stock['Target Price']) ? $stock['Target Price'] : (isset($stock['Price']) ? $stock['Price'] : '');
        }

        uksort($replacements, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Get default stock block template
     *
     * @return string Default template
     */
    private function getDefaultStockBlockTemplate()
    {
        return '<br><div class="stock-container pagebreak">
    <div style="" class="order-md-1">
        <h2 class="mt-1">[Company] ([Exchange]:[Ticker])</h2>
        [Chart]<br>
        <strong>Stock Price: </strong>$[Price]<br>
        <strong>Market Cap</strong>: $[Market Cap]<br>
        <strong>Consensus Price Target: </strong>$[Target Price]
    </div>
    <div class="w-100 mt-2 order-md-3">[Description]</div>
</div>';
    }

    /**
     * Generate TradingView widget HTML using iframe
     *
     * @param string $ticker Stock ticker symbol
     * @return string TradingView widget HTML
     */
    private function generateTradingViewWidget($ticker)
    {
        // Build the widget config - use the exact format from dummy report
        $widgetConfig = array(
            'symbol' => $ticker,
            'width' => '600',
            'height' => '220',
            'dateRange' => '12m',
            'colorTheme' => 'light',
            'trendLineColor' => '#37a6ef',
            'underLineColor' => '#E3F2FD',
            'isTransparent' => false,
            'autosize' => true,
            'largeChartUrl' => '',
            'utm_source' => 'finstrategist.com',
            'utm_medium' => 'widget',
            'utm_campaign' => 'mini-symbol-overview',
            'page-uri' => 'finstrategist.com/go/news/rep164556/TopStocksReport'
        );

        $encodedConfig = urlencode(json_encode($widgetConfig));
        $iframeSrc = 'https://www.tradingview-widget.com/embed-widget/mini-symbol-overview/?locale=en#' . $encodedConfig;

        $widget = '<!-- TradingView Widget BEGIN -->
            <div class="tradingview-widget-container" style="width: 600px; height: 220px;">
            <iframe scrolling="no" allowtransparency="true" frameborder="0" src="' . $iframeSrc . '" title="mini symbol-overview TradingView widget" lang="en" style="user-select: none; box-sizing: border-box; display: block; height: 100%; width: 100%;"></iframe>

            <style>
	.tradingview-widget-copyright {
		font-size: 13px !important;
		line-height: 32px !important;
		text-align: center !important;
		vertical-align: middle !important;
		/* @mixin sf-pro-display-font; */
		font-family: -apple-system, BlinkMacSystemFont, \'Trebuchet MS\', Roboto, Ubuntu, sans-serif !important;
		color: #B2B5BE !important;
	}

	.tradingview-widget-copyright .blue-text {
		color: #2962FF !important;
	}

	.tradingview-widget-copyright a {
		text-decoration: none !important;
		color: #B2B5BE !important;
	}

	.tradingview-widget-copyright a:visited {
		color: #B2B5BE !important;
	}

	.tradingview-widget-copyright a:hover .blue-text {
		color: #1E53E5 !important;
	}

	.tradingview-widget-copyright a:active .blue-text {
		color: #1848CC !important;
	}

	.tradingview-widget-copyright a:visited .blue-text {
		color: #2962FF !important;
	}
	</style></div>
            <!-- TradingView Widget END -->';

        return $widget;
    }

    /**
     * Build complete HTML document for PDF generation
     *
     * @param array $config Report configuration
     * @param array $stocks Stock data
     * @return string Complete HTML document
     */
    private function buildPdfHtml(array $config, array $stocks)
    {
        $title = isset($config['title']) ? htmlspecialchars($config['title']) : 'Stock Report';

        // Build the article body content (same as an HTML report)
        $articleBody = '<h1>' . $title . '</h1>';
        $articleBody .= '<div id="article-body">';

        // Add article image and intro content - use same structure as an HTML report
        $articleBody .= '<div class="mainarticle" style="width: 100%; line-height: 1.6;">';

        // Add article image if exists - wrap in p tag with inline float style like an HTML report
        if (!empty($config['images']['article_image'])) {
            $imagePath = '../../images/' . $config['images']['article_image'];
            $articleBody .= '<p><img alt="" src="' . $imagePath . '" style="float: left; width: 200px; height: 200px; margin: 0 20px 10px 0;"></p>';
        }

        // Add intro content from form template directly
        if (!empty($config['content_templates']['intro_html'])) {
            $introHtml = $this->replaceShortcodes(
                $config['content_templates']['intro_html'],
                null,
                $config
            );
            $articleBody .= $introHtml;
        }

        $articleBody .= '</div><br>';

        // Add stock blocks using form template
        $stockBlockTemplate = !empty($config['content_templates']['stock_block_html'])
            ? $config['content_templates']['stock_block_html']
            : $this->getDefaultStockBlockTemplate();

        foreach ($stocks as $stock) {
            $stockHtml = $this->replaceShortcodes(
                $stockBlockTemplate,
                $stock,
                $config
            );
            $stockHtml = preg_replace('/<!-- TradingView Widget BEGIN.*?TradingView Widget END -->/s', '', $stockHtml);
            $articleBody .= $stockHtml;
        }

        // No disclaimer in PDF
        $articleBody .= '</div>';

        // Build a complete HTML document with proper styling for PDF
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            font-size: 12px;
        }
        .cover-page {
            page-break-after: always;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100vh;
        }
        .cover-page img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .mainarticle {
            width: 100%;
        }
        .stock-container {
            page-break-inside: avoid;
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .stock-container h2 {
            margin-top: 0;
            font-size: 16px;
        }
        .w-100 {
            width: 100%;
            margin-top: 10px;
        }
        .order-md-1, .order-md-3 {
            width: 100%;
        }
        strong {
            font-weight: bold;
        }
        .intro-image {
            float: left;
            height: 200px;
            width: auto;
            margin: 0 20px 10px 0;
        }
        .intro-content {
            line-height: 1.6;
        }
        img {
            float: left;
            width: 150px;
            height: 150px;
            margin: 10px;
        }
    </style>
</head>
<body>';

        // Add cover page with PDF cover image (full page)
        if (!empty($config['images']['pdf_cover_image'])) {
            $imagePath = $this->imagesPath . '/' . $config['images']['pdf_cover_image'];
            if (file_exists($imagePath)) {
                // mPDF needs path relative to the mpdf.php script location (lib/mpdf/)
                $relativePath = '../../images/' . $config['images']['pdf_cover_image'];
                $html .= '<div style="height: 800px;width: 100%; text-align: center;page-break-after: always">
                    <img src="' . $relativePath . '" alt="Cover" style="width: 100%; height: 100%; object-fit: contain;" />
                </div>';
            }
        }

        $html .= $articleBody . '
</body>
</html>';

        return $html;
    }

    /**
     * Build flipbook HTML with page-flip effect using turn.js
     *
     * @param array $config Report configuration
     * @param array $stocks Stock data
     * @return string Complete flipbook HTML
     */
    private function buildFlipbookHtml(array $config, array $stocks)
    {
        $title = isset($config['title']) ? htmlspecialchars($config['title']) : 'Stock Report';

        // Build pages content
        $pages = '';

        // Cover page - use PDF cover image if available
        if (!empty($config['images']['pdf_cover_image'])) {
            $imagePath = '../images/' . $config['images']['pdf_cover_image'];
            $pages .= '<div class="page">
				<img src="' . $imagePath . '" draggable="false" alt="" height="100%" width="100%" />
			</div>';
        }

        // Disclaimer page
        if (!empty($config['content_templates']['disclaimer_html'])) {
            $pages .= '<div class="page">
			    <div id="disclaimer">
			        <div>' . $config['content_templates']['disclaimer_html'] . '</div>
			    </div>
			</div>';
        }

        // Stock pages
        $stockNum = 1;
        foreach ($stocks as $stock) {
            $company = isset($stock['Company']) ? htmlspecialchars($stock['Company']) : '';
            $ticker = isset($stock['Ticker']) ? htmlspecialchars($stock['Ticker']) : '';
            $price = isset($stock['Price']) ? htmlspecialchars($stock['Price']) : '';
            $targetPrice = isset($stock['Target Price']) ? htmlspecialchars($stock['Target Price']) : (isset($stock['Price']) ? htmlspecialchars($stock['Price']) : '');
            $exchange = isset($stock['Exchange']) ? htmlspecialchars($stock['Exchange']) : '';
            $marketCap = isset($stock['Market Cap']) ? htmlspecialchars($stock['Market Cap']) : '';
            $description = isset($stock['Description']) ? $stock['Description'] : '';

            // Generate TradingView widget
            $chartWidget = $this->generateTradingViewWidget($ticker);

            $pages .= '<div class="page pagebreak">
	<div class="stock-container">
        <div class="stock-container-2">
            <div style="" class="order-md-1">
                <h2 class="mt-1">' . $stockNum . ') ' . $company . ' (<a target="_blank" href="https://trendadvisor.net/go/stocks/' . $exchange . '/' . $ticker . '/">' . $exchange . ':' . $ticker . '</a>)</h2>
                ' . $chartWidget . '
                <br>
                <strong>Closing Price: </strong>$' . $price . '
                <br>
		        <strong>Dividend Yield: </strong>
		        <br>
		        <strong>Market Cap</strong>: $' . $marketCap . '
		        <br>
		        <strong>Consensus Price Target: </strong>$' . $targetPrice . '
            </div>
            <div class="w-100 mt-2 order-md-3 stock-description-2">' . $description . '</div>
        </div>
    </div>
</div>';
            $stockNum++;
        }

        // Build complete flipbook HTML
        return '<!DOCTYPE html>
		<html>
		<head>
		  	<title>' . $title . '</title>
		  	<meta charset="UTF-8">
			<script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
			<script src="https://go.trendadvisor.net/tools/flipbook/js/turn.min.js" type="text/javascript"></script>
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
			<link rel="stylesheet" href="https://go.trendadvisor.net/tools/flipbook/css/flipbook.css">
		</head>
		<body>
        <div class="wrapper">
			<div class="flipbook-viewport">
				<div class="container">
					<div class="flipbook" id="flipbook">
						' . $pages . '
					</div>
			        <div class="flip-control">
			            <a href="#" id="prev"><i class="fa fa-angle-left" style="font-size:3rem;color:black;font-weight: 600;"></i></a>
			            <a href="#" id="next"><i class="fa fa-angle-right" style="font-size:3rem;color:black;font-weight: 600;"></i></a>
			        </div>
		    	</div>
			</div>
		</div>
		<script type="text/javascript">
		    window.addEventListener("resize", resize);

		    document.body.addEventListener("touchmove", function(e) {
		      e.preventDefault();
		    });

		    function loadApp() {
		      var size = getSize();

		      // Create the flipbook
		      $(".flipbook").turn({
		          // Width
		         width: size.width,
		          // Height
		         height: size.height,
		          // Elevation
		          elevation: 50,
		          // Enable gradients
		          gradients: true,
		          // Auto center this flipbook
		          autoCenter: true,
		      });
		     $(".flipbook").turn("display", "single");
		    }

		    function getSize() {
		      console.log("get size");
		      var width;
		      var height;
		      if($(window).width() > 980){
		        height = ($(".wrapper").height()*0.9);
		        width = height*0.77;
		      }
		      else{
		        width = (document.body.clientWidth)*0.8;
		        height = width*1.3;
		      }
		      return {
		        width: width,
		        height: height
		      }
		    }

		    function resize() {
		      console.log("resize event triggered");

		      var size = getSize();
		      console.log(size);

		      if (size.width > size.height) { // landscape
		        $(".flipbook").turn("display", "double");
		      }
		      else {
		        $(".flipbook").turn("display", "single");
		      }

		      $(".flipbook").turn("size", size.width, size.height);
		    }

		    var oTurn = $(".flipbook");
		    $("#prev").click(function(e){
		      e.preventDefault();
		      oTurn.turn("previous");
		    });

		    $("#next").click(function(e){
		      e.preventDefault();
		      oTurn.turn("next");
		    });

		    $(".wrapper").css({"height":$(window).height()});

		    // Load App
		    loadApp();
		</script>
	</body>
	</html>';
    }

    /**
     * Convert HTML to PDF using mPDF
     *
     * @param string $html HTML content
     * @param string $pdfFile Path to output PDF file
     * @return array Result with success status and message
     */
    private function convertHtmlToPdfWithMpdf($html, $pdfFile)
    {
        // Load mPDF only when needed
        require_once __DIR__ . '/../../lib/mpdf/mpdf.php';

        try {
            // Initialize mPDF (version 6.x constructor syntax)
            $mpdf = new mPDF('utf-8', 'A4', 0, '', 15, 15, 16, 16, 9, 9, 'P');

            // Set some document properties
            $mpdf->SetDisplayMode('fullpage');

            // Write HTML to mPDF
            $mpdf->WriteHTML($html);

            // Output the PDF to file (destination 'F' = save to local file)
            $mpdf->Output($pdfFile, 'F');

            // Check if file was created
            if (file_exists($pdfFile)) {
                return array('success' => true);
            }

            return array(
                'success' => false,
                'message' => 'PDF file was not created'
            );
        } catch (MpdfException $e) {
            return array(
                'success' => false,
                'message' => 'mPDF error: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'PDF generation error: ' . $e->getMessage()
            );
        }
    }
}
