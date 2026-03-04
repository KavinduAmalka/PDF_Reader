<?php
/**
 * Configuration file for the PDF Reader application
 * API keys are stored in .env file for security
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found. Please copy .env.example to .env and add your API key.');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

// Google Gemini API Configuration
// Get your FREE API key from: https://makersuite.google.com/app/apikey
// Add your key to .env file
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_MODEL', 'gemini-2.5-flash');

// API Settings - Using v1beta for Gemini API
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/');
define('API_TIMEOUT', 30);

// SSL Settings (for development environments like WAMP)
define('VERIFY_SSL', false);

// Enable/Disable SVO Analysis
define('ENABLE_SVO_ANALYSIS', true);
