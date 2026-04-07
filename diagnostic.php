<?php
/**
 * Diagnostic Script - Check for common issues in the PDF Reader application
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PDF Reader Application - Diagnostic Report</h1>";
echo "<hr>";

// Check 1: Config file
echo "<h2>1. Configuration Check</h2>";
if (file_exists('config.php')) {
    echo "✅ config.php found<br>";
    require_once 'config.php';
    echo "✅ config.php loaded<br>";
    echo "API Key set: " . (!empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE' ? "✅ Yes" : "❌ No") . "<br>";
} else {
    echo "❌ config.php not found<br>";
}

// Check 2: Classes
echo "<h2>2. Classes Check</h2>";
$classes = [
    'PDFParser.php',
    'SRSParser.php',
    'SVOAnalyzer.php',
    'ComprehensiveTestCaseGenerator.php',
    'UATTestCase.php',
    'UATReportGenerator.php',
    'UATResultTracker.php'
];

foreach ($classes as $class) {
    $path = 'classes/' . $class;
    if (file_exists($path)) {
        $errors = [];
        $output = shell_exec("php -l $path 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $class - Syntax OK<br>";
        } else {
            echo "❌ $class - Syntax Error:<br>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
            $errors[] = $class;
        }
    } else {
        echo "❌ $class - File not found<br>";
    }
}

// Check 3: Session Variables
echo "<h2>3. Session Variables Check</h2>";
echo "Current Session ID: " . session_id() . "<br>";
echo "Session Data: " . (empty($_SESSION) ? "Empty (normal for fresh start)" : count($_SESSION) . " items") . "<br>";

// Check 4: Uploads Directory
echo "<h2>4. Directory Permissions Check</h2>";
$dirs = ['uploads', 'uat_results', 'templates'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "✅ $dir exists (permissions: $perms)<br>";
    } else {
        echo "❌ $dir missing<br>";
        @mkdir($dir, 0755, true);
        echo "   Created $dir<br>";
    }
}

// Check 5: Test Key Functions
echo "<h2>5. Function Availability Check</h2>";

// Check ComprehensiveTestCaseGenerator
if (file_exists('classes/ComprehensiveTestCaseGenerator.php')) {
    require_once 'classes/ComprehensiveTestCaseGenerator.php';
    if (class_exists('ComprehensiveTestCaseGenerator')) {
        echo "✅ ComprehensiveTestCaseGenerator class exists<br>";
        
        $methods = ['generateAllTestCases', 'exportAsJSON', 'exportAsCSV', 'generateTestCasesForRequirement'];
        $reflection = new ReflectionClass('ComprehensiveTestCaseGenerator');
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                echo "  ✅ Method: $method<br>";
            } else {
                echo "  ❌ Missing method: $method<br>";
            }
        }
    } else {
        echo "❌ ComprehensiveTestCaseGenerator class not found<br>";
    }
}

// Check UATResultTracker
if (file_exists('classes/UATResultTracker.php')) {
    require_once 'classes/UATResultTracker.php';
    if (class_exists('UATResultTracker')) {
        echo "✅ UATResultTracker class exists<br>";
        
        $methods = ['saveTestResult', 'loadResults', 'saveScreenshot', 'getUATSummary'];
        $reflection = new ReflectionClass('UATResultTracker');
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                echo "  ✅ Method: $method<br>";
            } else {
                echo "  ❌ Missing method: $method<br>";
            }
        }
    } else {
        echo "❌ UATResultTracker class not found<br>";
    }
}

// Check 6: Template Files
echo "<h2>6. Template Files Check</h2>";
$templates = glob('templates/*.pdf');
if (!empty($templates)) {
    echo "✅ Found " . count($templates) . " template PDF files<br>";
    foreach ($templates as $template) {
        echo "  - " . basename($template) . "<br>";
    }
} else {
    echo "❌ No template PDF files found<br>";
}

// Check 7: PHP Version
echo "<h2>7. PHP Environment Check</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "cURL enabled: " . (extension_loaded('curl') ? "✅ Yes" : "❌ No") . "<br>";
echo "JSON enabled: " . (extension_loaded('json') ? "✅ Yes" : "❌ No") . "<br>";
echo "Session support: " . (extension_loaded('session') ? "✅ Yes" : "❌ No") . "<br>";

echo "<hr>";
echo "<h2>Diagnostic Complete</h2>";
echo "<p><a href='index.php'>Back to Application</a></p>";
?>
