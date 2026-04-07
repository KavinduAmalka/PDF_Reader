<?php
session_start();

// Check if a PDF has been uploaded and parsed
if (!isset($_SESSION['srs_sections']) || !isset($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No requirements found. Please upload and parse a PDF first.');
}

require_once 'classes/ComprehensiveTestCaseGenerator.php';
require_once 'classes/UATResultTracker.php';

$sections = $_SESSION['srs_sections'];
$pdfPath = $_SESSION['uploaded_pdf'];
$pdfHash = md5($pdfPath);
$originalFilename = $_SESSION['original_filename'] ?? 'document.pdf';
$tracker = new UATResultTracker();

// Generate comprehensive test cases (3-4 per requirement, including sub-requirements)
$generator = new ComprehensiveTestCaseGenerator($sections);
$allTestCases = $generator->generateAllTestCases();

// Group test cases by parent requirement (FR-20 contains FR-20.01, FR-20.02, etc)
$groupedTestCases = groupTestCasesByParent($allTestCases);

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_format'])) {
    $exportFormat = $_POST['export_format'];
    
    if ($exportFormat === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="uat_test_cases.json"');
        echo $generator->exportAsJSON();
        exit;
    } elseif ($exportFormat === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="uat_test_cases.csv"');
        echo $generator->exportAsCSV();
        exit;
    }
}

// Get filter parameters
$filterType = $_GET['type'] ?? 'all'; // all, FR, NFR
$filterTestType = $_GET['test_type'] ?? 'all'; // all, Positive, Negative, Edge Case, Performance, Alternate Flow
$searchTerm = $_GET['search'] ?? '';

