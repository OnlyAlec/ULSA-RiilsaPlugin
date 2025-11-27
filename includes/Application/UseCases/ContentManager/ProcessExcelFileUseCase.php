<?php

declare(strict_types=1);

/**
 * Process Excel File Use Case
 *
 * @package RIILSA\Application\UseCases\ContentManager
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\ContentManager;

use RIILSA\Application\DTOs\ExcelProcessingResultDTO;
use RIILSA\Application\Services\ExcelParsingService;
use RIILSA\Domain\Services\ExcelValidationService;
use RIILSA\Domain\Entities\Project;
use RIILSA\Domain\Entities\News;
use RIILSA\Domain\Entities\Call;
use RIILSA\Domain\Repositories\ProjectRepositoryInterface;
use RIILSA\Domain\Repositories\NewsRepositoryInterface;
use RIILSA\Domain\Repositories\CallRepositoryInterface;
use function RIILSA\Core\debugLog;

/**
 * Use case for processing Excel files
 * 
 * Pattern: Use Case Pattern
 * This class orchestrates the entire Excel file processing workflow
 */
class ProcessExcelFileUseCase
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly ExcelParsingService $parsingService,
        private readonly ExcelValidationService $validationService,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly NewsRepositoryInterface $newsRepository,
        private readonly CallRepositoryInterface $callRepository,
        private readonly CreateProjectUseCase $createProjectUseCase,
        private readonly CreateNewsUseCase $createNewsUseCase,
        private readonly CreateCallUseCase $createCallUseCase
    ) {
    }
    
    /**
     * Execute the use case
     *
     * @param string $filePath Path to the uploaded Excel file
     * @param string $contentType Type of content (Projects, Calls, News)
     * @param array $options Additional options
     * @return ExcelProcessingResultDTO
     */
    public function execute(
        string $filePath,
        string $contentType,
        array $options = []
    ): ExcelProcessingResultDTO {
        try {
            // Parse the Excel file
            $parsedData = $this->parsingService->parseFile($filePath, $contentType);
            
            // Validate the data
            $validationResult = $this->validationService->validateBatch(
                $parsedData['data'],
                $contentType
            );
            
            if (!$validationResult['valid'] && empty($validationResult['validRows'])) {
                return ExcelProcessingResultDTO::failure(
                    'No valid data found in Excel file',
                    $validationResult['errors']
                );
            }
            
            // Save the file
            $savedPath = $this->saveExcelFile($filePath, $contentType);
            
            // Process valid rows
            $results = $this->processRows(
                $validationResult['validRows'],
                $contentType,
                $options
            );
            
            // Build result DTO
            $totalProcessed = count($results['processed']);
            $totalFailed = count($results['failed']) + count($validationResult['invalidRows']);
            
            $message = sprintf(
                'Excel processing completed: %d items processed successfully',
                $totalProcessed
            );
            
            if ($totalFailed > 0) {
                $message .= sprintf(', %d items failed', $totalFailed);
            }
            
            $warnings = [];
            if (!empty($validationResult['invalidRows'])) {
                foreach ($validationResult['invalidRows'] as $invalidRow) {
                    $warnings[] = sprintf(
                        'Row %d: %s',
                        $invalidRow['row'],
                        implode('; ', $invalidRow['errors'])
                    );
                }
            }
            
            return ExcelProcessingResultDTO::success(
                message: $message,
                processed: $results['processed'],
                statistics: [
                    'total' => $parsedData['rowCount'],
                    'processed' => $totalProcessed,
                    'failed' => $totalFailed,
                    'skipped' => $results['skipped'],
                ],
                savedFilePath: $savedPath,
                warnings: array_merge($warnings, $results['warnings'])
            );
            
        } catch (\Exception $e) {
            debugLog('Excel processing error: ' . $e->getMessage(), 'error');
            
            return ExcelProcessingResultDTO::failure(
                'Excel processing failed: ' . $e->getMessage(),
                [$e->getMessage()]
            );
        }
    }
    
    /**
     * Save the Excel file to storage
     *
     * @param string $tempPath
     * @param string $contentType
     * @return string
     * @throws \RuntimeException
     */
    private function saveExcelFile(string $tempPath, string $contentType): string
    {
        $uploadDir = wp_upload_dir();
        $baseDir = $uploadDir['basedir'] . '/gestion_contenido_excel';
        
        // Create directory structure
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $targetDir = "{$baseDir}/{$year}/{$month}/{$day}";
        
        if (!wp_mkdir_p($targetDir)) {
            throw new \RuntimeException('Failed to create directory: ' . $targetDir);
        }
        
        // Generate filename
        $filename = sprintf(
            '%s_%s.xlsx',
            str_replace(' ', '', $contentType),
            date('H-i-s')
        );
        
        $targetPath = "{$targetDir}/{$filename}";
        
        // Move file
        if (!rename($tempPath, $targetPath)) {
            throw new \RuntimeException('Failed to save Excel file');
        }
        
        return $targetPath;
    }
    
    /**
     * Process rows based on content type
     *
     * @param array $rows
     * @param string $contentType
     * @param array $options
     * @return array
     */
    private function processRows(array $rows, string $contentType, array $options): array
    {
        $processed = [];
        $failed = [];
        $warnings = [];
        $skipped = 0;
        
        // Begin transaction
        $repository = $this->getRepository($contentType);
        $repository->beginTransaction();
        
        try {
            foreach ($rows as $index => $row) {
                try {
                    // Add ID for News if not present
                    if ($contentType === 'News' && empty($row['id'])) {
                        $row['id'] = $index + 1;
                    }
                    
                    // Check if already exists
                    if ($this->checkExists($row, $contentType)) {
                        $skipped++;
                        $warnings[] = sprintf(
                            'Item with ID %s already exists',
                            $row['id'] ?? 'unknown'
                        );
                        continue;
                    }
                    
                    // Process based on type
                    $entity = $this->processRow($row, $contentType, $options);
                    
                    if ($entity) {
                        $processed[] = [
                            'id' => $entity->getId(),
                            'title' => $entity->getTitle(),
                            'externalId' => $entity->getExternalId(),
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $failed[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            // Commit transaction
            $repository->commit();
            
        } catch (\Exception $e) {
            // Rollback on error
            $repository->rollback();
            throw $e;
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'warnings' => $warnings,
            'skipped' => $skipped,
        ];
    }
    
    /**
     * Check if entity already exists
     *
     * @param array $row
     * @param string $contentType
     * @return bool
     */
    private function checkExists(array $row, string $contentType): bool
    {
        // For all content types, check by title first if available
        $title = (string)($row['titulo'] ?? '');
        if (!empty($title)) {
            $existsByTitle = match($contentType) {
                'News' => $this->newsRepository->existsByTitle($title),
                'Projects' => $this->projectRepository->existsByTitle($title),
                'Calls' => $this->callRepository->existsByTitle($title),
                default => false,
            };
            
            if ($existsByTitle) {
                return true;
            }
        }

        // Fallback to external ID check if title check didn't find anything
        // (This maintains backward compatibility if needed, though title is preferred now)
        $externalId = (string)($row['id'] ?? '');
        
        if (empty($externalId)) {
            return false;
        }
        
        return match($contentType) {
            'Projects' => $this->projectRepository->existsByExternalId($externalId),
            'Calls' => $this->callRepository->existsByExternalId($externalId),
            default => false,
        };
    }
    
    /**
     * Process a single row
     *
     * @param array $row
     * @param string $contentType
     * @param array $options
     * @return Project|News|Call|null
     */
    private function processRow(array $row, string $contentType, array $options): mixed
    {
        return match($contentType) {
            'Projects' => $this->createProjectUseCase->execute($row, $options),
            'Calls' => $this->createCallUseCase->execute($row, $options),
            'News' => $this->createNewsUseCase->execute($row, $options),
            default => throw new \InvalidArgumentException("Unknown content type: {$contentType}"),
        };
    }
    
    /**
     * Get repository for content type
     *
     * @param string $contentType
     * @return ProjectRepositoryInterface|NewsRepositoryInterface|CallRepositoryInterface
     */
    private function getRepository(string $contentType): mixed
    {
        return match($contentType) {
            'Projects' => $this->projectRepository,
            'Calls' => $this->callRepository,
            'News' => $this->newsRepository,
            default => throw new \InvalidArgumentException("Unknown content type: {$contentType}"),
        };
    }
}
