<?php

declare(strict_types=1);

/**
 * PhpSpreadsheet Excel Service Implementation
 *
 * @package RIILSA\Infrastructure\Services
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Services;

/**
 * Excel file service using PhpSpreadsheet
 * 
 * Pattern: Service Pattern
 * This class provides Excel file manipulation capabilities
 */
class PhpSpreadsheetExcelService
{
    /**
     * Check if PhpSpreadsheet is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        // Check for WP plugin that provides PhpSpreadsheet
        if (defined('CBXPHPSPREADSHEET_PLUGIN_NAME') && 
            file_exists(CBXPHPSPREADSHEET_ROOT_PATH . 'lib/vendor/autoload.php')) {
            require_once(CBXPHPSPREADSHEET_ROOT_PATH . 'lib/vendor/autoload.php');
            return true;
        }
        
        // Check if it's available through Composer
        return class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);
    }
    
    /**
     * Get the last uploaded file for a content type
     *
     * @param string $contentType
     * @return string|null URL to the file or null if not found
     */
    public function getLastUploadedFile(string $contentType): ?string
    {
        $uploadDir = wp_upload_dir();
        $basePath = $uploadDir['basedir'] . '/gestion_contenido_excel';
        
        if (!is_dir($basePath)) {
            return null;
        }
        
        $latestFile = null;
        $latestTime = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match("/^{$contentType}_.*\.xlsx$/i", $file->getFilename())) {
                $modTime = $file->getMTime();
                if ($modTime > $latestTime) {
                    $latestFile = $file->getPathname();
                    $latestTime = $modTime;
                }
            }
        }
        
        if ($latestFile) {
            // Convert to URL
            return str_replace(
                $uploadDir['basedir'],
                $uploadDir['baseurl'],
                str_replace('\\', '/', $latestFile)
            );
        }
        
        return null;
    }
    
    /**
     * Create a new spreadsheet
     *
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     * @throws \RuntimeException
     */
    public function createSpreadsheet(): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('PhpSpreadsheet is not available');
        }
        
        return new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }
    
    /**
     * Load spreadsheet from file
     *
     * @param string $filePath
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     * @throws \RuntimeException
     */
    public function loadSpreadsheet(string $filePath): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('PhpSpreadsheet is not available');
        }
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found: ' . $filePath);
        }
        
        try {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to load spreadsheet: ' . $e->getMessage());
        }
    }
    
    /**
     * Save spreadsheet to file
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param string $filePath
     * @param string $format Default 'Xlsx'
     * @return void
     * @throws \RuntimeException
     */
    public function saveSpreadsheet(
        \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,
        string $filePath,
        string $format = 'Xlsx'
    ): void {
        try {
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, $format);
            $writer->save($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to save spreadsheet: ' . $e->getMessage());
        }
    }
}
