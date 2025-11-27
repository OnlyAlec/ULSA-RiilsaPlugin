<?php

declare(strict_types=1);

/**
 * Content Manager Controller
 *
 * @package RIILSA\Presentation\Controllers
 * @since 3.1.0
 */

namespace RIILSA\Presentation\Controllers;

use RIILSA\Application\UseCases\ContentManager\ProcessExcelFileUseCase;
use RIILSA\Presentation\Actions\ExcelProcessAction;

/**
 * Controller for Content Manager functionality
 * 
 * Pattern: Controller Pattern
 * This class handles HTTP requests for content management
 */
class ContentManagerController
{
    /**
     * Process Excel file use case
     *
     * @var ProcessExcelFileUseCase
     */
    private ProcessExcelFileUseCase $processExcelFileUseCase;
    
    /**
     * Excel process action
     *
     * @var ExcelProcessAction|null
     */
    private ?ExcelProcessAction $excelProcessAction = null;
    
    /**
     * Constructor
     *
     * @param ProcessExcelFileUseCase $processExcelFileUseCase
     */
    public function __construct(
        ProcessExcelFileUseCase $processExcelFileUseCase
    ) {
        $this->processExcelFileUseCase = $processExcelFileUseCase;
    }
    
    /**
     * Initialize the controller
     *
     * @return void
     */
    public function init(): void
    {
        // Register Elementor form action
        add_action('elementor_pro/forms/actions/register', [$this, 'registerExcelAction'], 20);
    }
    
    /**
     * Register Excel processing action with Elementor
     *
     * @param \ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $formActionsRegistrar
     * @return void
     */
    public function registerExcelAction($formActionsRegistrar): void
    {
        // Check if Elementor Pro class exists to avoid fatal errors
        if (!class_exists('\ElementorPro\Modules\Forms\Classes\Action_Base')) {
            error_log('RIILSA WARNING: Elementor Pro Action_Base class not found. Cannot register Excel action.');
            return;
        }

        // Lazy instantiate the action to prevent loading it before Elementor Pro is ready
        if ($this->excelProcessAction === null) {
            $this->excelProcessAction = new ExcelProcessAction($this->processExcelFileUseCase);
        }

        $formActionsRegistrar->register($this->excelProcessAction);
    }
}
