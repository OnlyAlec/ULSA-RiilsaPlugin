<?php

declare(strict_types=1);

/**
 * Excel Processing Result DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

/**
 * Data Transfer Object for Excel processing results
 * 
 * Pattern: DTO Pattern
 * This class transfers processing results between layers
 */
final class ExcelProcessingResultDTO
{
    /**
     * Constructor
     *
     * @param bool $success
     * @param string $message
     * @param array $processed
     * @param array $failed
     * @param array $errors
     * @param array $warnings
     * @param array $statistics
     * @param string|null $savedFilePath
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $processed,
        public readonly array $failed,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly array $statistics,
        public readonly ?string $savedFilePath = null
    ) {
    }
    
    /**
     * Create a success result
     *
     * @param string $message
     * @param array $processed
     * @param array $statistics
     * @param string|null $savedFilePath
     * @param array $warnings
     * @return self
     */
    public static function success(
        string $message,
        array $processed,
        array $statistics,
        ?string $savedFilePath = null,
        array $warnings = []
    ): self {
        return new self(
            success: true,
            message: $message,
            processed: $processed,
            failed: [],
            errors: [],
            warnings: $warnings,
            statistics: $statistics,
            savedFilePath: $savedFilePath
        );
    }
    
    /**
     * Create a failure result
     *
     * @param string $message
     * @param array $errors
     * @param array $failed
     * @param array $processed
     * @return self
     */
    public static function failure(
        string $message,
        array $errors,
        array $failed = [],
        array $processed = []
    ): self {
        return new self(
            success: false,
            message: $message,
            processed: $processed,
            failed: $failed,
            errors: $errors,
            warnings: [],
            statistics: [
                'total' => count($processed) + count($failed),
                'processed' => count($processed),
                'failed' => count($failed)
            ],
            savedFilePath: null
        );
    }
    
    /**
     * Check if there are warnings
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
    
    /**
     * Check if there are errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Get total items count
     *
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->statistics['total'] ?? (count($this->processed) + count($this->failed));
    }
    
    /**
     * Get processed items count
     *
     * @return int
     */
    public function getProcessedCount(): int
    {
        return $this->statistics['processed'] ?? count($this->processed);
    }
    
    /**
     * Get failed items count
     *
     * @return int
     */
    public function getFailedCount(): int
    {
        return $this->statistics['failed'] ?? count($this->failed);
    }
    
    /**
     * Convert to array for JSON serialization
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'processed' => $this->processed,
            'failed' => $this->failed,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'statistics' => $this->statistics,
            'savedFilePath' => $this->savedFilePath,
        ];
    }
    
    /**
     * Convert to modal data for frontend display
     *
     * @return array
     */
    public function toModalData(): array
    {
        $type = 'success';
        if ($this->hasErrors()) {
            $type = 'error';
        } elseif ($this->hasWarnings()) {
            $type = 'warning';
        }
        
        return [
            'title' => $this->message,
            'type' => $type,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'successes' => $this->success ? [
                sprintf(
                    'Processed %d of %d items successfully',
                    $this->getProcessedCount(),
                    $this->getTotalItems()
                )
            ] : [],
            'statistics' => $this->statistics,
        ];
    }
}