// Calculate statistics
$totalRequirements = count($groupedTestCases);
$totalTestCases = 0;
foreach ($groupedTestCases as $group) {
    $totalTestCases += countAllTests($group);
}
$frCount = 0;
$nfrCount = 0;
foreach (array_keys($groupedTestCases) as $key) {
    if (strpos($key, 'FR-') === 0) {
        $frCount++;
    } elseif (strpos($key, 'NFR-') === 0) {
        $nfrCount++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAT Test Cases - Enhanced Module</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
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
            flex: 1;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 13px;
            margin-top: 5px;
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
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .controls-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .control-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .control-group label {
            font-weight: 600;
            color: #667eea;
        }
        
        .control-group select,
        .control-group input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .test-case-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .test-case-container:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .requirement-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        
        .requirement-header:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6b3f8f 100%);
        }
        
        .requirement-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .requirement-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .test-cases-grid {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            display: none;
        }
        
        .test-cases-grid.active {
            display: grid;
        }
        
        .test-case-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .test-case-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .test-case-card.positive {
            border-left: 4px solid #4CAF50;
        }
        
        .test-case-card.negative {
            border-left: 4px solid #f44336;
        }
        
        .test-case-card.edge {
            border-left: 4px solid #ff9800;
        }
        
        .test-case-card.performance {
            border-left: 4px solid #2196F3;
        }
        
        .test-case-card.alternate {
            border-left: 4px solid #9C27B0;
        }
        
        .test-type-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .test-type-badge.positive {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .test-type-badge.negative {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .test-type-badge.edge {
            background: #ffe0b2;
            color: #e65100;
        }
        
        .test-type-badge.performance {
            background: #bbdefb;
            color: #1565c0;
        }
        
        .test-type-badge.alternate {
            background: #e1bee7;
            color: #6a1b9a;
        }
        
        .test-case-title {
            font-weight: 600;
            color: #667eea;
            font-size: 14px;
            margin: 10px 0;
        }
        
        .test-case-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .test-case-meta {
            font-size: 12px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .test-case-meta div {
            margin: 5px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .export-dialog {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .export-dialog.active {
            display: flex;
        }
        
        .export-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }
        
        .export-content h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .export-options {
            display: grid;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .export-option {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-option:hover {
            border-color: #667eea;
            background: #f5f7fa;
            transform: translateY(-2px);
        }
        
        .export-option label {
            cursor: pointer;
            display: block;
        }
        
        .dialog-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .info-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #1565c0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>🧪 UAT Test Cases - Enhanced</h1>
                <p>3-4 comprehensive test cases per requirement with smart filtering</p>
            </div>
            <div class="header-buttons">
                <button class="btn btn-primary" onclick="showExportDialog()">📥 Export</button>
                <a href="uat_test_execution.php" class="btn btn-primary">▶ Execute Tests</a>
                <a href="uat_results.php" class="btn btn-secondary">📊 Results</a>
                <a href="sections.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Info Banner -->
        <div class="info-badge">
            ℹ️ This enhanced module generates <strong>3-4 comprehensive test cases</strong> per requirement, including Positive, Negative, Edge Case, and Performance/Alternate Flow tests.
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Requirements</div>
                <div class="number"><?php echo $totalRequirements; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Test Cases</div>
                <div class="number"><?php echo $totalTestCases; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Functional (FR)</div>
                <div class="number"><?php echo $frCount; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Non-Functional (NFR)</div>
                <div class="number"><?php echo $nfrCount; ?></div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="controls">
            <div class="controls-row">
                <div class="control-group">
                    <label>Requirement Type:</label>
                    <select id="filterType" onchange="filterResults()">
                        <option value="all">All</option>
                        <option value="FR">Functional (FR)</option>
                        <option value="NFR">Non-Functional (NFR)</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Test Type:</label>
                    <select id="filterTestType" onchange="filterResults()">
                        <option value="all">All Types</option>
                        <option value="Positive">✓ Positive</option>
                        <option value="Negative">✗ Negative</option>
                        <option value="Edge Case">🔄 Edge Case</option>
                        <option value="Performance">⚡ Performance</option>
                        <option value="Alternate Flow">🔀 Alternate Flow</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Search:</label>
                    <input type="text" id="searchTerm" placeholder="Search test cases..." onkeyup="filterResults()">
                </div>
            </div>
        </div>
        
        <!-- Test Cases -->
        <div id="testCasesContainer">
            <?php
            if (!empty($groupedTestCases)):
                foreach ($groupedTestCases as $reqCode => $subRequirements):
                    $reqType = str_starts_with($reqCode, 'FR-') ? 'FR' : 'NFR';
                    $totalSubTests = countAllTests([$reqCode => $subRequirements]);
            ?>
                <div class="test-case-container" data-req-code="<?php echo $reqCode; ?>" data-req-type="<?php echo $reqType; ?>">
                    <div class="requirement-header" onclick="toggleTestCases(this)">
                        <div class="requirement-title">
                            <span style="transition: transform 0.3s ease; display: inline-block;">▼</span>
                            <span><?php echo htmlspecialchars($reqCode); ?></span>
                            <?php if (count($subRequirements) > 1): ?>
                                <span style="opacity: 0.8; font-size: 13px;"> (<?php echo count($subRequirements); ?> sub-requirements)</span>
                            <?php endif; ?>
                        </div>
                        <span class="requirement-badge"><?php echo $totalSubTests; ?> tests</span>
                    </div>
                    
                    <div class="test-cases-grid active">
                        <!-- Sub-Requirements Container -->
                        <div style="grid-column: 1 / -1; padding: 0;">
                            <?php foreach ($subRequirements as $subReqCode => $testCases): ?>
                                <div style="border-left: 3px solid #667eea; padding: 15px; background: #f9f9f9; margin-bottom: 15px; border-radius: 5px;">
                                    <h4 style="color: #667eea; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><?php echo htmlspecialchars($subReqCode); ?></span>
                                        <span style="font-size: 12px; background: #667eea; color: white; padding: 4px 10px; border-radius: 3px;"><?php echo count($testCases); ?> tests</span>
                                    </h4>
                                    
                                    <!-- Test Cases Grid for this sub-requirement -->
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;">
                                        <?php foreach ($testCases as $tc): ?>
                                            <div class="test-case-card <?php echo strtolower(str_replace(' ', '-', $tc['test_type'])); ?>" 
                                                 data-test-type="<?php echo htmlspecialchars($tc['test_type']); ?>">
                                                <span class="test-type-badge <?php echo strtolower(str_replace(' ', '-', $tc['test_type'])); ?>">
                                                    <?php echo htmlspecialchars($tc['test_type']); ?>
                                                </span>
                                                <div class="test-case-title"><?php echo htmlspecialchars(substr($tc['test_name'], 0, 50)); ?></div>
                                                <div class="test-case-description"><?php echo htmlspecialchars(substr($tc['description'], 0, 80)); ?>...</div>
                                                <div class="test-case-meta">
                                                    <div><strong>ID:</strong> <code><?php echo htmlspecialchars($tc['test_id']); ?></code></div>
                                                    <div><strong>Persona:</strong> <?php echo htmlspecialchars($tc['user_persona']); ?></div>
                                                    <div><strong>Priority:</strong> <?php echo htmlspecialchars($tc['priority']); ?></div>
                                                    <div><strong>Steps:</strong> <?php echo count($tc['test_steps']); ?></div>
                                                    <a href="uat_test_execution.php?req=<?php echo urlencode($subReqCode); ?>&tc=<?php echo urlencode($tc['test_id']); ?>" 
                                                       class="btn btn-primary" style="display: inline-block; margin-top: 10px; width: 100%; text-align: center; text-decoration: none;">
                                                        Execute →
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="empty-state">
                    <p>📭 No test cases generated. Please ensure the PDF contains valid requirements.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Export Dialog -->
    <div class="export-dialog" id="exportDialog">
        <div class="export-content">
            <h2>📥 Export Test Cases</h2>
            <form method="POST">
                <div class="export-options">
                    <label class="export-option">
                        <input type="radio" name="export_format" value="json" checked>
                        <div><strong>JSON Format</strong></div>
                        <small>For TestRail, Jira, API integration</small>
                    </label>
                    <label class="export-option">
                        <input type="radio" name="export_format" value="csv">
                        <div><strong>CSV Format</strong></div>
                        <small>Open in Excel or spreadsheet</small>
                    </label>
                </div>
                <div class="dialog-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeExportDialog()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Download</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleTestCases(element) {
            const icon = element.querySelector('span:first-child');
            const grid = element.nextElementSibling;
            
            if (grid.classList.contains('active')) {
                grid.classList.remove('active');
                icon.textContent = '▶';
            } else {
                grid.classList.add('active');
                icon.textContent = '▼';
            }
        }
        
        function filterResults() {
            const typeFilter = document.getElementById('filterType').value;
            const testTypeFilter = document.getElementById('filterTestType').value;
            const searchTerm = document.getElementById('searchTerm').value.toLowerCase();
            
            const containers = document.querySelectorAll('.test-case-container');
            
            containers.forEach(container => {
                const reqType = container.dataset.reqType;
                let show = true;
                
                // Filter by requirement type
                if (typeFilter !== 'all' && reqType !== typeFilter) {
                    show = false;
                }
                
                // Filter by test type
                if (testTypeFilter !== 'all' && show) {
                    const testTypeCards = container.querySelectorAll('[data-test-type="' + testTypeFilter + '"]');
                    if (testTypeCards.length === 0) {
                        show = false;
                    }
                }
                
                // Filter by search term
                if (searchTerm && show) {
                    const hasMatch = container.textContent.toLowerCase().includes(searchTerm);
                    if (!hasMatch) {
                        show = false;
                    }
                }
                
                container.style.display = show ? 'block' : 'none';
            });
        }
        
        function showExportDialog() {
            document.getElementById('exportDialog').classList.add('active');
        }
        
        function closeExportDialog() {
            document.getElementById('exportDialog').classList.remove('active');
        }
        
        // Close dialog when clicking outside
        document.addEventListener('click', function(event) {
            const dialog = document.getElementById('exportDialog');
            if (event.target === dialog) {
                closeExportDialog();
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Group test cases by parent requirement
 * e.g., FR-20.01, FR-20.02 → grouped under FR-20
 */
function groupTestCasesByParent($testCases) {
    $grouped = [];
    
    foreach ($testCases as $code => $tests) {
        // Check if this is a sub-requirement (e.g., FR-20.01)
        if (preg_match('/^(FR|NFR)-(\d+)\.(\d+)$/', $code, $matches)) {
            // This is a sub-requirement, group under parent
            $parentCode = $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            
            if (!isset($grouped[$parentCode])) {
                $grouped[$parentCode] = [];
            }
            $grouped[$parentCode][$code] = $tests;
        } else {
            // This is a main requirement or NFR without sub-requirements
            if (!isset($grouped[$code])) {
                $grouped[$code] = [];
            }
            $grouped[$code][$code] = $tests;
        }
    }
    
    // Sort the grouped requirements
    uksort($grouped, function($a, $b) {
        preg_match('/(FR|NFR)-(\d+)/', $a, $matchA);
        preg_match('/(FR|NFR)-(\d+)/', $b, $matchB);
        
        if ($matchA[1] !== $matchB[1]) {
            return $matchA[1] === 'FR' ? -1 : 1;
        }
        
        return (int)$matchA[2] - (int)$matchB[2];
    });
    
    return $grouped;
}

/**
 * Count all test cases in a grouped structure
 */
function countAllTests($group) {
    $count = 0;
    foreach ($group as $subRequirements) {
        if (is_array($subRequirements)) {
            foreach ($subRequirements as $tests) {
                $count += is_array($tests) ? count($tests) : 0;
            }
        }
    }
    return $count;
}

