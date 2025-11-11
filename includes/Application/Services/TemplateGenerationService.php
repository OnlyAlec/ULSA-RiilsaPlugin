<?php

declare(strict_types=1);

/**
 * Template Generation Application Service
 *
 * @package RIILSA\Application\Services
 * @since 3.1.0
 */

namespace RIILSA\Application\Services;

use RIILSA\Domain\Entities\Newsletter;
use RIILSA\Domain\Entities\News;

/**
 * Application service for generating newsletter templates
 * 
 * Pattern: Application Service Pattern
 * This service handles the generation of HTML templates for newsletters
 */
class TemplateGenerationService
{
    /**
     * Template paths
     *
     * @var array
     */
    private array $templatePaths = [
        'base' => RIILSA_PATH_TEMPLATE_BASE,
        'grid' => RIILSA_PATH_TEMPLATE_GRID,
        'item' => RIILSA_PATH_TEMPLATE_ITEM,
        'highlight' => RIILSA_PATH_TEMPLATE_HIGHLIGHT,
        'normal' => RIILSA_PATH_TEMPLATE_NORMAL,
        'space' => RIILSA_PATH_TEMPLATE_SPACE,
    ];
    
    /**
     * Generate HTML for newsletter
     *
     * @param Newsletter $newsletter
     * @return string
     * @throws \RuntimeException
     */
    public function generateNewsletterHtml(Newsletter $newsletter): string
    {
        // Load base template
        $baseTemplate = $this->loadTemplate('base');
        
        // Replace header text and ID
        $html = $this->replaceBasePlaceholders($baseTemplate, $newsletter);
        
        // Generate content sections
        $contentHtml = $this->generateContentSections($newsletter->getCategorizedNews());
        
        // Replace content placeholder
        $html = str_replace('-- REPLACE --', $contentHtml, $html);
        
        // Post-process the HTML
        $html = $this->postProcessHtml($html);
        
        return $html;
    }
    
    /**
     * Load template file
     *
     * @param string $templateName
     * @return string
     * @throws \RuntimeException
     */
    private function loadTemplate(string $templateName): string
    {
        if (!isset($this->templatePaths[$templateName])) {
            throw new \RuntimeException("Unknown template: {$templateName}");
        }
        
        $path = $this->templatePaths[$templateName];
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: {$path}");
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read template: {$path}");
        }
        
