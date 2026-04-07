<?php
/**
 * Error Handler & Fixer for PDF Reader Application
 * Helps identify and report application issues
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'checks' => [],
    'errors' => [],
    'warnings' => [],
    'fixes_applied' => [],
    'recommendations' => []
];

// Check 1: Session state
$response['checks']['session'] = [
    'session_id' => session_id(),
    'has_pdf' => isset($_SESSION['uploaded_pdf']),
    'has_sections' => isset($_SESSION['srs_sections']),
    'pdf_file' => $_SESSION['uploaded_pdf'] ?? 'N/A'
];

if (!isset($_SESSION['uploaded_pdf'])) {
    $response['warnings'][] = 'No PDF uploaded. Please upload a PDF first.';
}

// Check 2: File existence
if (isset($_SESSION['uploaded_pdf'])) {
    if (!file_exists($_SESSION['uploaded_pdf'])) {
        $response['errors'][] = 'Uploaded PDF file not found at: ' . $_SESSION['uploaded_pdf'];
    }
}

// Check 3: Directories exist and are writable
$dirs_to_check = ['uploads', 'uat_results', 'templates'];
foreach ($dirs_to_check as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $response['fixes_applied'][] = "Created missing directory: $dir";
    }
    
    if (!is_writable($dir)) {
        chmod($dir, 0755);
        $response['fixes_applied'][] = "Fixed permissions for directory: $dir";
    }
    
    $response['checks']["directory_$dir"] = [
        'exists' => is_dir($dir),
        'writable' => is_writable($dir),
        'permissions' => substr(sprintf('%o', fileperms($dir)), -4)
    ];
}

// Check 4: Required classes exist
$required_classes = [
    'ComprehensiveTestCaseGenerator',
    'PDFParser',
    'SRSParser',
    'SVOAnalyzer',
    'UATTestCase',
    'UATReportGenerator',
    'UATResultTracker'
];

foreach ($required_classes as $class_name) {
    $class_file = "classes/$class_name.php";
    if (!file_exists($class_file)) {
        $response['errors'][] = "Missing class file: $class_file";
    } else {
        $response['checks']["class_$class_name"] = 'exists';
    }
}

// Check 5: Configuration
if (file_exists('config.php')) {
    require_once 'config.php';
    $response['checks']['config'] = [
        'file_exists' => true,
        'api_key_set' => !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE'
    ];
    
    if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
        $response['warnings'][] = 'Gemini API key not configured. SVO analysis will not work.';
    }
} else {
    $response['errors'][] = 'config.php not found';
}

// Check 6: PHP compatibility
$response['checks']['php_version'] = phpversion();
$response['checks']['extensions'] = [
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'session' => extension_loaded('session'),
    'spl' => extension_loaded('spl')
];

// Check 7: Verify test case generation
if (isset($_SESSION['srs_sections']) && !empty($_SESSION['srs_sections'])) {
    try {
        require_once 'classes/ComprehensiveTestCaseGenerator.php';
        $generator = new ComprehensiveTestCaseGenerator($_SESSION['srs_sections']);
        $test_cases = $generator->generateAllTestCases();
        
        $response['checks']['test_generation'] = [
            'success' => true,
            'total_requirements' => count($test_cases),
            'total_test_cases' => count($test_cases) * 4 // Assuming 4 tests per requirement
        ];
    } catch (Exception $e) {
        $response['errors'][] = "Test generation error: " . $e->getMessage();
    }
}

// Check 8: Look for common issues
if (isset($_GET['analyze_full']) && $_GET['analyze_full'] === 'true') {
    // Parse sections
    if (isset($_SESSION['uploaded_pdf']) && file_exists($_SESSION['uploaded_pdf'])) {
        try {
            require_once 'classes/PDFParser.php';
            $parser = new PDFParser($_SESSION['uploaded_pdf']);
            $text = $parser->extractText();
            
            if (strpos($text, 'ERROR:') === 0) {
                $response['errors'][] = "PDF Extraction Error: " . substr($text, 0, 100);
            } else {
                $response['checks']['pdf_extraction'] = [
                    'success' => true,
                    'text_length' => strlen($text)
                ];
            }
        } catch (Exception $e) {
            $response['errors'][] = "PDF Parser error: " . $e->getMessage();
        }
    }
}

// Final status
$response['status'] = empty($response['errors']) ? 'success' : 'has_errors';
$response['summary'] = [
    'total_checks' => count($response['checks']),
    'errors_found' => count($response['errors']),
    'warnings_found' => count($response['warnings']),
    'fixes_applied' => count($response['fixes_applied'])
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
