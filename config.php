<?php
/**
 * Configuration file for the PDF Reader application
 * API keys are stored in .env file for security
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        // Create a template .env file if it doesn't exist
        $template = "# PDF Reader Configuration\n" .
                   "# Get your API key from: https://aistudio.google.com/\n" .
                   "GEMINI_API_KEY=your_api_key_here\n" .
                   "GEMINI_MODEL=gemini-1.5-pro\n" .
                   "GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models\n" .
                   "API_TIMEOUT=30\n" .
                   "VERIFY_SSL=false\n" .
                   "ENABLE_SVO_ANALYSIS=true\n";
        
        @file_put_contents($path, $template);
        error_log('NOTICE: .env file created. Please add your GEMINI_API_KEY.');
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') === false) {
            continue; // Skip invalid lines
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV) && !empty($name) && !empty($value)) {
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

// Google Gemini API Configuration
// Get your FREE API key from: https://aistudio.google.com/
// Add your key to .env file
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_MODEL', $_ENV['GEMINI_MODEL'] ?? 'gemini-2.5-flash');

// API Settings - Using v1beta for Gemini API
define('GEMINI_API_URL', $_ENV['GEMINI_API_URL'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/');
define('API_TIMEOUT', (int)($_ENV['API_TIMEOUT'] ?? '30'));

// SSL Settings (for development environments like WAMP)
define('VERIFY_SSL', (bool)($_ENV['VERIFY_SSL'] ?? false));

// Enable/Disable SVO Analysis
define('ENABLE_SVO_ANALYSIS', (bool)($_ENV['ENABLE_SVO_ANALYSIS'] ?? true));

// Validate configuration
if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || GEMINI_API_KEY === 'your_api_key_here') {
    error_log('Warning: GEMINI_API_KEY not configured. SVO analysis features will not be available.');
}
