<?php
session_start();

// Check if a PDF has been uploaded and parsed
if (!isset($_SESSION['srs_sections']) || !isset($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No PDF parsed. Please upload and parse a PDF first.');
}

require_once 'classes/ComprehensiveTestCaseGenerator.php';
require_once 'classes/UATResultTracker.php';

$sections = $_SESSION['srs_sections'];
$pdfPath = $_SESSION['uploaded_pdf'];
$pdfHash = md5($pdfPath);
$tracker = new UATResultTracker();

// Generate test cases using comprehensive generator (includes sub-requirements)
$generator = new ComprehensiveTestCaseGenerator($sections);
$allTestCases = $generator->generateAllTestCases();

// Build requirement list with sub-requirements
$parentRequirements = [];
$allRequirements = [];

foreach ($allTestCases as $code => $tests) {
    $reqType = str_starts_with($code, 'FR-') ? 'FR' : 'NFR';
    
    // Check if this is a sub-requirement (e.g., FR-20.01)
    if (preg_match('/^(FR|NFR)-(\d+)\.(\d+)$/', $code, $matches)) {
        // This is a sub-requirement
        $parentCode = $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        
        if (!isset($parentRequirements[$parentCode])) {
            $parentRequirements[$parentCode] = ['type' => $reqType, 'subs' => []];
        }
        
        $parentRequirements[$parentCode]['subs'][$code] = true;
        $allRequirements[$code] = ['type' => $reqType, 'isSubReq' => true];
    } else {
        // This is a main requirement
        if (!isset($parentRequirements[$code])) {
            $parentRequirements[$code] = ['type' => $reqType, 'subs' => []];
        }
        $allRequirements[$code] = ['type' => $reqType, 'isSubReq' => false];
    }
}

// Sort requirements
uksort($parentRequirements, function($a, $b) {
    $matchA = [];
    $matchB = [];
    preg_match('/(FR|NFR)-(\d+)/', $a, $matchA);
    preg_match('/(FR|NFR)-(\d+)/', $b, $matchB);
    
    if (empty($matchA) || empty($matchB)) {
        return 0;
    }
    
    if ($matchA[1] !== $matchB[1]) {
        return $matchA[1] === 'FR' ? -1 : 1;
    }
    
    return (int)$matchA[2] - (int)$matchB[2];
});

// Handle test result submission
$resultSubmitted = false;
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_result'])) {
    $requirementCode = $_POST['requirement_code'] ?? '';
    $testId = $_POST['test_id'] ?? '';
    $status = $_POST['status'] ?? 'Not Tested';
    $notes = $_POST['notes'] ?? '';
    
    $evidenceFile = null;
    
    // Handle screenshot upload
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (in_array($_FILES['evidence']['type'], $validMimes) && $_FILES['evidence']['size'] <= 5 * 1024 * 1024) {
            $evidenceFile = $tracker->saveScreenshot($pdfHash, $requirementCode, $testId, $_FILES['evidence']);
        }
    }
    
    // Save test result
    $result = [
        'status' => $status,
        'notes' => $notes,
        'tester_name' => $_POST['tester_name'] ?? 'Unknown',
        'execution_date' => date('Y-m-d H:i:s'),
        'test_type' => $_POST['test_type'] ?? 'N/A',
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    if ($evidenceFile) {
        $result['evidence_file'] = $evidenceFile;
    }
    
    $tracker->saveTestResult($pdfHash, $requirementCode, $testId, $result);
    $resultSubmitted = true;
    $successMessage = "✅ Test result saved successfully!";
}

// Get selected requirement from URL (can be main or sub-requirement)
$selectedReq = $_GET['req'] ?? null;
$selectedTestId = $_GET['tc'] ?? null;

// If no requirement selected, select first one
if (!$selectedReq) {
    reset($parentRequirements);
    $selectedReq = key($parentRequirements);
}

// Get test cases for selected requirement
$testCases = isset($allTestCases[$selectedReq]) ? $allTestCases[$selectedReq] : [];

// Get selected test case
$selectedTestCase = null;
if ($selectedTestId && !empty($testCases)) {
    foreach ($testCases as $tc) {
        if ($tc['test_id'] === $selectedTestId) {
            $selectedTestCase = $tc;
            break;
        }
    }
}

// If no specific test selected, select first one
if (!$selectedTestCase && !empty($testCases)) {
    $selectedTestCase = $testCases[0];
    $selectedTestId = $selectedTestCase['test_id'];
}

