<?php

declare(strict_types=1);

/**
 * MJML Template Service Implementation
 *
 * @package RIILSA\Infrastructure\Services
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Services;

/**
 * MJML template service
 * 
 * Pattern: Service Pattern
 * This class provides MJML template compilation capabilities
 */
class MJMLTemplateService
{
    /**
     * MJML templates path
     *
     * @var string
     */
    private string $templatesPath;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->templatesPath = RIILSA_PLUGIN_DIR . 'assets/templates/Newsletter/';
    }
    
    /**
     * Compile MJML to HTML
     *
     * @param string $mjmlContent
     * @return string
     * @throws \RuntimeException
     */
    public function compile(string $mjmlContent): string
    {
        $mjmlPath = $this->getMjmlExecutable();
        
        if (!$mjmlPath) {
            debugLog('MJML executable not found, returning content as-is', 'warning');
            return $mjmlContent;
        }
        
        // Create temporary files
        $tempInput = tempnam(sys_get_temp_dir(), 'mjml_');
        $tempOutput = $tempInput . '.html';
        
        file_put_contents($tempInput, $mjmlContent);
        
        // Execute MJML
        $command = sprintf(
            '%s %s -o %s 2>&1',
            escapeshellcmd($mjmlPath),
            escapeshellarg($tempInput),
            escapeshellarg($tempOutput)
        );
        
        exec($command, $output, $returnCode);
        
        // Clean up input file
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
     * Load MJML template file
     *
     * @param string $templateName
     * @return string
     * @throws \RuntimeException
     */
    public function loadTemplate(string $templateName): string
    {
        $filePath = $this->templatesPath . $templateName . '.mjml';
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException('MJML template not found: ' . $templateName);
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \RuntimeException('Failed to read MJML template: ' . $templateName);
        }
        
        return $content;
    }
    
    /**
     * Compile template file
     *
     * @param string $templateName
     * @param array $variables Variables to replace in template
     * @return string
     * @throws \RuntimeException
     */
    public function compileTemplate(string $templateName, array $variables = []): string
    {
        $mjml = $this->loadTemplate($templateName);
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $mjml = str_replace("{{$key}}", $value, $mjml);
        }
        
        return $this->compile($mjml);
    }
    
    /**
     * Check if MJML is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->getMjmlExecutable() !== null;
    }
    
    /**
     * Get MJML executable path
     *
     * @return string|null
     */
    private function getMjmlExecutable(): ?string
    {
        // Check common locations
        $paths = [
            '/usr/local/bin/mjml',
            '/usr/bin/mjml',
            'C:\\Program Files\\nodejs\\mjml.cmd', // Windows
            'C:\\Program Files (x86)\\nodejs\\mjml.cmd', // Windows 32-bit
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Try to find via which/where command
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $result = trim(shell_exec('where mjml 2>nul') ?: '');
        } else {
            $result = trim(shell_exec('which mjml 2>/dev/null') ?: '');
        }
        
        if (!empty($result) && file_exists($result)) {
            return $result;
        }
        
        return null;
    }
}
