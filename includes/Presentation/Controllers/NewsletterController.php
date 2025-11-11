<?php

declare(strict_types=1);

/**
 * Newsletter Controller
 *
 * @package RIILSA\Presentation\Controllers
 * @since 3.1.0
 */

namespace RIILSA\Presentation\Controllers;

use RIILSA\Presentation\Ajax\NewsletterAjaxHandler;

/**
 * Controller for Newsletter functionality
 * 
 * Pattern: Controller Pattern
 * This class handles HTTP requests for newsletter management
 */
class NewsletterController
{
    /**
     * AJAX handler
     *
     * @var NewsletterAjaxHandler
     */
    private NewsletterAjaxHandler $ajaxHandler;
    
    /**
     * Constructor
     *
     * @param NewsletterAjaxHandler $ajaxHandler
     */
    public function __construct(NewsletterAjaxHandler $ajaxHandler)
    {
        $this->ajaxHandler = $ajaxHandler;
    }
    
    /**
     * Initialize the controller
     *
     * @return void
     */
    public function init(): void
    {
        // Only initialize on newsletter management page or during AJAX
        add_action('wp', function() {
            $ajaxActions = [
                'controlEmails',
                'controlDependencies',
                'generateNewsletter',
                'sendNewsletter',
                'historyNewsletter',
                'historyBoletin'
            ];
            
            $isNewsletterPage = is_page('gestion-boletin');
            $isNewsletterAjax = wp_doing_ajax() && 
                               isset($_REQUEST['action']) && 
                               in_array($_REQUEST['action'], $ajaxActions);
            
            if ($isNewsletterPage || $isNewsletterAjax) {
                $this->registerAjaxHandlers();
            }
        });
    }
    
    /**
     * Register AJAX handlers
     *
     * @return void
     */
    private function registerAjaxHandlers(): void
    {
        // Generate newsletter
        add_action('wp_ajax_generateNewsletter', [$this->ajaxHandler, 'handleGenerateNewsletter']);
        
        // Send newsletter
        add_action('wp_ajax_sendNewsletter', [$this->ajaxHandler, 'handleSendNewsletter']);
        
        // Get newsletter history
        add_action('wp_ajax_historyNewsletter', [$this->ajaxHandler, 'handleGetHistory']);
        add_action('wp_ajax_historyBoletin', [$this->ajaxHandler, 'handleGetHistory']);
        
        // Email and dependency management
        add_action('wp_ajax_controlEmails', [$this->ajaxHandler, 'handleControlEmails']);
        add_action('wp_ajax_controlDependencies', [$this->ajaxHandler, 'handleControlDependencies']);
    }
}
