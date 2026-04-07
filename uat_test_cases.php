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

// Get all requirements from sections
$requirements = [];
$testCasesByReq = [];

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
        
        if (empty($reqText)) continue;  // Skip if no content
        
        $req = ['code' => $code, 'text' => $reqText, 'type' => 'FR'];
        $requirements[] = $req;
        
        // Generate test cases for this requirement
        $tcGenerator = new UATTestCase($code, $reqText, 'FR');
        $testCases = $tcGenerator->generateTestCases();
        $testCasesByReq[$code] = $testCases;
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
        
        if (empty($reqText)) continue;  // Skip if no content
        
        $req = ['code' => $code, 'text' => $reqText, 'type' => 'NFR'];
        $requirements[] = $req;
        
        // Generate test cases for this requirement
        $tcGenerator = new UATTestCase($code, $reqText, 'NFR');
        $testCases = $tcGenerator->generateTestCases();
        $testCasesByReq[$code] = $testCases;
    }
}

$selectedReq = $_GET['req'] ?? (count($requirements) > 0 ? $requirements[0]['code'] : null);
$testCases = isset($testCasesByReq[$selectedReq]) ? $testCasesByReq[$selectedReq] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAT Test Cases</title>
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
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .req-list {
            list-style: none;
        }
        
        .req-item {
            margin-bottom: 8px;
        }
        
        .req-item a {
            display: block;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid transparent;
            text-decoration: none;
            color: #333;
            border-radius: 3px;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        
        .req-item a:hover {
            background: #efefef;
            border-left-color: #667eea;
        }
        
        .req-item a.active {
            background: #667eea;
            color: white;
            border-left-color: #667eea;
        }
        
        .req-type {
            font-size: 11px;
            opacity: 0.6;
            display: block;
            margin-top: 2px;
        }
        
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .req-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .req-header h2 {
            margin-bottom: 10px;
        }
        
        .req-text {
            font-size: 14px;
            line-height: 1.6;
            opacity: 0.95;
        }
        
        .test-cases-container {
            display: grid;
            gap: 20px;
        }
        
        .test-case-card {
            border: 1px solid #ddd;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 5px;
            background: #fafafa;
        }
        
        .test-case-card h3 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .test-id {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .test-type-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .test-type-positive {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .test-type-edge {
            background: #fff3e0;
            color: #e65100;
        }
        
        .test-type-negative {
            background: #ffebee;
            color: #c62828;
        }
        
        .test-type-performance {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .test-case-description {
            color: #666;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .test-case-section {
            margin-bottom: 15px;
        }
        
        .test-case-section h4 {
            color: #667eea;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .preconditions, .test-steps, .expected {
            background: white;
            padding: 12px;
            border-radius: 3px;
            line-height: 1.8;
        }
        
        .preconditions ul, .expected ul {
            margin-left: 20px;
        }
        
        .preconditions li, .expected li {
            margin-bottom: 5px;
            color: #555;
        }
        
        .step {
            background: white;
            padding: 10px;
            margin-bottom: 8px;
            border-left: 3px solid #667eea;
            border-radius: 2px;
        }
        
        .step-num {
            font-weight: bold;
            color: #667eea;
        }
        
        .step-action {
            color: #333;
            margin: 5px 0;
        }
        
        .step-expected {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
            font-style: italic;
        }
        
        .priority-high {
            background: #ffebee;
            color: #c62828;
        }
        
        .priority-medium {
            background: #fff3e0;
            color: #e65100;
        }
        
        .priority-low {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .test-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .btn-execute {
            background: #4caf50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }
        
        .btn-execute:hover {
            background: #45a049;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .footer-nav {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        @media (max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: relative;
                top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 UAT Test Cases</h1>
        <p style="opacity: 0.9; margin-bottom: 10px;">Auto-generated test cases from SRS requirements</p>
        <div class="header-buttons">
            <a href="sections.php" class="btn btn-secondary">← Back to Sections</a>
            <a href="uat_results.php" class="btn btn-secondary">📊 View Results</a>
            <a href="uat_reports.php" class="btn btn-secondary">📄 Generate Reports</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <h3>Requirements</h3>
            <ul class="req-list">
                <?php foreach ($requirements as $req) : ?>
                    <li class="req-item">
                        <a href="?req=<?php echo urlencode($req['code']); ?>" 
                           class="<?php echo ($selectedReq === $req['code']) ? 'active' : ''; ?>">
                            <strong><?php echo htmlspecialchars($req['code']); ?></strong>
                            <span class="req-type"><?php echo htmlspecialchars($req['type']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="main-content">
            <?php if ($selectedReq && isset($sections['functional_requirements']['subsections'])) : ?>
                <?php
                $selectedReqData = null;
                
                // Find requirement in FR
                if (isset($sections['subsections']['functional'][$selectedReq])) {
                    $selectedReqData = [
                        'code' => $selectedReq,
                        'text' => $sections['subsections']['functional'][$selectedReq],
                        'type' => 'FR'
                    ];
                }
                
                // Find requirement in NFR if not found
                if (!$selectedReqData && isset($sections['subsections']['non_functional'][$selectedReq])) {
                    $selectedReqData = [
                        'code' => $selectedReq,
                        'text' => $sections['subsections']['non_functional'][$selectedReq],
                        'type' => 'NFR'
                    ];
                }
                
                if ($selectedReqData) :
                ?>
                    <div class="req-header">
                        <h2><?php echo htmlspecialchars($selectedReq); ?> <span style="font-size: 14px; opacity: 0.8;">(<?php echo htmlspecialchars($selectedReqData['type']); ?>)</span></h2>
                        <div class="req-text"><?php echo htmlspecialchars($selectedReqData['text']); ?></div>
                    </div>
                    
                    <?php if (!empty($testCases)) : ?>
                        <div class="test-cases-container">
                            <h3 style="color: #667eea; margin-bottom: 20px;">
                                <span style="background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                                    <?php echo count($testCases); ?> Test Cases
                                </span>
                            </h3>
                            
                            <?php foreach ($testCases as $tc) : ?>
                                <div class="test-case-card">
                                    <h3>
                                        <span class="test-id"><?php echo htmlspecialchars($tc['test_id']); ?></span>
                                        <span class="test-type-badge test-type-<?php echo strtolower(str_replace(' ', '-', $tc['test_type'])); ?>">
                                            <?php echo htmlspecialchars($tc['test_type']); ?>
                                        </span>
                                        <span class="priority-<?php echo strtolower($tc['priority']); ?>" style="padding: 5px 10px; border-radius: 3px; font-size: 11px;">
                                            <?php echo htmlspecialchars($tc['priority']); ?>
                                        </span>
                                    </h3>
                                    
                                    <div class="test-case-description">
                                        <?php echo htmlspecialchars($tc['title']); ?>
                                    </div>
                                    
                                    <div class="test-case-section">
                                        <h4>Description</h4>
                                        <div class="preconditions">
                                            <?php echo htmlspecialchars($tc['description']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="test-case-section">
                                        <h4>Preconditions</h4>
                                        <div class="preconditions">
                                            <ul>
                                                <?php foreach ($tc['preconditions'] as $pc) : ?>
                                                    <li><?php echo htmlspecialchars($pc); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="test-case-section">
                                        <h4>Test Steps</h4>
                                        <div class="test-steps">
                                            <?php foreach ($tc['test_steps'] as $step) : ?>
                                                <div class="step">
                                                    <div class="step-num">Step <?php echo $step['step']; ?>:</div>
                                                    <div class="step-action"><strong>Action:</strong> <?php echo htmlspecialchars($step['action']); ?></div>
                                                    <div class="step-expected"><strong>Expected:</strong> <?php echo htmlspecialchars($step['expectedResult']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="test-case-section">
                                        <h4>Expected Result</h4>
                                        <div class="expected">
                                            <?php echo htmlspecialchars($tc['expected_result']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="test-actions">
                                        <a href="uat_test_execution.php?req=<?php echo urlencode($selectedReq); ?>&tc=<?php echo urlencode($tc['test_id']); ?>" 
                                           class="btn-execute">▶ Execute Test</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>No test cases generated yet.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="empty-state">
                    <p>No requirements found. Please upload and parse a PDF first.</p>
                </div>
            <?php endif; ?>
            
            <div class="footer-nav">
                <a href="sections.php" class="btn btn-secondary">← Back to Sections</a>
                <a href="uat_test_execution.php" class="btn btn-primary">Execute Tests →</a>
            </div>
        </div>
    </div>
</body>
</html>
