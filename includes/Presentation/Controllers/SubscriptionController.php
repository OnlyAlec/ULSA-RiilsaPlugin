<?php

declare(strict_types=1);

/**
 * Subscription Controller
 *
 * @package RIILSA\Presentation\Controllers
 * @since 3.1.0
 */

namespace RIILSA\Presentation\Controllers;

use RIILSA\Application\UseCases\Newsletter\ConfirmSubscriptionUseCase;
use RIILSA\Application\DTOs\SubscriptionConfirmationDTO;

/**
 * Controller for subscription confirmation
 * 
 * Pattern: Controller Pattern
 * This class handles subscription confirmation requests
 */
class SubscriptionController
{
    /**
     * Confirm subscription use case
     *
     * @var ConfirmSubscriptionUseCase
     */
    private ConfirmSubscriptionUseCase $confirmSubscriptionUseCase;
    
    /**
     * Constructor
     *
     * @param ConfirmSubscriptionUseCase $confirmSubscriptionUseCase
     */
    public function __construct(ConfirmSubscriptionUseCase $confirmSubscriptionUseCase)
    {
        $this->confirmSubscriptionUseCase = $confirmSubscriptionUseCase;
    }
    
    /**
     * Initialize the controller
     *
     * @return void
     */
    public function init(): void
    {
        add_action('wp', function() {
            if (is_page('confirmacion-boletin')) {
                $this->handleConfirmationPage();
            }
        });
    }
    
    /**
     * Handle confirmation page requests
     *
     * @return void
     */
    private function handleConfirmationPage(): void
    {
        // Check if this is a confirmation request
        if (!isset($_GET['action']) || $_GET['action'] !== 'confirm') {
            $this->renderDefaultConfirmationPage();
            return;
        }
        
        $email = $_GET['email'] ?? '';
        $token = $_GET['token'] ?? '';
        
        if (empty($email) || empty($token)) {
            $this->renderErrorPage('Invalid confirmation link.');
            return;
        }
        
        try {
            // Create DTO
            $dto = SubscriptionConfirmationDTO::fromRequest([
                'email' => $email,
                'token' => $token,
            ]);
            
            // Execute use case
            $result = $this->confirmSubscriptionUseCase->execute($dto);
            
            // Render result page
            if ($result->success) {
                $this->renderSuccessPage($result->message);
            } else {
                $this->renderErrorPage($result->message);
            }
            
        } catch (\Exception $e) {
            debugLog('Confirmation error: ' . $e->getMessage(), 'error');
            $this->renderErrorPage('An error occurred during confirmation. Please try again later.');
        }
        
        // Stop WordPress from rendering the normal page
        remove_filter('the_content', 'wpautop');
        add_filter('the_content', [$this, 'replaceContent']);
    }
    
    /**
     * Render default confirmation page
     *
     * @return void
     */
    private function renderDefaultConfirmationPage(): void
    {
        $this->renderInfoPage(
            __('Newsletter Subscription Confirmation', 'riilsa'),
            __('Please click the confirmation link in your email to complete your subscription.', 'riilsa')
        );
        
        add_filter('the_content', [$this, 'replaceContent']);
    }
    
    /**
     * Render success page
     *
     * @param string $message
     * @return void
     */
    private function renderSuccessPage(string $message): void
    {
        $templatePath = RIILSA_PATH_PAGE_CONFIRM_OK;
        
        if (file_exists($templatePath)) {
            $html = file_get_contents($templatePath);
            $html = str_replace('-- MESSAGE --', esc_html($message), $html);
            $this->pageContent = $html;
        } else {
            $this->renderInfoPage(__('Success!', 'riilsa'), $message);
        }
    }
    
    /**
     * Render error page
     *
     * @param string $message
     * @return void
     */
    private function renderErrorPage(string $message): void
    {
        $templatePath = RIILSA_PATH_PAGE_CONFIRM_FAIL;
        
        if (file_exists($templatePath)) {
            $html = file_get_contents($templatePath);
            $html = str_replace('-- MESSAGE --', esc_html($message), $html);
            $this->pageContent = $html;
        } else {
            $this->renderInfoPage(__('Error', 'riilsa'), $message);
        }
    }
    
    /**
     * Render generic info page
     *
     * @param string $title
     * @param string $message
     * @return void
     */
    private function renderInfoPage(string $title, string $message): void
    {
        $this->pageContent = sprintf(
            '<div class="riilsa-confirmation-page"><h2>%s</h2><p>%s</p></div>',
            esc_html($title),
            esc_html($message)
        );
    }
    
    /**
     * Page content to replace
     *
     * @var string
     */
    private string $pageContent = '';
    
    /**
     * Replace page content
     *
     * @param string $content
     * @return string
     */
    public function replaceContent(string $content): string
    {
        return $this->pageContent ?: $content;
    }
}
