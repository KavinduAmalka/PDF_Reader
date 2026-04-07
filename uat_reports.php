<?php
session_start();

// Check if a PDF has been uploaded and parsed
if (!isset($_SESSION['srs_sections']) || !isset($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No PDF parsed. Please upload and parse a PDF first.');
}

require_once 'classes/UATTestCase.php';
require_once 'classes/UATResultTracker.php';
require_once 'classes/UATReportGenerator.php';

$sections = $_SESSION['srs_sections'];
$pdfPath = $_SESSION['uploaded_pdf'];
$originalFilename = $_SESSION['original_filename'] ?? 'document.pdf';
$pdfHash = md5($pdfPath);
$tracker = new UATResultTracker();

// Get all requirements
$requirements = [];

if (isset($sections['subsections']['functional']) && is_array($sections['subsections']['functional'])) {
    foreach ($sections['subsections']['functional'] as $code => $reqData) {
        // Extract text from requirement - handle both string and array structures
        $reqText = '';
        if (is_array($reqData)) {
            if (isset($reqData['content']) && is_array($reqData['content'])) {
                // Content is array of sub-items, combine descriptions
                foreach ($reqData['content'] as $item) {
                    if (isset($item['description']) && !empty($item['description'])) {
                        $reqText .= $item['description'] . ' ';
                    }
                }
                $reqText = trim($reqText);
            } elseif (isset($reqData['content'])) {
                $reqText = $reqData['content'];
            } elseif (isset($reqData['title'])) {
                $reqText = $reqData['title'];
            }
        }
        
        if (!empty($reqText)) {
            $requirements[] = ['code' => $code, 'text' => $reqText, 'type' => 'FR'];
        }
    }
}

if (isset($sections['subsections']['non_functional']) && is_array($sections['subsections']['non_functional'])) {
    foreach ($sections['subsections']['non_functional'] as $code => $reqData) {
        // Extract text from requirement - handle both string and array structures
        $reqText = '';
        if (is_array($reqData)) {
            if (isset($reqData['content']) && is_array($reqData['content'])) {
                // Content is array of sub-items, combine descriptions
                foreach ($reqData['content'] as $item) {
                    if (isset($item['description']) && !empty($item['description'])) {
                        $reqText .= $item['description'] . ' ';
                    }
                }
                $reqText = trim($reqText);
            } elseif (isset($reqData['content'])) {
                $reqText = $reqData['content'];
            }
        }
        
        if (!empty($reqText)) {
            $requirements[] = ['code' => $code, 'text' => $reqText, 'type' => 'NFR'];
        }
    }
}

// Handle report generation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? '';
    
    $reportGen = new UATReportGenerator($sections, $sections, $pdfPath, $tracker);
    
    if ($reportType === 'html') {
        // Generate HTML report for preview/PDF
        $html = $reportGen->generateHTMLReport();
        
        // For now, display it
        echo $html;
        exit();
    } elseif ($reportType === 'csv') {
        // Generate CSV export
        $csv = $reportGen->generateCSVData();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="UAT_Report_' . date('Y-m-d_His') . '.csv"');
        echo $csv;
        exit();
    } elseif ($reportType === 'pdf') {
        // For PDF, we'll generate HTML and provide download instructions
        $html = $reportGen->generateHTMLReport();
        
        // Create temporary HTML file
        $tempFile = 'uat_reports/temp_report_' . time() . '.html';
        if (!file_exists('uat_reports')) {
            mkdir('uat_reports', 0755, true);
        }
        
        file_put_contents($tempFile, $html);
        
        // Display message with download instructions
        echo '<html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; padding: 40px; }
            .message { background: #e8f5e9; padding: 20px; border-radius: 5px; }
            .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
        </style></head><body>';
        echo '<div class="message"><strong>✅ Report Generated!</strong><p>The HTML report is ready. You can:</p>';
        echo '<ol><li>Use <strong>Ctrl+P</strong> (or right-click → Print) to save as PDF</li>';
        echo '<li><a class="btn" href="' . $tempFile . '" target="_blank">View Report in Browser</a></li></ol></div>';
        echo '</body></html>';
        exit();
    }
}

// Get summary for display
$summary = $tracker->getUATSummary($pdfHash, $requirements);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAT Reports</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .header-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .container {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .status-info {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }
        
        .status-info h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .status-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 3px;
        }
        
        .status-item-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .status-item-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-top: 8px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .report-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
        }
        
        .report-body {
            padding: 20px;
        }
        
        .report-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .report-features {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 3px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .report-features h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .report-features ul {
            list-style: none;
            padding-left: 0;
        }
        
        .report-features li {
            padding: 4px 0;
            color: #666;
        }
        
        .report-features li:before {
            content: "✓ ";
            color: #4caf50;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .report-form {
            display: flex;
            gap: 10px;
        }
        
        .report-form form {
            display: contents;
        }
        
        .btn-generate {
            background: #4caf50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
        }
        
        .btn-generate:hover {
            background: #45a049;
        }
        
        .warning {
            background: #fff3e0;
            border: 1px solid #ff9800;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .warning-icon {
            color: #ff9800;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .footer-nav {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        @media (max-width: 900px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 UAT Reports</h1>
        <p style="opacity: 0.9; margin-bottom: 10px;">Generate comprehensive UAT test reports</p>
        <div class="header-buttons">
            <a href="uat_test_cases.php" class="btn btn-secondary">← Test Cases</a>
            <a href="uat_test_execution.php" class="btn btn-secondary">▶ Execute Tests</a>
            <a href="uat_results.php" class="btn btn-secondary">📊 Results</a>
            <a href="sections.php" class="btn btn-secondary">← Back to Sections</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Current Status -->
        <div class="status-info">
            <h2>Current UAT Status</h2>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-item-label">Total Tests</div>
                    <div class="status-item-value"><?php echo $summary['total_tests']; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-item-label">Tests Passed</div>
                    <div class="status-item-value" style="color: #4caf50;"><?php echo $summary['tests_passed']; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-item-label">Tests Failed</div>
                    <div class="status-item-value" style="color: #f44336;"><?php echo $summary['tests_failed']; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-item-label">Pass Rate</div>
                    <div class="status-item-value" style="color: #2196f3;"><?php echo $summary['overall_pass_percentage']; ?>%</div>
                </div>
            </div>
        </div>
        
        <?php if ($summary['total_tests'] === 0) : ?>
            <div class="warning">
                <span class="warning-icon">⚠️</span>
                <strong>No test results yet.</strong> Please execute some tests before generating reports.
            </div>
        <?php endif; ?>
        
        <h2>Report Types</h2>
        
        <div class="reports-grid">
            <!-- HTML/PDF Report -->
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon">📋</div>
                    <div class="report-title">Comprehensive Report</div>
                </div>
                <div class="report-body">
                    <div class="report-description">
                        Complete UAT test report with all requirements, test cases, results, and evidence.
                    </div>
                    <div class="report-features">
                        <h4>Includes:</h4>
                        <ul>
                            <li>Executive Summary</li>
                            <li>Test Coverage Matrix</li>
                            <li>Requirement vs Test Mapping</li>
                            <li>Detailed Test Results</li>
                            <li>Evidence References</li>
                            <li>Pass/Fail Statistics</li>
                        </ul>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="pdf">
                        <button type="submit" class="btn-generate">📥 Generate HTML Report</button>
                    </form>
                    <p style="font-size: 12px; color: #999; margin-top: 10px; text-align: center;">
                        💡 Tip: Use browser Print (Ctrl+P) to save as PDF
                    </p>
                </div>
            </div>
            
            <!-- CSV/Excel Report -->
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon">📊</div>
                    <div class="report-title">Excel Export</div>
                </div>
                <div class="report-body">
                    <div class="report-description">
                        Export test data to CSV format for analysis in Excel or other tools.
                    </div>
                    <div class="report-features">
                        <h4>Includes:</h4>
                        <ul>
                            <li>All Test Cases</li>
                            <li>Test Results</li>
                            <li>Execution Dates</li>
                            <li>Tester Notes</li>
                            <li>Pass/Fail Status</li>
                            <li>Coverage %</li>
                        </ul>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="csv">
                        <button type="submit" class="btn-generate">📥 Export to CSV</button>
                    </form>
                    <p style="font-size: 12px; color: #999; margin-top: 10px; text-align: center;">
                        Compatible with Microsoft Excel, Google Sheets
                    </p>
                </div>
            </div>
            
            <!-- Summary Report -->
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon">📈</div>
                    <div class="report-title">Summary Report</div>
                </div>
                <div class="report-body">
                    <div class="report-description">
                        High-level summary with key metrics and coverage dashboard.
                    </div>
                    <div class="report-features">
                        <h4>Includes:</h4>
                        <ul>
                            <li>Overall UAT Status</li>
                            <li>Test Statistics</li>
                            <li>Coverage by Requirement</li>
                            <li>Pass/Fail Breakdown</li>
                            <li>Critical Issues</li>
                            <li>Recommendations</li>
                        </ul>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="html">
                        <button type="submit" class="btn-generate">📥 Generate Summary</button>
                    </form>
                    <p style="font-size: 12px; color: #999; margin-top: 10px; text-align: center;">
                        Perfect for stakeholder reviews
                    </p>
                </div>
            </div>
        </div>
        
        <h2>Report Guidelines</h2>
        
        <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
            <h3 style="color: #667eea; margin-bottom: 15px;">Best Practices for UAT Reporting</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #333; margin-bottom: 10px;">📋 Before Generating Reports</h4>
                    <ul style="list-style: none; padding: 0; color: #666; font-size: 14px; line-height: 1.8;">
                        <li>✓ Execute all planned test cases</li>
                        <li>✓ Document tester names and signatures</li>
                        <li>✓ Attach evidence/screenshots for all tests</li>
                        <li>✓ Resolve or track all failed tests</li>
                        <li>✓ Verify data accuracy</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #333; margin-bottom: 10px;">📊 Report Review Steps</h4>
                    <ul style="list-style: none; padding: 0; color: #666; font-size: 14px; line-height: 1.8;">
                        <li>✓ Check requirement coverage (100%)</li>
                        <li>✓ Verify all test results are recorded</li>
                        <li>✓ Review failed tests and root causes</li>
                        <li>✓ Cross-check with requirement mapping</li>
                        <li>✓ Get stakeholder sign-off</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #333; margin-bottom: 10px;">✍️ Sign-Off Process</h4>
                    <ul style="list-style: none; padding: 0; color: #666; font-size: 14px; line-height: 1.8;">
                        <li>✓ QA Lead approval</li>
                        <li>✓ Business Analyst confirmation</li>
                        <li>✓ Client/Stakeholder acceptance</li>
                        <li>✓ Document sign-off date</li>
                        <li>✓ Archive report for compliance</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer-nav">
            <a href="uat_results.php" style="color: #667eea; text-decoration: none; font-weight: bold;">
                ← Back to Results Dashboard
            </a>
        </div>
    </div>
</body>
</html>
