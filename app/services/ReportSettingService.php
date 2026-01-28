<?php

/**
 * ReportSettingService - Service for managing report configurations
 * Handles CRUD operations for report settings stored in the report_settings.json file
 */

require_once __DIR__ . '/../api/common.php';

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
     * @var string Path to the settings file
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
     * Load settings file and return decoded data
     *
     * @return array|null Decoded JSON data or null if file doesn't exist/invalid
     */
    private function loadSettingsFile()
    {
        if (!file_exists($this->settingsFile)) {
            return null;
        }

        $content = file_get_contents($this->settingsFile);

        return json_decode($content, true);
    }

    /**
     * Get all report configurations
     *
     * @return array Result with success status and data
     */
    public function getConfigurations()
    {
        $data = $this->loadSettingsFile();

        if ($data === null) {
            return array(
                'success' => true,
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
        $data = $this->loadSettingsFile();

        if ($data === null || !isset($data['reports'])) {
            return array(
                'success' => false,
                'message' => 'Configuration not found'
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
        $data = $this->loadSettingsFile();
        if ($data === null) {
            $data = array('last_id' => 0, 'reports' => array());
        }

        // Determine if this is an update or create
        $isUpdate = isset($input['id']) && !empty($input['id']);

        // Keep the original file_name for JSON storage, sanitize for file operations
        $originalFileName = isset($input['file_name']) ? $input['file_name'] : '';
        $sanitizedFileName = sanitizeFilename($originalFileName);

        // Check for duplicate filename (skip own filename when updating)
        if (!empty($originalFileName)) {
            foreach ($data['reports'] as $r) {
                // Skip own record when updating
                if ($isUpdate && isset($r['id']) && $r['id'] == $input['id']) {
                    continue;
                }
                // Check for duplicate filename (case-insensitive)
                if (isset($r['file_name']) && strtolower($r['file_name']) === strtolower($originalFileName)) {
                    return array('success' => false, 'message' => 'Configuration with filename "' . $originalFileName . '" already exists. Please use a different filename.');
                }
            }
        }

        // Handle article image upload
        $articleImage = null;
        if (isset($files['article_image']) && $files['article_image']['error'] == 0) {
            $articleImage = handleImageUpload($files['article_image'], $this->imagesPath, $sanitizedFileName, 'article');
        }

        // Handle PDF cover image upload
        $coverImage = null;
        if (isset($files['pdf_cover']) && $files['pdf_cover']['error'] == 0) {
            $coverImage = handleImageUpload($files['pdf_cover'], $this->imagesPath, $sanitizedFileName, 'cover');
        }

        // Build report object
        $report = array(
            'id' => $isUpdate ? $input['id'] : ($data['last_id'] + 1),
            'file_name' => $originalFileName,  // Store original user input
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
                return array('success' => false, 'message' => 'Configuration not found');
            }
        } else {
            // Create a new report setting
            $data['last_id'] = $report['id'];
            $data['reports'][] = $report;
        }

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

        $data = $this->loadSettingsFile();

        if ($data === null || !isset($data['reports'])) {
            return array('success' => false, 'message' => 'Configuration not found');
        }

        $found = false;

        for ($i = 0; $i < count($data['reports']); $i++) {
            if (isset($data['reports'][$i]['id']) && $data['reports'][$i]['id'] == $id) {
                array_splice($data['reports'], $i, 1);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return array('success' => false, 'message' => 'Configuration not found');
        }

        // Write updated data back to the file
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $result = file_put_contents($this->settingsFile, $json);

        if ($result !== false) {
            return array('success' => true, 'message' => 'Configuration deleted successfully');
        }

        return array('success' => false, 'message' => 'Failed to write file');
    }
}
