<?php

/**
 * ReportSettingService - Service for managing report configurations
 * Handles CRUD operations for report settings stored in the report_settings.json file
 */

date_default_timezone_set('UTC');

class ReportSettingService
{
    /**
     * @var string Path to data directory
     */
    private $dataPath;

    /**
     * @var string Path to images directory
     */
    private $imagesPath;

    /**
     * @var string Path to reports directory
     */
    private $reportsPath;

    /**
     * @var string Path to settings file
     */
    private $settingsFile;

    /**
     * ReportSettingService constructor
     */
    public function __construct()
    {
        $this->dataPath = __DIR__ . '/../../db';
        $this->imagesPath = __DIR__ . '/../../images';
        $this->reportsPath = __DIR__ . '/../../reports';
        $this->settingsFile = $this->dataPath . '/report_settings.json';

        // Ensure directories exist
        $this->ensureDirectoriesExist();
    }

    /**
     * Ensure all required directories exist
     */
    private function ensureDirectoriesExist()
    {
        if (!is_dir($this->imagesPath)) {
            mkdir($this->imagesPath, 0755, true);
        }
        if (!is_dir($this->reportsPath)) {
            mkdir($this->reportsPath, 0755, true);
        }
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    /**
     * Get all report configurations
     *
     * @return array Result with success status and data
     */
    public function getConfigurations()
    {
        if (!file_exists($this->settingsFile)) {
            return array(
                'success' => true,
                'data' => array()
            );
        }

        $content = file_get_contents($this->settingsFile);
        $data = json_decode($content, true);

        if ($data === null) {
            return array(
                'success' => false,
                'message' => 'Invalid JSON format',
                'data' => array()
            );
        }

        return array(
            'success' => true,
            'data' => isset($data['reports']) ? $data['reports'] : array()
        );
    }

    /**
     * Get a single configuration by ID
     *
     * @param int $id Configuration ID
     * @return array Result with success status and data
     */
    public function getConfigurationById($id)
    {
        if (!file_exists($this->settingsFile)) {
            return array(
                'success' => false,
                'message' => 'Settings file not found'
            );
        }

        $content = file_get_contents($this->settingsFile);
        $data = json_decode($content, true);

        if ($data === null || !isset($data['reports'])) {
            return array(
                'success' => false,
                'message' => 'Invalid data format'
            );
        }

        foreach ($data['reports'] as $report) {
            if (isset($report['id']) && $report['id'] == $id) {
                return array(
                    'success' => true,
                    'data' => $report
                );
            }
        }

        return array(
            'success' => false,
            'message' => 'Configuration not found'
        );
    }

    /**
     * Create or update a configuration
     *
     * @param array $input Form input data
     * @param array $files Uploaded files (optional)
     * @return array Result with success status and message
     */
    public function saveConfiguration(array $input, array $files = array())
    {
        // Read current data
        if (file_exists($this->settingsFile)) {
            $content = file_get_contents($this->settingsFile);
            $data = json_decode($content, true);
        } else {
            $data = array('last_id' => 0, 'reports' => array());
        }

        if ($data === null) {
            $data = array('last_id' => 0, 'reports' => array());
        }

        // Determine if this is an update or create
        $isUpdate = isset($input['id']) && !empty($input['id']);

        // Handle article image upload
        $articleImage = null;
        if (isset($files['article_image']) && $files['article_image']['error'] == 0) {
            $articleImage = $this->handleImageUpload($files['article_image'], $input['file_name'], 'article');
        }

        // Handle PDF cover image upload
        $coverImage = null;
        if (isset($files['pdf_cover']) && $files['pdf_cover']['error'] == 0) {
            $coverImage = $this->handleImageUpload($files['pdf_cover'], $input['file_name'], 'cover');
        }

        // Handle manual PDF upload
        $manualPdf = null;
        if (isset($files['manual_pdf']) && $files['manual_pdf']['error'] == 0) {
            $manualPdf = $this->handleManualPdfUpload($files['manual_pdf'], $input['file_name']);
        }

        // Build report object
        $report = array(
            'id' => $isUpdate ? $input['id'] : ($data['last_id'] + 1),
            'file_name' => isset($input['file_name']) ? $input['file_name'] : '',
            'title' => isset($input['report_title']) ? $input['report_title'] : '',
            'author' => isset($input['author_name']) ? $input['author_name'] : '',
            'number_of_stocks' => isset($input['stock_count']) ? intval($input['stock_count']) : 6,
            'data_source' => isset($input['data_source']) ? $input['data_source'] : 'data.csv',
            'images' => array(
                'article_image' => $articleImage,
                'pdf_cover_image' => $coverImage
            ),
            'content_templates' => array(
                'intro_html' => isset($input['report_intro_html']) ? $input['report_intro_html'] : '',
                'stock_block_html' => isset($input['stock_block_html']) ? $input['stock_block_html'] : '',
                'disclaimer_html' => isset($input['disclaimer_html']) ? $input['disclaimer_html'] : ''
            ),
            'created_at' => date('c'),
            'updated_at' => date('c')
        );

        if ($isUpdate) {
            // Update existing report - preserve images if not updated
            $updated = false;
            foreach ($data['reports'] as $index => $r) {
                if ($r['id'] == $input['id']) {
                    // Preserve existing images if new ones not uploaded
                    if ($report['images']['article_image'] === null && isset($r['images']['article_image'])) {
                        $report['images']['article_image'] = $r['images']['article_image'];
                    }
                    if ($report['images']['pdf_cover_image'] === null && isset($r['images']['pdf_cover_image'])) {
                        $report['images']['pdf_cover_image'] = $r['images']['pdf_cover_image'];
                    }
                    $report['created_at'] = $r['created_at'];
                    $data['reports'][$index] = $report;
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                return array('success' => false, 'message' => 'Report not found');
            }
        } else {
            // Create new report
            $data['last_id'] = $report['id'];
            $data['reports'][] = $report;
        }

        // Write to file
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if (file_put_contents($this->settingsFile, $json) !== false) {
            return array(
                'success' => true,
                'message' => $isUpdate ? 'Configuration updated successfully' : 'Configuration created successfully',
                'data' => $report
            );
        }

        return array('success' => false, 'message' => 'Failed to save configuration');
    }

    /**
     * Delete a configuration by ID
     *
     * @param int $id Configuration ID
     * @return array Result with success status and message
     */
    public function deleteConfiguration($id)
    {
        if ($id === null) {
            return array('success' => false, 'message' => 'ID is required');
        }

        if (!file_exists($this->settingsFile)) {
            return array('success' => false, 'message' => 'Settings file not found');
        }

        // Read current data
        $content = file_get_contents($this->settingsFile);
        $data = json_decode($content, true);

        if ($data === null || !isset($data['reports'])) {
            return array('success' => false, 'message' => 'Invalid data format');
        }

        // Find and remove the report with matching ID
        $initialCount = count($data['reports']);
        $data['reports'] = array_values(array_filter($data['reports'], function($report) use ($id) {
            return !isset($report['id']) || $report['id'] != $id;
        }));

        if (count($data['reports']) < $initialCount) {
            // Write updated data back to file
            $json = json_encode($data, JSON_PRETTY_PRINT);
            $result = file_put_contents($this->settingsFile, $json);

            if ($result !== false) {
                return array('success' => true, 'message' => 'Configuration deleted successfully');
            }

            return array('success' => false, 'message' => 'Failed to write file');
        }

        return array('success' => false, 'message' => 'Configuration not found');
    }

    /**
     * Handle image upload
     *
     * @param array $file Uploaded file data from $_FILES
     * @param string $fileName Base file name for the report
     * @param string $prefix Prefix for the image filename (article or cover)
     * @return string|null Generated filename or null on failure
     */
    private function handleImageUpload($file, $fileName, $prefix)
    {
        if ($file['error'] != 0) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . $fileName . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $this->imagesPath . '/' . $filename)) {
            return $filename;
        }

        return null;
    }

    /**
     * Handle manual PDF upload
     *
     * @param array $file Uploaded file data from $_FILES
     * @param string $fileName Base file name for the report
     * @return string|null Generated filename or null on failure
     */
    private function handleManualPdfUpload($file, $fileName)
    {
        if ($file['error'] != 0) {
            return null;
        }

        $filename = $fileName . '.pdf';

        if (move_uploaded_file($file['tmp_name'], $this->reportsPath . '/' . $filename)) {
            return $filename;
        }

        return null;
    }
}
