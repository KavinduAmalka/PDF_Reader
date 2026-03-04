<?php
/**
 * Configuration file for the PDF Reader application
 * Add your API keys and settings here
 */

// Google Gemini API Configuration
// Get your FREE API key from: https://makersuite.google.com/app/apikey
define('GEMINI_API_KEY', 'AIzaSyBRuVbHNDN4d6IBDFf9leUbkwKJ1yAyMyg');
define('GEMINI_MODEL', 'gemini-2.5-flash'); // Latest flash model

// API Settings - Using v1beta for Gemini API
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/');
define('API_TIMEOUT', 30); // seconds

// SSL Settings (for development environments like WAMP)
// Set to false if you get SSL certificate errors
// For production, keep this true and configure proper SSL certificates
define('VERIFY_SSL', false); // Set to false for local development

// Enable/Disable SVO Analysis
define('ENABLE_SVO_ANALYSIS', true);
