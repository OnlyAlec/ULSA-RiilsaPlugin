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
     * @var ExcelProcessAction
     */
    private ExcelProcessAction $excelProcessAction;
    
    /**
     * Constructor
     *
     * @param ProcessExcelFileUseCase $processExcelFileUseCase
     * @param ExcelProcessAction $excelProcessAction
     */
    public function __construct(
        ProcessExcelFileUseCase $processExcelFileUseCase,
        ExcelProcessAction $excelProcessAction
    ) {
        $this->processExcelFileUseCase = $processExcelFileUseCase;
        $this->excelProcessAction = $excelProcessAction;
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
        $formActionsRegistrar->register($this->excelProcessAction);
    }
}
