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
        
        if (empty($reqText)) continue;
        $requirements[] = ['code' => $code, 'text' => $reqText, 'type' => 'FR'];
        $tcGenerator = new UATTestCase($code, $reqText, 'FR');
        $testCasesByReq[$code] = $tcGenerator->generateTestCases();
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
        
        if (empty($reqText)) continue;
        $requirements[] = ['code' => $code, 'text' => $reqText, 'type' => 'NFR'];
        $tcGenerator = new UATTestCase($code, $reqText, 'NFR');
        $testCasesByReq[$code] = $tcGenerator->generateTestCases();
    }
}

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

$selectedReq = $_GET['req'] ?? (count($requirements) > 0 ? $requirements[0]['code'] : null);
$selectedTestId = $_GET['tc'] ?? null;
$testCases = isset($testCasesByReq[$selectedReq]) ? $testCasesByReq[$selectedReq] : [];

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
    <title>UAT Test Execution</title>
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
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
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
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
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
        
        .test-list {
            list-style: none;
        }
        
        .test-list h4 {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .test-item {
            margin-bottom: 8px;
        }
        
        .test-item a {
            display: block;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid transparent;
            text-decoration: none;
            color: #333;
            border-radius: 3px;
            transition: all 0.3s ease;
            font-size: 12px;
        }
        
        .test-item a:hover {
            background: #efefef;
            border-left-color: #667eea;
        }
        
        .test-item a.active {
            background: #667eea;
            color: white;
            border-left-color: #667eea;
        }
        
        .test-item .status-badge {
            font-size: 10px;
            margin-left: auto;
            padding: 2px 6px;
            border-radius: 2px;
            display: inline-block;
        }
        
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }
        
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
        }
        
        .test-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .test-details h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .test-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            background: white;
            padding: 10px;
            border-radius: 3px;
            border-left: 3px solid #667eea;
        }
        
        .meta-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }
        
        .meta-value {
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.2);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .radio-group,
        .checkbox-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .radio-item {
            display: flex;
            align-items: center;
        }
        
        input[type="radio"],
        input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 5px;
            padding: 30px;
            text-align: center;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            background: #f0f0f0;
            border-color: #764ba2;
        }
        
        .upload-area.active {
            background: #efefef;
            border-color: #667eea;
        }
        
        .upload-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .upload-text {
            color: #333;
            font-weight: bold;
        }
        
        .upload-hint {
            color: #999;
            font-size: 13px;
            margin-top: 10px;
        }
        
        #photoInput {
            display: none;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .review-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .review-section h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .review-item {
            padding: 10px;
            background: white;
            border-radius: 3px;
            margin-bottom: 10px;
            border-left: 3px solid #667eea;
        }
        
        .review-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }
        
        .review-value {
            margin-top: 5px;
            color: #333;
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
        <h1>▶ UAT Test Execution</h1>
        <p style="opacity: 0.9; margin-bottom: 10px;">Execute tests and record results</p>
        <div class="header-buttons">
            <a href="uat_test_cases.php" class="btn btn-secondary">← Back to Tests</a>
            <a href="uat_results.php" class="btn btn-secondary">📊 View Results</a>
            <a href="uat_reports.php" class="btn btn-secondary">📄 Reports</a>
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
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($testCases)) : ?>
                <h4 style="color: #667eea; margin-bottom: 10px;">Test Cases</h4>
                <ul class="test-list">
                    <?php foreach ($testCases as $tc) : 
                        $tcResult = $tracker->getTestResult($pdfHash, $selectedReq, $tc['test_id']);
                        $tcStatus = $tcResult['status'] ?? 'Not Tested';
                        $statusColor = ($tcStatus === 'PASS') ? '#4caf50' : (($tcStatus === 'FAIL') ? '#f44336' : '#999');
                    ?>
                        <li class="test-item">
                            <a href="?req=<?php echo urlencode($selectedReq); ?>&tc=<?php echo urlencode($tc['test_id']); ?>" 
                               class="<?php echo ($selectedTestId === $tc['test_id']) ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars(substr($tc['test_id'], -2)); ?></span>
                                <?php if ($tcResult) : ?>
                                    <span class="status-badge" style="background: <?php echo $statusColor; ?>; color: white;">
                                        <?php echo substr($tcStatus, 0, 1); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="main-content">
            <?php if ($resultSubmitted) : ?>
                <div class="alert alert-success">
                    <strong>✅ Success!</strong> <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($selectedTestCase) : ?>
                <div class="test-details">
                    <h2><?php echo htmlspecialchars($selectedTestCase['test_id']); ?> - <?php echo htmlspecialchars($selectedTestCase['title']); ?></h2>
                    <p style="color: #666; margin-top: 10px;"><?php echo htmlspecialchars($selectedTestCase['description']); ?></p>
                    
                    <div class="test-meta">
                        <div class="meta-item">
                            <div class="meta-label">Type</div>
                            <div class="meta-value"><?php echo htmlspecialchars($selectedTestCase['test_type']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Priority</div>
                            <div class="meta-value"><?php echo htmlspecialchars($selectedTestCase['priority']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Requirement</div>
                            <div class="meta-value"><?php echo htmlspecialchars($selectedReq); ?></div>
                        </div>
                        <?php if ($existingResult) : ?>
                            <div class="meta-item">
                                <div class="meta-label">Last Executed</div>
                                <div class="meta-value"><?php echo htmlspecialchars($existingResult['execution_date']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="requirement_code" value="<?php echo htmlspecialchars($selectedReq); ?>">
                    <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($selectedTestId); ?>">
                    <input type="hidden" name="test_type" value="<?php echo htmlspecialchars($selectedTestCase['test_type']); ?>">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($selectedTestCase['title']); ?>">
                    <input type="hidden" name="description" value="<?php echo htmlspecialchars($selectedTestCase['description']); ?>">
                    
                    <div class="form-group">
                        <label for="testerName">Tester Name *</label>
                        <input type="text" id="testerName" name="tester_name" placeholder="Enter your name" 
                               value="<?php echo htmlspecialchars($existingResult['tester_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Result *</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="pass" name="status" value="PASS" 
                                       <?php echo (($existingResult['status'] ?? '') === 'PASS') ? 'checked' : ''; ?> required>
                                <label for="pass" style="margin: 0;">✅ PASS</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="fail" name="status" value="FAIL"
                                       <?php echo (($existingResult['status'] ?? '') === 'FAIL') ? 'checked' : ''; ?>>
                                <label for="fail" style="margin: 0;">❌ FAIL</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="blocked" name="status" value="BLOCKED"
                                       <?php echo (($existingResult['status'] ?? '') === 'BLOCKED') ? 'checked' : ''; ?>>
                                <label for="blocked" style="margin: 0;">⏸️ BLOCKED</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Tester Notes</label>
                        <textarea id="notes" name="notes" placeholder="Add any observations, issues, or additional notes..."><?php echo htmlspecialchars($existingResult['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>📸 Upload Evidence (Screenshot/Proof)</label>
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">Click or drag screenshot here</div>
                            <div class="upload-hint">Max 5MB • JPG, PNG, GIF, or PDF</div>
                            <input type="file" id="photoInput" name="evidence" accept="image/*,.pdf">
                        </div>
                        <?php if (isset($existingResult['evidence_file'])) : ?>
                            <p style="color: #4caf50; margin-top: 10px;">✅ Evidence file: <?php echo htmlspecialchars(basename($existingResult['evidence_file'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit_result" class="btn btn-success">💾 Save Test Result</button>
                        <a href="uat_test_cases.php?req=<?php echo urlencode($selectedReq); ?>" class="btn btn-secondary">← Back</a>
                    </div>
                </form>
                
                <?php if ($selectedTestCase) : ?>
                    <div class="review-section" style="margin-top: 40px;">
                        <h3>Test Case Details</h3>
                        
                        <?php if (!empty($selectedTestCase['preconditions'])) : ?>
                            <div class="review-item">
                                <div class="review-label">Preconditions</div>
                                <div class="review-value">
                                    <ul style="margin-left: 20px;">
                                        <?php foreach ($selectedTestCase['preconditions'] as $pc) : ?>
                                            <li><?php echo htmlspecialchars($pc); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($selectedTestCase['test_steps'])) : ?>
                            <div class="review-item">
                                <div class="review-label">Test Steps</div>
                                <div class="review-value">
                                    <?php foreach ($selectedTestCase['test_steps'] as $step) : ?>
                                        <div style="margin-bottom: 10px; padding: 8px; background: #f9f9f9; border-radius: 3px;">
                                            <strong>Step <?php echo $step['step']; ?>:</strong> <?php echo htmlspecialchars($step['action']); ?>
                                            <br>
                                            <em style="color: #666;">Expected: <?php echo htmlspecialchars($step['expectedResult']); ?></em>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="review-item">
                            <div class="review-label">Expected Result</div>
                            <div class="review-value"><?php echo htmlspecialchars($selectedTestCase['expected_result']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <p style="font-size: 24px; margin-bottom: 10px;">📋</p>
                    <p>Select a requirement to view its test cases</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Upload area drag and drop
        const uploadArea = document.getElementById('uploadArea');
        const photoInput = document.getElementById('photoInput');
        
        if (uploadArea && photoInput) {
            uploadArea.addEventListener('click', () => photoInput.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('active');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('active');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('active');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    photoInput.files = files;
                }
            });
            
            photoInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    const fileName = e.target.files[0].name;
                    uploadArea.innerHTML = `<div class="upload-icon">✅</div><div class="upload-text">File selected: ${fileName}</div>`;
                }
            });
        }
    </script>
</body>
</html>
