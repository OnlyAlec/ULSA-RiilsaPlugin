<?php

declare(strict_types=1);

/**
 * Excel Validation Domain Service
 *
 * @package RIILSA\Domain\Services
 * @since 3.1.0
 */

namespace RIILSA\Domain\Services;

/**
 * Domain service for Excel file validation
 * 
 * Pattern: Domain Service Pattern
 * This service contains business logic for validating Excel data
 * that doesn't naturally fit within a single entity
 */
class ExcelValidationService
{
    /**
     * Excel column structures for each content type
     *
     * @var array
     */
    private array $excelStructures = [
        'Projects' => [
            'Id',
            'Hora de inicio',
            'Hora de finalización',
            'Correo electrónico',
            'Nombre',
            'Nombre completo (Apellido paterno, materno y nombre)',
            'Correo electrónico institucional',
            'Universidad',
            'País',
            'Área del conocimiento',
            'Línea Generadora y de Aplicación del Conocimiento',
            'Título del proyecto',
            'Objetivo del proyecto',
            'Fecha de inicio',
            'Fecha de termino',
            'Página web o sitio para más información',
            'ODS',
            'Resultados esperados',
            'Resumen de divulgación',
            'Problematica',
            'A quién va dirigido'
        ],
        'Calls' => [
            'Id',
            'Hora de inicio',
            'Hora de finalización',
            'Correo electrónico',
            'Nombre',
            'Título de la convocatoría',
            'Contacto',
            'Descripción',
            'Link de la publicación',
            'Apertura',
            'Cierre'
        ],
        'News' => [
            'Marca temporal',
            'Título',
            'Dos Bullets',
            'Cuerpo de la nota',
            'Datos de contacto ',
            'Número de boletín',
            'Imagen de la nota',
            'Línea Generadora de Investigación'
        ],
    ];
    
    /**
     * Required fields for each content type
     *
     * @var array
     */
    private array $requiredFields = [
        'Projects' => [
            'id', 'titulo', 'objetivo', 'nombre', 'correo', 
            'universidad', 'pais', 'area', 'linea', 
            'fecha_inicio', 'fecha_termino'
        ],
        'Calls' => [
            'id', 'titulo', 'contacto', 'descripcion', 
            'apertura', 'cierre'
        ],
        'News' => [
            'titulo', 'cuerpo'
        ],
    ];
    
    /**
     * Validate Excel structure
     *
     * @param array $headers The header row from Excel
     * @param string $contentType The type of content (Projects, Calls, News)
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateStructure(array $headers, string $contentType): array
    {
        if (!isset($this->excelStructures[$contentType])) {
            return [
                'valid' => false,
                'errors' => ["Invalid content type: {$contentType}"]
            ];
        }
        
        $expectedStructure = $this->excelStructures[$contentType];
        $errors = [];
        $headerValues = array_map('trim', array_values($headers));
        
        // Check for missing columns
        foreach ($expectedStructure as $index => $expectedColumn) {
            if (!isset($headerValues[$index])) {
                $errors[] = "Missing column at position " . ($index + 1) . ": {$expectedColumn}";
                continue;
            }
            
            if ($headerValues[$index] !== $expectedColumn) {
                $errors[] = "Column mismatch at position " . ($index + 1) . 
                           ": expected '{$expectedColumn}', found '{$headerValues[$index]}'";
            }
        }
        
        // Check for extra columns
        if (count($headerValues) > count($expectedStructure)) {
            $extraColumns = array_slice($headerValues, count($expectedStructure));
            $errors[] = "Extra columns found: " . implode(', ', $extraColumns);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate a data row
     *
     * @param array $rowData The data row from Excel
     * @param string $contentType The type of content
     * @param int $rowNumber The row number for error reporting
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateRow(array $rowData, string $contentType, int $rowNumber): array
    {
        $errors = [];
        $requiredFields = $this->requiredFields[$contentType] ?? [];
        
        foreach ($requiredFields as $field) {
            if (empty($rowData[$field])) {
                $errors[] = "Row {$rowNumber}: Required field '{$field}' is empty";
            }
        }
        
        // Type-specific validations
        switch ($contentType) {
            case 'Projects':
                $errors = array_merge($errors, $this->validateProjectRow($rowData, $rowNumber));
                break;
                
            case 'Calls':
                $errors = array_merge($errors, $this->validateCallRow($rowData, $rowNumber));
                break;
                
            case 'News':
                $errors = array_merge($errors, $this->validateNewsRow($rowData, $rowNumber));
                break;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate project-specific fields
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     */
    private function validateProjectRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        
        // Validate email
        if (!empty($rowData['correo']) && !filter_var($rowData['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row {$rowNumber}: Invalid email format";
        }
        
        // Validate dates
        if (!empty($rowData['fecha_inicio']) && !$this->isValidDate($rowData['fecha_inicio'])) {
            $errors[] = "Row {$rowNumber}: Invalid start date format";
        }
        
        if (!empty($rowData['fecha_termino']) && !$this->isValidDate($rowData['fecha_termino'])) {
            $errors[] = "Row {$rowNumber}: Invalid end date format";
        }
        
        // Validate date range
        if (!empty($rowData['fecha_inicio']) && !empty($rowData['fecha_termino'])) {
            $start = strtotime($rowData['fecha_inicio']);
            $end = strtotime($rowData['fecha_termino']);
            
            if ($start && $end && $start > $end) {
                $errors[] = "Row {$rowNumber}: Start date must be before end date";
            }
        }
        
        // Validate URL if present
        if (!empty($rowData['pagina']) && !filter_var($rowData['pagina'], FILTER_VALIDATE_URL)) {
            $errors[] = "Row {$rowNumber}: Invalid website URL format";
        }
        
        return $errors;
    }
    
    /**
     * Validate call-specific fields
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     */
    private function validateCallRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        
        // Validate dates
        if (!empty($rowData['apertura']) && !$this->isValidDate($rowData['apertura'])) {
            $errors[] = "Row {$rowNumber}: Invalid opening date format";
        }
        
        if (!empty($rowData['cierre']) && !$this->isValidDate($rowData['cierre'])) {
            $errors[] = "Row {$rowNumber}: Invalid closing date format";
        }
        
        // Validate date range
        if (!empty($rowData['apertura']) && !empty($rowData['cierre'])) {
            $start = strtotime($rowData['apertura']);
            $end = strtotime($rowData['cierre']);
            
            if ($start && $end && $start > $end) {
                $errors[] = "Row {$rowNumber}: Opening date must be before closing date";
            }
        }
        
        // Validate URL if present
        if (!empty($rowData['link']) && !filter_var($rowData['link'], FILTER_VALIDATE_URL)) {
            $errors[] = "Row {$rowNumber}: Invalid publication link URL format";
        }
        
        return $errors;
    }
    
