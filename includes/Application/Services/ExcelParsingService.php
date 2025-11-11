<?php

declare(strict_types=1);

/**
 * Excel Parsing Application Service
 *
 * @package RIILSA\Application\Services
 * @since 3.1.0
 */

namespace RIILSA\Application\Services;

use RIILSA\Domain\Services\ExcelValidationService;

/**
 * Application service for parsing Excel files
 * 
 * Pattern: Application Service Pattern
 * This service handles Excel file parsing and data extraction
 */
class ExcelParsingService
{
    /**
     * Excel validation service
     *
     * @var ExcelValidationService
     */
    private ExcelValidationService $validationService;
    
    /**
     * Constructor
     *
     * @param ExcelValidationService $validationService
     */
    public function __construct(ExcelValidationService $validationService)
    {
        $this->validationService = $validationService;
    }
    
    /**
     * Parse Excel file and extract data
     *
     * @param string $filePath
     * @param string $contentType
     * @return array
     * @throws \RuntimeException
     */
    public function parseFile(string $filePath, string $contentType): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }
        
        if (!$this->checkPhpSpreadsheetAvailable()) {
            throw new \RuntimeException('PhpSpreadsheet library is not available');
        }
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Validate empty file
            if ($worksheet->getHighestDataRow() < 2) {
                throw new \RuntimeException('Excel file is empty or contains no data');
            }
            
            // Extract headers
            $headers = $this->extractHeaders($worksheet);
            
            // Validate structure
            $structureValidation = $this->validationService->validateStructure($headers, $contentType);
            if (!$structureValidation['valid']) {
                throw new \RuntimeException(
                    'Invalid Excel structure: ' . implode('; ', $structureValidation['errors'])
                );
            }
            
            // Extract data rows
            $data = $this->extractData($worksheet, $contentType);
            
            // Clean up
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            return [
                'headers' => $headers,
                'data' => $data,
                'contentType' => $contentType,
                'rowCount' => count($data),
            ];
            
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            throw new \RuntimeException('Error reading Excel file: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if PhpSpreadsheet is available
     *
     * @return bool
     */
    private function checkPhpSpreadsheetAvailable(): bool
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
     * Extract headers from worksheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
     * @return array
     */
    private function extractHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet): array
    {
        $headers = [];
        $columnIterator = $worksheet->getColumnIterator();
        
        foreach ($columnIterator as $column) {
            $cell = $worksheet->getCell($column->getColumnIndex() . '1');
            $value = trim($cell->getFormattedValue());
            
            if (empty($value)) {
                break; // Stop at first empty header
            }
            
            $headers[] = $value;
        }
        
        return $headers;
    }
    
    /**
     * Extract data from worksheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
     * @param string $contentType
     * @return array
     */
    private function extractData(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        string $contentType
    ): array {
        $data = [];
        $highestRow = $worksheet->getHighestDataRow();
        $highestColumn = $worksheet->getHighestDataColumn();
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $hasData = false;
            
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $worksheet->getCell($col . $row);
                $value = $cell->getFormattedValue();
                
                if (!empty($value)) {
                    $hasData = true;
                }
                
                // Map to field name
                $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col) - 1;
                $fieldName = $this->getFieldName($columnIndex, $contentType);
                
                if ($fieldName && $this->shouldIncludeField($columnIndex, $contentType)) {
                    $rowData[$fieldName] = $this->processFieldValue($value, $fieldName, $contentType);
                }
            }
            
            // Only add rows that have data
            if ($hasData) {
                $data[] = $rowData;
            }
        }
        
        return $data;
    }
    
    /**
     * Get field name for column index
     *
     * @param int $columnIndex
     * @param string $contentType
     * @return string|null
     */
    private function getFieldName(int $columnIndex, string $contentType): ?string
    {
        $fieldMappings = [
            'Projects' => [
                0 => 'id',
                1 => 'hora_inicio',
                2 => 'hora_fin',
                3 => 'email',
                4 => 'nombre_corto',
                5 => 'nombre',
                6 => 'correo',
                7 => 'universidad',
                8 => 'pais',
                9 => 'area',
                10 => 'linea',
                11 => 'titulo',
                12 => 'objetivo',
                13 => 'fecha_inicio',
                14 => 'fecha_termino',
                15 => 'pagina',
                16 => 'ods',
                17 => 'resultados',
                18 => 'resumen',
                19 => 'problematica',
                20 => 'quien',
            ],
            'Calls' => [
                0 => 'id',
                1 => 'hora_inicio',
                2 => 'hora_fin',
                3 => 'email',
                4 => 'nombre',
                5 => 'titulo',
                6 => 'contacto',
                7 => 'descripcion',
                8 => 'link',
                9 => 'apertura',
                10 => 'cierre',
            ],
            'News' => [
                0 => 'marca',
                1 => 'titulo',
                2 => 'bullets',
                3 => 'cuerpo',
                4 => 'datos',
                5 => 'numero',
                6 => 'imagen',
                7 => 'linea',
            ],
        ];
        
        return $fieldMappings[$contentType][$columnIndex] ?? null;
    }
    
    /**
     * Check if field should be included
     *
     * @param int $columnIndex
     * @param string $contentType
     * @return bool
     */
    private function shouldIncludeField(int $columnIndex, string $contentType): bool
    {
        $excludedColumns = [
            'Projects' => [1, 3], // hora_inicio, first email
            'Calls' => [1, 3, 4], // hora_inicio, email, nombre
            'News' => [], // Include all
        ];
        
        return !in_array($columnIndex, $excludedColumns[$contentType] ?? []);
    }
    
    /**
     * Process field value
     *
     * @param mixed $value
     * @param string $fieldName
     * @param string $contentType
     * @return mixed
     */
    private function processFieldValue(mixed $value, string $fieldName, string $contentType): mixed
    {
        // Trim string values
        if (is_string($value)) {
            $value = trim($value);
        }
        
        // Handle date fields
        $dateFields = ['fecha_inicio', 'fecha_termino', 'apertura', 'cierre', 'marca'];
        if (in_array($fieldName, $dateFields)) {
            return $this->processDateValue($value);
        }
        
        // Handle numeric fields
        $numericFields = ['id', 'numero'];
        if (in_array($fieldName, $numericFields)) {
            return is_numeric($value) ? (int)$value : $value;
        }
        
        // Handle URL fields
        $urlFields = ['pagina', 'link', 'imagen'];
        if (in_array($fieldName, $urlFields)) {
            return filter_var($value, FILTER_SANITIZE_URL);
        }
        
        // Handle email fields
        if (in_array($fieldName, ['correo', 'email'])) {
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        }
        
        return $value;
    }
    
    /**
     * Process date value
     *
     * @param mixed $value
     * @return string
     */
    private function processDateValue(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        // Try to parse the date
        $timestamp = strtotime((string)$value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        // If it's already in Y-m-d format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
            return (string)$value;
        }
        
        return '';
    }
    
    /**
     * Validate parsed data
     *
     * @param array $data
     * @param string $contentType
     * @return array
     */
    public function validateParsedData(array $data, string $contentType): array
    {
        return $this->validationService->validateBatch($data, $contentType);
    }
}
