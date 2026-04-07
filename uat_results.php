<?php
session_start();

// Check if a PDF has been uploaded and parsed
if (!isset($_SESSION['srs_sections']) || !isset($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No PDF parsed. Please upload and parse a PDF first.');
}

require_once 'classes/UATTestCase.php';
require_once 'classes/UATResultTracker.php';

$sections = $_SESSION['srs_sections'];
$pdfPath = $_SESSION['uploaded_pdf'];
$pdfHash = md5($pdfPath);
$tracker = new UATResultTracker();

// Get all requirements
$requirements = [];

// Get functional requirements
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

// Get non-functional requirements
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

// Get UAT summary
$summary = $tracker->getUATSummary($pdfHash, $requirements);

// Determine overall status
$overallStatus = '⭕ NOT STARTED';
$statusColor = '#999';

if ($summary['total_tests'] > 0) {
    if ($summary['tests_failed'] > 0) {
        $overallStatus = '❌ FAILED';
        $statusColor = '#f44336';
    } elseif ($summary['tests_blocked'] > 0) {
        $overallStatus = '⏸️ BLOCKED';
        $statusColor = '#ff9800';
    } elseif ($summary['tests_passed'] === $summary['total_tests']) {
        $overallStatus = '✅ PASSED';
        $statusColor = '#4caf50';
    } else {
        $overallStatus = '🔄 IN PROGRESS';
        $statusColor = '#2196f3';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAT Results & Coverage</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
            text-align: center;
        }
        
        .summary-card h3 {
            color: #999;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: normal;
        }
        
        .summary-card .value {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
        }
        
        .summary-card.status-passed {
            border-left-color: #4caf50;
        }
        
        .summary-card.status-passed .value {
            color: #4caf50;
        }
        
        .summary-card.status-failed {
            border-left-color: #f44336;
        }
        
        .summary-card.status-failed .value {
            color: #f44336;
        }
        
        .summary-card.status-blocked {
            border-left-color: #ff9800;
        }
        
        .summary-card.status-blocked .value {
            color: #ff9800;
        }
        
        .overall-status {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .overall-status .status-label {
            color: #999;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .overall-status .status-value {
            font-size: 36px;
            font-weight: bold;
            padding: 20px;
            border-radius: 10px;
            background: #f9f9f9;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 30px;
            overflow: hidden;
            margin-top: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 13px;
            font-weight: bold;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-passed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-failed {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-blocked {
            background: #fff3e0;
            color: #e65100;
        }
        
        .status-not-tested {
            background: #f0f0f0;
            color: #666;
        }
        
        .status-in-progress {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .progress-container {
            margin: 15px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .mini-progress {
            background: #e0e0e0;
            border-radius: 5px;
            height: 8px;
            overflow: hidden;
        }
        
        .mini-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .requirement-row {
            font-size: 14px;
        }
        
        .requirement-code {
            font-weight: bold;
            color: #667eea;
        }
        
        .requirement-type {
            font-size: 12px;
            color: #999;
        }
        
        .test-counts {
            text-align: center;
            font-size: 13px;
        }
        
        .test-count-item {
            display: inline-block;
            margin: 0 10px;
        }
        
        .test-count-value {
            font-weight: bold;
            color: #333;
        }
        
        .percentage {
            font-weight: bold;
            color: #267cb7;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state-text {
            color: #999;
            font-size: 16px;
        }
        
        @media (max-width: 900px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 UAT Results & Coverage</h1>
        <p style="opacity: 0.9; margin-bottom: 10px;">Test execution summary and requirement coverage tracking</p>
        <div class="header-buttons">
            <a href="uat_test_cases.php" class="btn btn-secondary">← Test Cases</a>
            <a href="uat_test_execution.php" class="btn btn-secondary">▶ Execute Tests</a>
            <a href="uat_reports.php" class="btn btn-secondary">📄 Generate Reports</a>
            <a href="sections.php" class="btn btn-secondary">← Back to Sections</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Overall Status -->
        <div class="overall-status">
            <div class="status-label">Overall UAT Status</div>
            <div class="status-value" style="color: <?php echo $statusColor; ?>;">
                <?php echo $overallStatus; ?>
            </div>
            <?php if ($summary['total_tests'] > 0) : ?>
                <div class="progress-label" style="justify-content: center;">
                    <strong><?php echo $summary['overall_pass_percentage']; ?>% Pass Rate</strong>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $summary['overall_pass_percentage']; ?>%;">
                        <?php echo $summary['overall_pass_percentage']; ?>%
                    </div>
                </div>
            <?php else : ?>
                <p style="color: #999;">No tests executed yet. Start by running test cases.</p>
            <?php endif; ?>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Tests</h3>
                <div class="value"><?php echo $summary['total_tests']; ?></div>
            </div>
            <div class="summary-card status-passed">
                <h3>Tests Passed</h3>
                <div class="value"><?php echo $summary['tests_passed']; ?></div>
            </div>
            <div class="summary-card status-failed">
                <h3>Tests Failed</h3>
                <div class="value"><?php echo $summary['tests_failed']; ?></div>
            </div>
            <div class="summary-card status-blocked">
                <h3>Tests Blocked</h3>
                <div class="value"><?php echo $summary['tests_blocked']; ?></div>
            </div>
            <div class="summary-card">
                <h3>Requirements Tested</h3>
                <div class="value"><?php echo $summary['requirements_tested']; ?>/<?php echo $summary['total_requirements']; ?></div>
            </div>
            <div class="summary-card">
                <h3>Pass Percentage</h3>
                <div class="value"><?php echo $summary['overall_pass_percentage']; ?>%</div>
            </div>
        </div>
        
        <!-- Coverage Table -->
        <h2>Requirement Coverage Details</h2>
        
        <?php if (!empty($requirements)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Requirement Code</th>
                        <th>Type</th>
                        <th style="text-align: center;">Total Tests</th>
                        <th style="text-align: center;">Passed</th>
                        <th style="text-align: center;">Failed</th>
                        <th style="text-align: center;">Blocked</th>
                        <th style="width: 200px;">Progress</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary['requirement_details'] as $code => $coverage) : ?>
                        <?php
                        $total = $coverage['total_tests'];
                        $statusText = $coverage['coverage_status'];
                        $statusClass = 'status-not-tested';
                        
                        if ($statusText === 'Passed') {
                            $statusClass = 'status-passed';
                        } elseif ($statusText === 'Failed') {
                            $statusClass = 'status-failed';
                        } elseif ($statusText === 'Blocked') {
                            $statusClass = 'status-blocked';
                        } elseif ($statusText === 'In Progress') {
                            $statusClass = 'status-in-progress';
                        }
                        
                        // Get requirement type
                        $reqType = 'FR';
                        foreach ($requirements as $req) {
                            if ($req['code'] === $code) {
                                $reqType = $req['type'];
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td class="requirement-code"><?php echo htmlspecialchars($code); ?></td>
                            <td><span class="requirement-type"><?php echo htmlspecialchars($reqType); ?></span></td>
                            <td style="text-align: center;"><strong><?php echo $total; ?></strong></td>
                            <td style="text-align: center; color: #2e7d32;"><strong><?php echo $coverage['passed']; ?></strong></td>
                            <td style="text-align: center; color: #c62828;"><strong><?php echo $coverage['failed']; ?></strong></td>
                            <td style="text-align: center; color: #e65100;"><strong><?php echo $coverage['blocked']; ?></strong></td>
                            <td>
                                <?php if ($total > 0) : ?>
                                    <div class="progress-container">
                                        <div class="mini-progress">
                                            <div class="mini-progress-fill" style="width: <?php echo $coverage['pass_percentage']; ?>%;"></div>
                                        </div>
                                        <div style="font-size: 12px; margin-top: 4px; text-align: right; color: #667eea; font-weight: bold;">
                                            <?php echo $coverage['pass_percentage']; ?>%
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($statusText); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <div class="empty-state-text">No requirements found. Please upload and parse a PDF first.</div>
            </div>
        <?php endif; ?>
        
        <!-- Test Distribution -->
        <h2 style="margin-top: 40px;">Test Distribution</h2>
        
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Requirements</h3>
                <div class="value"><?php echo $summary['total_requirements']; ?></div>
            </div>
            <div class="summary-card status-passed">
                <h3>Requirements Passed</h3>
                <div class="value"><?php echo $summary['requirements_passed']; ?></div>
            </div>
            <div class="summary-card status-failed">
                <h3>Requirements Failed</h3>
                <div class="value"><?php echo $summary['requirements_failed']; ?></div>
            </div>
            <div class="summary-card">
                <h3>Requirements Not Started</h3>
                <div class="value"><?php echo $summary['total_requirements'] - $summary['requirements_tested']; ?></div>
            </div>
        </div>
    </div>
</body>
</html>