// Get existing result for this test
$existingResult = null;
if ($selectedTestCase && $selectedReq) {
    $existingResult = $tracker->getTestResult($pdfHash, $selectedReq, $selectedTestId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAT Test Execution - Enhanced</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-buttons {
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
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
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
            grid-template-columns: 300px 1fr;
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            margin-bottom: 5px;
        }
        
        .req-item-main a {
            display: block;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .req-item-main a:hover {
            opacity: 0.9;
        }
        
        .req-item-main a.active {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }
        
        .req-item-sub {
            margin-left: 10px;
            margin-bottom: 3px;
        }
        
        .req-item-sub a {
            display: block;
            padding: 8px 10px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .req-item-sub a:hover {
            background: #e0e0e0;
            border-left-color: #667eea;
        }
        
        .req-item-sub a.active {
            background: #667eea;
            color: white;
            border-left-color: #667eea;
        }
        
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .no-selection {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .test-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .test-btn {
            padding: 12px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .test-btn:hover {
            border-color: #667eea;
            background: #f9f9f9;
        }
        
        .test-btn.positive {
            border-color: #4CAF50;
            color: #4CAF50;
        }
        
        .test-btn.positive.active {
            background: #4CAF50;
            color: white;
        }
        
        .test-btn.negative {
            border-color: #f44336;
            color: #f44336;
        }
        
        .test-btn.negative.active {
            background: #f44336;
            color: white;
        }
        
        .test-btn.edge {
            border-color: #ff9800;
            color: #ff9800;
        }
        
        .test-btn.edge.active {
            background: #ff9800;
            color: white;
        }
        
        .test-btn.alternate {
            border-color: #9C27B0;
            color: #9C27B0;
        }
        
        .test-btn.alternate.active {
            background: #9C27B0;
            color: white;
        }
        
        .test-case-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .test-case-header h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .test-case-section {
            margin-bottom: 25px;
        }
        
        .test-case-section h3 {
            color: #667eea;
            font-size: 15px;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ddd;
        }
        
        .test-steps {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .step {
            background: white;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #667eea;
            border-radius: 3px;
        }
        
        .step-number {
            font-weight: 600;
            color: #667eea;
        }
        
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .success-message {
            background: #c8e6c9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>▶ Test Execution</h1>
            <div class="header-buttons">
                <a href="uat_test_cases_enhanced.php" class="btn btn-secondary">← Test Cases</a>
                <a href="uat_results.php" class="btn btn-secondary">📊 Results</a>
                <a href="sections.php" class="btn btn-secondary">Back to Sections</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Sidebar with Requirements -->
        <aside class="sidebar">
            <h3>📋 Requirements</h3>
            <ul class="req-list">
                <?php foreach ($parentRequirements as $mainCode => $mainReq): ?>
                    <li class="req-item req-item-main">
                        <a href="?req=<?php echo urlencode($mainCode); ?>" 
                           class="<?php echo ($selectedReq === $mainCode && !isset($allRequirements[$selectedReq]['isSubReq']) || !$allRequirements[$selectedReq]['isSubReq']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($mainCode); ?>
                        </a>
                        
                        <!-- Sub-requirements -->
                        <?php if (!empty($mainReq['subs'])): ?>
                            <?php foreach (array_keys($mainReq['subs']) as $subCode): ?>
                                <div class="req-item-sub">
                                    <a href="?req=<?php echo urlencode($subCode); ?>" 
                                       class="<?php echo ($selectedReq === $subCode) ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($subCode); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php if ($resultSubmitted): ?>
                <div class="success-message"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if (empty($testCases)): ?>
                <div class="no-selection">
                    <p>📭 Select a requirement to view its test cases</p>
                </div>
            <?php else: ?>
                <!-- Test Type Selector -->
                <div class="test-selector">
                    <?php foreach ($testCases as $tc): ?>
                        <button class="test-btn <?php echo strtolower(str_replace(' ', '-', $tc['test_type'])); ?> <?php echo ($selectedTestId === $tc['test_id']) ? 'active' : ''; ?>" 
                                onclick="location.href='?req=<?php echo urlencode($selectedReq); ?>&tc=<?php echo urlencode($tc['test_id']); ?>'">
                            <?php 
                                $typeSymbol = [
                                    'Positive' => '✓',
                                    'Negative' => '✗',
                                    'Edge Case' => '🔄',
                                    'Alternate Flow' => '🔀'
                                ];
                                echo ($typeSymbol[$tc['test_type']] ?? '') . ' ' . htmlspecialchars($tc['test_type']);
                            ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($selectedTestCase): ?>
                    <!-- Test Case Details -->
                    <div class="test-case-header">
                        <h2>Test: <?php echo htmlspecialchars($selectedTestCase['test_name']); ?></h2>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($selectedTestCase['test_id']); ?> | 
                           <strong>Requirement:</strong> <?php echo htmlspecialchars($selectedReq); ?> | 
                           <strong>Priority:</strong> <?php echo htmlspecialchars($selectedTestCase['priority']); ?></p>
                    </div>
                    
                    <!-- Description -->
                    <div class="test-case-section">
                        <h3>Description</h3>
                        <p><?php echo htmlspecialchars($selectedTestCase['description']); ?></p>
                    </div>
                    
                    <!-- Pre-conditions -->
                    <?php if (!empty($selectedTestCase['pre_conditions'])): ?>
                        <div class="test-case-section">
                            <h3>Pre-Conditions</h3>
                            <ul style="margin-left: 20px;">
                                <?php foreach ($selectedTestCase['pre_conditions'] as $precond): ?>
                                    <li style="margin-bottom: 5px;"><?php echo htmlspecialchars($precond); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Test Steps -->
                    <div class="test-case-section">
                        <h3>Test Steps</h3>
                        <div class="test-steps">
                            <?php if (!empty($selectedTestCase['test_steps'])): ?>
                                <?php foreach ($selectedTestCase['test_steps'] as $idx => $step): ?>
                                    <div class="step">
                                        <span class="step-number">Step <?php echo $idx + 1; ?>:</span>
                                        <div style="margin-top: 5px;">
                                            <strong>Action:</strong> <?php echo htmlspecialchars($step['action'] ?? $step); ?><br>
                                            <?php if (isset($step['expected']) && !empty($step['expected'])): ?>
                                                <strong>Expected:</strong> <?php echo htmlspecialchars($step['expected']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Expected Result -->
                    <?php if (!empty($selectedTestCase['expected_result'])): ?>
                        <div class="test-case-section">
                            <h3>Expected Result</h3>
                            <p><?php echo htmlspecialchars($selectedTestCase['expected_result']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Test Execution Form -->
                    <form method="POST" class="form-section" enctype="multipart/form-data">
                        <h3 style="margin-top: 0;">Execute Test</h3>
                        
                        <input type="hidden" name="requirement_code" value="<?php echo htmlspecialchars($selectedReq); ?>">
                        <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($selectedTestId); ?>">
                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($selectedTestCase['test_name']); ?>">
                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($selectedTestCase['description']); ?>">
                        <input type="hidden" name="test_type" value="<?php echo htmlspecialchars($selectedTestCase['test_type']); ?>">
                        
                        <div class="form-group">
                            <label>Tester Name:</label>
                            <input type="text" name="tester_name" placeholder="Your name" value="<?php echo htmlspecialchars($existingResult['tester_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Test Result:</label>
                            <select name="status" required>
                                <option value="Not Tested" <?php echo (($existingResult['status'] ?? '') === 'Not Tested') ? 'selected' : ''; ?>>Not Tested</option>
                                <option value="✓ Pass" <?php echo (($existingResult['status'] ?? '') === '✓ Pass') ? 'selected' : ''; ?>>✓ Pass</option>
                                <option value="✗ Fail" <?php echo (($existingResult['status'] ?? '') === '✗ Fail') ? 'selected' : ''; ?>>✗ Fail</option>
                                <option value="⊗ Blocked" <?php echo (($existingResult['status'] ?? '') === '⊗ Blocked') ? 'selected' : ''; ?>>⊗ Blocked</option>
                                <option value="⚠ Warning" <?php echo (($existingResult['status'] ?? '') === '⚠ Warning') ? 'selected' : ''; ?>>⚠ Warning</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes / Comments:</label>
                            <textarea name="notes" placeholder="Add your observations, issues, or screenshots details..."><?php echo htmlspecialchars($existingResult['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Evidence (Screenshot/PDF):</label>
                            <input type="file" name="evidence" accept="image/jpeg,image/png,image/gif,application/pdf">
                            <small style="color: #666; margin-top: 5px; display: block;">Max 5MB. Supported: JPG, PNG, GIF, PDF</small>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="submit_result" class="btn btn-primary">💾 Save Result</button>
                            <a href="uat_results.php" class="btn btn-secondary">View Results</a>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
