<?php

declare(strict_types=1);

/**
 * Excel Process Action for Elementor
 *
 * @package RIILSA\Presentation\Actions
 * @since 3.1.0
 */

namespace RIILSA\Presentation\Actions;

use ElementorPro\Modules\Forms\Classes\Action_Base;
use RIILSA\Application\UseCases\ContentManager\ProcessExcelFileUseCase;

/**
 * Elementor form action for processing Excel files
 * 
 * Pattern: Action Pattern (Elementor)
 * This class integrates with Elementor Pro forms to process Excel uploads
 */
class ExcelProcessAction extends Action_Base
{
    /**
     * Process Excel file use case
     *
     * @var ProcessExcelFileUseCase
     */
    private ProcessExcelFileUseCase $processExcelFileUseCase;
    
    /**
     * Content type mapping
     *
     * @var array
     */
    private array $contentTypeMapping = [
        'Proyectos' => 'Projects',
        'Convocatorias' => 'Calls',
        'Noticias' => 'News',
    ];
    
    /**
     * Constructor
     *
     * @param ProcessExcelFileUseCase $processExcelFileUseCase
     */
    public function __construct(ProcessExcelFileUseCase $processExcelFileUseCase)
    {
        $this->processExcelFileUseCase = $processExcelFileUseCase;
    }
    
    /**
     * Get action name
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'riilsa_excel_process';
    }
    
    /**
     * Get action label
     *
     * @return string
     */
    public function get_label(): string
    {
        return esc_html__('RIILSA Excel Process', 'riilsa');
    }
    
    /**
     * Register settings section
     *
     * @param \ElementorPro\Modules\Forms\Widgets\Form $widget
     * @return void
     */
    public function register_settings_section($widget): void
    {
        $widget->start_controls_section(
            'section_riilsa_excel',
            [
                'label' => esc_html__('RIILSA Excel Settings', 'riilsa'),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );
        
        $widget->add_control(
            'riilsa_excel_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => esc_html__('This action will process uploaded Excel files to create posts.', 'riilsa'),
            ]
        );
        
        $widget->end_controls_section();
    }
    
    /**
     * Export handler (required by Action_Base)
     *
     * @param mixed $element
     * @return void
     */
    public function on_export($element): void
    {
        // Nothing to export
    }
    
    /**
     * Run the action
     *
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajaxHandler
     * @return void
     */
    public function run($record, $ajaxHandler): void
    {
        try {
            // Get uploaded file
            $files = $record->get('files');
            
            if (empty($files['field_b2eb58f']['path'][0])) {
                $this->sendErrorResponse($ajaxHandler, 'No file uploaded');
                return;
            }
            
            $filePath = $this->normalizePath($files['field_b2eb58f']['path'][0]);
            
            // Get form name to determine content type
            $formSettings = $record->get('form_settings');
            $formName = $formSettings['form_name'] ?? '';
            
            if (!isset($this->contentTypeMapping[$formName])) {
                $this->sendErrorResponse($ajaxHandler, 'Invalid form type');
                return;
            }
            
            $contentType = $this->contentTypeMapping[$formName];
            
            // Execute use case
            $result = $this->processExcelFileUseCase->execute($filePath, $contentType);
            
            // Send response
            $this->sendResponse($ajaxHandler, $result);
            
        } catch (\Exception $e) {
            debugLog('Excel process action error: ' . $e->getMessage(), 'error');
            $this->sendErrorResponse($ajaxHandler, 'Excel processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Send response to Elementor
     *
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajaxHandler
     * @param \RIILSA\Application\DTOs\ExcelProcessingResultDTO $result
     * @return void
     */
    private function sendResponse($ajaxHandler, \RIILSA\Application\DTOs\ExcelProcessingResultDTO $result): void
    {
        $modalData = $result->toModalData();
        
        $ajaxHandler->add_response_data('riilsa_modal', $modalData);
        
        if ($result->success) {
            if ($result->hasWarnings()) {
                $ajaxHandler->add_response_data('message', $result->message);
            } else {
                $ajaxHandler->add_response_data('message', $result->message);
            }
        } else {
            $ajaxHandler->add_error_message($result->message);
        }
    }
    
    /**
     * Send error response
     *
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajaxHandler
     * @param string $message
     * @return void
     */
    private function sendErrorResponse($ajaxHandler, string $message): void
    {
        $modalData = [
            'title' => 'Processing Error',
            'type' => 'error',
            'errors' => [$message],
            'warnings' => [],
            'successes' => [],
        ];
        
        $ajaxHandler->add_response_data('riilsa_modal', $modalData);
        $ajaxHandler->add_error_message($message);
    }
    
    /**
     * Normalize file path for Windows/Unix compatibility
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $path = str_replace('/', '\\', $path);
        }
        
        return $path;
    }
}
