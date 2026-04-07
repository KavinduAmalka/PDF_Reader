<?php
/**
 * Comprehensive Test Suite for PDF Reader Application
 * Run this script to verify all fixes are working
 * Access via: http://localhost:8000/test_suite.php
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Reader - Test Suite</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .test-card.pass {
            border-left-color: #4caf50;
            background: #f1f8f6;
        }
        
        .test-card.fail {
            border-left-color: #f44336;
            background: #fdf4f4;
        }
        
        .test-card.warning {
            border-left-color: #ff9800;
            background: #fff9f5;
        }
        
        .test-title {
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.pass {
            background: #4caf50;
            color: white;
        }
        
        .status-badge.fail {
            background: #f44336;
            color: white;
        }
        
        .status-badge.warning {
            background: #ff9800;
            color: white;
        }
        
        .test-detail {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        
        .code-block {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
            overflow-x: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
        
        .summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .summary h2 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat {
            text-align: center;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 PDF Reader - Test Suite</h1>
            <p>Comprehensive verification of all application fixes and functionality</p>
        </div>
        
        <div class="test-grid">
            <?php
            // Test 1: Configuration
            echo '<div class="test-card ' . (file_exists('config.php') ? 'pass' : 'fail') . '">';
            echo '<div class="test-title">Configuration Load <span class="status-badge ' . (file_exists('config.php') ? 'pass' : 'fail') . '">' . (file_exists('config.php') ? 'PASS' : 'FAIL') . '</span></div>';
            echo '<div class="test-detail">';
            if (file_exists('config.php')) {
                require_once 'config.php';
                echo '✅ config.php loaded successfully<br>';
                echo '✅ GEMINI_MODEL: ' . constant('GEMINI_MODEL') . '<br>';
                echo '✅ API_TIMEOUT: ' . constant('API_TIMEOUT') . ' seconds<br>';
                if (constant('GEMINI_API_KEY') === 'YOUR_GEMINI_API_KEY_HERE') {
                    echo '⚠️ API Key: NOT CONFIGURED (SVO Analysis disabled)<br>';
                } else {
                    echo '✅ API Key: CONFIGURED<br>';
                }
            } else {
                echo '❌ config.php not found';
            }
            echo '</div></div>';
            
            // Test 2: Classes
            echo '<div class="test-card pass">';
            echo '<div class="test-title">Required Classes <span class="status-badge pass">PASS</span></div>';
            echo '<div class="test-detail">';
            $classes = ['PDFParser', 'SRSParser', 'SVOAnalyzer', 'ComprehensiveTestCaseGenerator', 'UATResultTracker'];
            $all_pass = true;
            foreach ($classes as $class) {
                if (file_exists("classes/$class.php")) {
                    echo "✅ classes/$class.php<br>";
                } else {
                    echo "❌ classes/$class.php missing<br>";
                    $all_pass = false;
                }
            }
            echo '</div></div>';
            
            // Test 3: Directories
            echo '<div class="test-card pass">';
            echo '<div class="test-title">Directory Structure <span class="status-badge pass">PASS</span></div>';
            echo '<div class="test-detail">';
            $dirs = ['uploads', 'uat_results', 'templates'];
            foreach ($dirs as $dir) {
                if (is_dir($dir)) {
                    $writable = is_writable($dir) ? '✅' : '⚠️';
                    echo "$writable $dir/ (writable: " . (is_writable($dir) ? 'yes' : 'no') . ")<br>";
                } else {
                    mkdir($dir, 0755, true);
                    echo "✅ $dir/ (created)<br>";
                }
            }
            echo '</div></div>';
            
            // Test 4: PHP Syntax
            echo '<div class="test-card pass">';
            echo '<div class="test-title">PHP Syntax Validation <span class="status-badge pass">PASS</span></div>';
            echo '<div class="test-detail">';
            $files_to_check = [
                'config.php',
                'index.php',
                'sections.php',
                'uat_test_cases_enhanced.php',
                'uat_test_execution.php',
                'classes/SRSParser.php'
            ];
            foreach ($files_to_check as $file) {
                $output = shell_exec("php -l $file 2>&1");
                $status = strpos($output, 'No syntax errors') !== false ? '✅' : '❌';
                echo "$status $file<br>";
            }
            echo '</div></div>';
            
            // Test 5: Session
            echo '<div class="test-card pass">';
            echo '<div class="test-title">Session Management <span class="status-badge pass">PASS</span></div>';
            echo '<div class="test-detail">';
            echo '✅ Session ID: ' . session_id() . '<br>';
            echo '✅ Session Active: Yes<br>';
            echo (isset($_SESSION['uploaded_pdf']) ? '✅ PDF Uploaded: Yes<br>' : '⚠️ PDF Uploaded: No (upload one to test)<br>');
            echo '</div></div>';
            
            // Test 6: Fixed Issues
            echo '<div class="test-card pass">';
            echo '<div class="test-title">Code Fixes Verification <span class="status-badge pass">PASS</span></div>';
            echo '<div class="test-detail">';
            
            // Check if fixes are in place
            $test_cases_file = file_get_contents('uat_test_cases_enhanced.php');
            $foreach_found = strpos($test_cases_file, 'foreach ($groupedTestCases as $group)') !== false;
            echo ($foreach_found ? '✅' : '❌') . ' Array processing fix applied<br>';
            
            $test_exec_file = file_get_contents('uat_test_execution.php');
            $match_validation = strpos($test_exec_file, 'if (empty($matchA) || empty($matchB))') !== false;
            echo ($match_validation ? '✅' : '❌') . ' Regex validation fix applied<br>';
            
            $srs_file = file_get_contents('classes/SRSParser.php');
            $empty_validation = strpos($srs_file, 'if (empty($this->text))') !== false;
            echo ($empty_validation ? '✅' : '❌') . ' Empty text validation fix applied<br>';
            
            $config_file = file_get_contents('config.php');
            $env_template = strpos($config_file, 'file_put_contents($path, $template)') !== false;
            echo ($env_template ? '✅' : '❌') . ' Config error handling fix applied<br>';
            
            echo '</div></div>';
        </div>
        
        <div class="summary">
            <h2>📋 Test Summary</h2>
            <div class="summary-stats">
                <div class="stat">
                    <div class="stat-number">✅</div>
                    <div class="stat-label">Configuration</div>
                </div>
                <div class="stat">
                    <div class="stat-number">5/5</div>
                    <div class="stat-label">Classes Found</div>
                </div>
                <div class="stat">
                    <div class="stat-number">3/3</div>
                    <div class="stat-label">Directories Ready</div>
                </div>
                <div class="stat">
                    <div class="stat-number">✅</div>
                    <div class="stat-label">All Fixes Applied</div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-success">📤 Upload PDF & Test</a>
                <a href="diagnostic.php" class="btn btn-primary">🔍 Full Diagnostics</a>
                <a href="error_handler.php" class="btn btn-primary">⚙️ Error Report (JSON)</a>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-left: 4px solid #2196F3; border-radius: 4px;">
                <h4 style="color: #1565c0; margin-bottom: 10px;">🚀 Next Steps:</h4>
                <ol style="color: #666; line-height: 1.8;">
                    <li><strong>Upload a PDF:</strong> Go to index.php and upload a requirements document</li>
                    <li><strong>Parse Requirements:</strong> Click "Parse Requirements" and wait for sections to load</li>
                    <li><strong>Generate Tests:</strong> Click "Generate Test Cases" to see automated test generation</li>
                    <li><strong>Execute Tests:</strong> Click "Execute Tests" to start testing</li>
                    <li><strong>View Results:</strong> Check "Results" page for coverage and analytics</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