        return $content;
    }
    
    /**
     * Replace base template placeholders
     *
     * @param string $template
     * @param Newsletter $newsletter
     * @return string
     */
    private function replaceBasePlaceholders(string $template, Newsletter $newsletter): string
    {
        $replacements = [
            '-- HEADER --' => $newsletter->getHeaderText(),
            '-- ID --' => (string)$newsletter->getNumber(),
            '-- DATE --' => $this->formatDate(new \DateTime()),
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
    
    /**
     * Generate content sections
     *
     * @param array $categorizedNews
     * @return string
     */
    private function generateContentSections(array $categorizedNews): string
    {
        $sections = [];
        
        // Process highlights
        if (!empty($categorizedNews['highlight'])) {
            $sections = array_merge($sections, $this->generateHighlights($categorizedNews['highlight']));
        }
        
        // Process normal news
        if (!empty($categorizedNews['normal'])) {
            $sections = array_merge($sections, $this->generateNormalNews($categorizedNews['normal']));
        }
        
        // Process grid news
        if (!empty($categorizedNews['grid'])) {
            $sections = array_merge($sections, $this->generateGridNews($categorizedNews['grid']));
        }
        
        // Interleave with spacers
        return $this->interleaveSections($sections);
    }
    
    /**
     * Generate highlight sections
     *
     * @param array<News> $newsItems
     * @return array
     */
    private function generateHighlights(array $newsItems): array
    {
        $sections = [];
        $template = $this->loadTemplate('highlight');
        
        foreach ($newsItems as $news) {
            $sections[] = $this->replaceNewsPlaceholders($template, $news);
        }
        
        return $sections;
    }
    
    /**
     * Generate normal news sections
     *
     * @param array<News> $newsItems
     * @return array
     */
    private function generateNormalNews(array $newsItems): array
    {
        $sections = [];
        $template = $this->loadTemplate('normal');
        
        // Group in chunks of 3
        $chunks = array_chunk($newsItems, 3);
        
        foreach ($chunks as $chunk) {
            $sectionHtml = '';
            foreach ($chunk as $news) {
                $sectionHtml .= $this->replaceNewsPlaceholders($template, $news);
            }
            $sections[] = $sectionHtml;
        }
        
        return $sections;
    }
    
    /**
     * Generate grid news sections
     *
     * @param array<News> $newsItems
     * @return array
     */
    private function generateGridNews(array $newsItems): array
    {
        $sections = [];
        $gridTemplate = $this->loadTemplate('grid');
        $itemTemplate = $this->loadTemplate('item');
        
        // Group in chunks of 3
        $chunks = array_chunk($newsItems, 3);
        
        foreach ($chunks as $chunk) {
            $gridHtml = $gridTemplate;
            
            // Replace each item placeholder
            foreach ($chunk as $news) {
                $itemHtml = $this->replaceNewsPlaceholders($itemTemplate, $news);
                $gridHtml = $this->replaceFirst('-- ITEM --', $itemHtml, $gridHtml);
            }
            
            // Clear any remaining item placeholders
            $gridHtml = str_replace('-- ITEM --', '', $gridHtml);
            
            $sections[] = $gridHtml;
        }
        
        return $sections;
    }
    
    /**
     * Replace news placeholders in template
     *
     * @param string $template
     * @param News $news
     * @return string
     */
    private function replaceNewsPlaceholders(string $template, News $news): string
    {
        $replacements = [
            '__title__' => $this->escapeHtml($news->getTitle()),
            '__description__' => $this->escapeHtml($news->getExcerpt()),
            '__url__' => esc_url($news->getUrl()),
            '__img__' => esc_url($news->getFeaturedImageUrl() ?? $this->getDefaultImageUrl()),
            '__area__' => $this->escapeHtml($news->getResearchLine() ?? ''),
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
    
    /**
     * Interleave sections with spacers
     *
     * @param array $sections
     * @return string
     */
    private function interleaveSections(array $sections): string
    {
        if (empty($sections)) {
            return '';
        }
        
        $spacer = $this->loadTemplate('space');
        $result = '';
        
        foreach ($sections as $index => $section) {
            $result .= $section;
            
            // Add spacer between sections (not after the last one)
            if ($index < count($sections) - 1) {
                $result .= $spacer;
            }
        }
        
        return $result;
    }
    
    /**
     * Replace first occurrence of string
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    private function replaceFirst(string $search, string $replace, string $subject): string
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
    
    /**
     * Post-process HTML
     *
     * @param string $html
     * @return string
     */
    private function postProcessHtml(string $html): string
    {
        // Clean up extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Ensure proper encoding
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        
        return $html;
    }
    
    /**
     * Escape HTML
     *
     * @param string $text
     * @return string
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Format date
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    private function formatDate(\DateTimeInterface $date): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        $day = $date->format('j');
        $month = $months[(int)$date->format('n')];
        $year = $date->format('Y');
        
        return "{$day} {$month} {$year}";
    }
    
    /**
     * Get default image URL
     *
     * @return string
     */
    private function getDefaultImageUrl(): string
    {
        return pluginUrl('assets/img/email_logo.png');
    }
    
    /**
     * Generate confirmation email HTML
     *
     * @param string $confirmationUrl
     * @param string $email
     * @return string
     * @throws \RuntimeException
     */
    public function generateConfirmationEmail(string $confirmationUrl, string $email): string
    {
        $template = file_get_contents(RIILSA_PATH_TEMPLATE_CONFIRM);
        
        if ($template === false) {
            throw new \RuntimeException('Failed to load confirmation email template');
        }
        
        $replacements = [
            '-- URL --' => esc_url($confirmationUrl),
            '-- EMAIL --' => $this->escapeHtml($email),
            '-- DATE --' => $this->formatDate(new \DateTime()),
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
    
    /**
     * Compile template with MJML (if available)
     *
     * @param string $mjmlTemplate
     * @return string
     * @throws \RuntimeException
     */
    public function compileMjmlTemplate(string $mjmlTemplate): string
    {
        // Check if MJML is available
        $mjmlPath = $this->getMjmlExecutablePath();
        if (!$mjmlPath) {
            // Return template as-is if MJML is not available
            debugLog('MJML not available, using HTML template directly', 'warning');
            return $mjmlTemplate;
        }
        
        // Write template to temporary file
        $tempInput = tempnam(sys_get_temp_dir(), 'mjml_input_');
        $tempOutput = tempnam(sys_get_temp_dir(), 'mjml_output_');
        
        file_put_contents($tempInput, $mjmlTemplate);
        
        // Execute MJML
        $command = sprintf(
            '%s %s -o %s',
            escapeshellcmd($mjmlPath),
            escapeshellarg($tempInput),
            escapeshellarg($tempOutput)
        );
        
        exec($command, $output, $returnCode);
        
        // Clean up temp files
        @unlink($tempInput);
        
        if ($returnCode !== 0) {
            @unlink($tempOutput);
            throw new \RuntimeException('MJML compilation failed: ' . implode("\n", $output));
        }
        
        $html = file_get_contents($tempOutput);
        @unlink($tempOutput);
        
        if ($html === false) {
            throw new \RuntimeException('Failed to read MJML output');
        }
        
        return $html;
    }
    
    /**
     * Get MJML executable path
     *
     * @return string|null
     */
    private function getMjmlExecutablePath(): ?string
    {
        // Check common locations
        $paths = [
            '/usr/local/bin/mjml',
            '/usr/bin/mjml',
            'mjml', // In PATH
        ];
        
        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        
        // Check if available via npm
        $npmPath = trim(shell_exec('which npm 2>/dev/null') ?: '');
        if ($npmPath) {
            $mjmlPath = trim(shell_exec('npm bin 2>/dev/null') ?: '') . '/mjml';
            if (is_executable($mjmlPath)) {
                return $mjmlPath;
            }
        }
        
        return null;
    }
}
