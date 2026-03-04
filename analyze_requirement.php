<?php
/**
 * AJAX endpoint for analyzing functional requirements
 * Returns SVO analysis in JSON format
 */
session_start();

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input'
    ]);
    exit;
}

// Validate input
if (empty($input['requirement_text'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'requirement_text is required'
    ]);
    exit;
}

$requirementText = $input['requirement_text'];
$requirementCode = $input['requirement_code'] ?? '';

// Load the analyzer
require_once 'classes/SVOAnalyzer.php';

try {
    $analyzer = new SVOAnalyzer();
    
    // Check if API is available
    if (!$analyzer->isAvailable()) {
        echo json_encode([
            'success' => false,
            'error' => 'SVO Analysis is not configured. Please add your Gemini API key in config.php',
            'config_needed' => true
        ]);
        exit;
    }
    
    // Perform analysis
    $result = $analyzer->analyze($requirementText, $requirementCode);
    
    // Add debug mode support
    $debugMode = isset($input['debug']) && $input['debug'] === true;
    if ($debugMode && isset($result['success']) && $result['success']) {
        $result['debug_info'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'requirement_length' => strlen($requirementText),
            'api_model' => GEMINI_MODEL
        ];
    }
    
    // Ensure proper JSON boolean encoding (not using JSON_NUMERIC_CHECK to keep booleans as true/false)
    header('Content-Type: application/json; charset=utf-8');
    
    // Return result with proper JSON encoding
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
