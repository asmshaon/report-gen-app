<?php

class ReportSettingService
{
    /**
     * @var string Path to the JSON file
     */
    private $filePath;

    /**
     * ReportSettingService constructor.
     */
    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../db/report_settings.json';
    }

    /**
     * Get all report settings
     *
     * @return array
     */
    public function getAll()
    {
        $data = $this->readFile();
        return $data;
    }

    /**
     * Get a single report setting by ID
     *
     * @param int|string $id
     * @return array|null
     */
    public function getById($id)
    {
        $data = $this->readFile();

        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Create a new report setting
     *
     * @param array $setting
     * @return array The created setting with ID
     */
    public function create(array $setting)
    {
        $data = $this->readFile();

        // Generate new ID
        $maxId = 0;
        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
        $setting['id'] = $maxId + 1;

        $data[] = $setting;
        $this->writeFile($data);

        return $setting;
    }

    /**
     * Update an existing report setting
     *
     * @param int|string $id
     * @param array $setting
     * @return array|null The updated setting or null if not found
     */
    public function update($id, array $setting)
    {
        $data = $this->readFile();
        $updated = false;

        foreach ($data as $index => $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                $setting['id'] = $id;
                $data[$index] = $setting;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->writeFile($data);
            return $setting;
        }

        return null;
    }

    /**
     * Delete a report setting by ID
     *
     * @param int|string $id
     * @return bool True if deleted, false if not found
     */
    public function delete($id)
    {
        $data = $this->readFile();
        $initialCount = count($data);

        $data = array_values(array_filter($data, function ($item) use ($id) {
            return !isset($item['id']) || $item['id'] != $id;
        }));

        if (count($data) < $initialCount) {
            $this->writeFile($data);
            return true;
        }

        return false;
    }

    /**
     * Read data from JSON file
     *
     * @return array
     */
    private function readFile()
    {
        if (!file_exists($this->filePath)) {
            return array();
        }

        $content = file_get_contents($this->filePath);

        if ($content === false || empty($content)) {
            return array();
        }

        $data = json_decode($content, true);

        if ($data === null) {
            return array();
        }

        return $data;
    }

    /**
     * Write data to JSON file
     *
     * @param array $data
     * @return bool
     */
    private function writeFile(array $data)
    {
        $dir = dirname($this->filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT);

        return file_put_contents($this->filePath, $json) !== false;
    }
}