    /**
     * Validate news-specific fields
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     */
    private function validateNewsRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        
        // Validate newsletter number if present
        if (!empty($rowData['numero']) && !is_numeric($rowData['numero'])) {
            $errors[] = "Row {$rowNumber}: Newsletter number must be numeric";
        }
        
        // Validate image URL if present
        if (!empty($rowData['imagen']) && !$this->isValidImageUrl($rowData['imagen'])) {
            $errors[] = "Row {$rowNumber}: Invalid image URL format";
        }
        
        return $errors;
    }
    
    /**
     * Check if a string is a valid date
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        $timestamp = strtotime($date);
        return $timestamp !== false;
    }
    
    /**
     * Check if a URL is a valid image URL
     *
     * @param string $url
     * @return bool
     */
    private function isValidImageUrl(string $url): bool
    {
        // First check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if it's a Google Drive URL or has an image extension
        if (preg_match(RIILSA_REGEX_GOOGLE_DRIVE, $url)) {
            return true;
        }
        
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }
    
    /**
     * Validate batch of rows
     *
     * @param array $rows
     * @param string $contentType
     * @return array
     */
    public function validateBatch(array $rows, string $contentType): array
    {
        $errors = [];
        $validRows = [];
        $invalidRows = [];
        
        foreach ($rows as $index => $row) {
            $validation = $this->validateRow($row, $contentType, $index + 2); // +2 for Excel row number
            
            if ($validation['valid']) {
                $validRows[] = $row;
            } else {
                $invalidRows[] = [
                    'row' => $index + 2,
                    'data' => $row,
                    'errors' => $validation['errors']
                ];
                $errors = array_merge($errors, $validation['errors']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'validRows' => $validRows,
            'invalidRows' => $invalidRows,
            'errors' => $errors,
            'summary' => [
                'total' => count($rows),
                'valid' => count($validRows),
                'invalid' => count($invalidRows)
            ]
        ];
    }
    
    /**
     * Get expected structure for a content type
     *
     * @param string $contentType
     * @return array
     */
    public function getExpectedStructure(string $contentType): array
    {
        return $this->excelStructures[$contentType] ?? [];
    }
    
    /**
     * Get required fields for a content type
     *
     * @param string $contentType
     * @return array
     */
    public function getRequiredFields(string $contentType): array
    {
        return $this->requiredFields[$contentType] ?? [];
    }
}
