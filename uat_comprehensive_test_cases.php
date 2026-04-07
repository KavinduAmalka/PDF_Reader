<?php
session_start();

// Check if a PDF has been uploaded and parsed
if (!isset($_SESSION['srs_sections']) || !isset($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No requirements found. Please upload and parse a PDF first.');
}

require_once 'classes/ComprehensiveTestCaseGenerator.php';

$sections = $_SESSION['srs_sections'];
$pdfPath = $_SESSION['uploaded_pdf'];
$originalFilename = $_SESSION['original_filename'] ?? 'document.pdf';

// Generate comprehensive test cases
$generator = new ComprehensiveTestCaseGenerator($sections);
$allTestCases = $generator->generateAllTestCases();

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_format'])) {
    $exportFormat = $_POST['export_format'];
    
    if ($exportFormat === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="comprehensive_test_cases.json"');
        echo $generator->exportAsJSON();
        exit;
    } elseif ($exportFormat === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="comprehensive_test_cases.csv"');
        echo $generator->exportAsCSV();
        exit;
    }
}

// Get filter/sort parameters
$filterType = $_GET['type'] ?? 'all'; // all, FR, NFR
$filterTestType = $_GET['test_type'] ?? 'all'; // all, Positive, Negative, Edge Case, Performance
$searchTerm = $_GET['search'] ?? '';

// Filter test cases
$filteredCases = filterTestCases($allTestCases, $filterType, $filterTestType, $searchTerm);

// Calculate statistics
$totalRequirements = count($allTestCases);
$totalTestCases = array_sum(array_map('count', $allTestCases));
$frCount = count(array_filter(array_keys($allTestCases), fn($k) => str_starts_with($k, 'FR-')));
$nfrCount = count(array_filter(array_keys($allTestCases), fn($k) => str_starts_with($k, 'NFR-')));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Test Cases - Expert QA</title>
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
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .test-case-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
        
        .requirement-header.collapsed {
            background: linear-gradient(135deg, #999 0%, #666 100%);
        }
        
        .requirement-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .requirement-code {
            background: rgba(255, 255, 255, 0.3);
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .requirement-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: auto;
        }
        
        .toggle-icon {
            font-size: 20px;
            transition: transform 0.3s ease;
        }
        
        .test-cases-grid {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
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
            margin: 10px 0;
            color: #667eea;
            font-size: 14px;
        }
        
        .test-case-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
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
        
        .section-divider {
            border-top: 2px solid #ddd;
            margin: 20px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-top: 10px;
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
            text-align: center;
        }
        
        .export-option:hover {
            border-color: #667eea;
            background: #f5f7fa;
            transform: translateY(-2px);
        }
        
        .export-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .dialog-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📊 Comprehensive Test Cases - Expert QA</h1>
            <p class="header-subtitle">Auto-generated from SRS: <?php echo htmlspecialchars($originalFilename); ?></p>
        </div>
    </div>
    
    <div class="container">
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
                <div class="label">Functional Requirements</div>
                <div class="number"><?php echo $frCount; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Non-Functional Requirements</div>
                <div class="number"><?php echo $nfrCount; ?></div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="controls">
            <div class="controls-row">
                <div class="control-group">
                    <label>Filter by Type:</label>
                    <select id="filterType" onchange="filterResults()">
                        <option value="all">All Requirements</option>
                        <option value="FR">Functional (FR)</option>
                        <option value="NFR">Non-Functional (NFR)</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Test Type:</label>
                    <select id="filterTestType" onchange="filterResults()">
                        <option value="all">All Types</option>
                        <option value="Positive">Positive</option>
                        <option value="Negative">Negative</option>
                        <option value="Edge Case">Edge Case</option>
                        <option value="Performance">Performance</option>
                        <option value="Alternate Flow">Alternate Flow</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Search:</label>
                    <input type="text" id="searchTerm" placeholder="Search test cases..." onkeyup="filterResults()">
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="showExportDialog()">📥 Export</button>
                    <button class="btn btn-secondary" onclick="window.history.back()">← Back</button>
                </div>
            </div>
        </div>
        
        <!-- Test Cases -->
        <div id="testCasesContainer">
            <?php
            foreach ($allTestCases as $reqCode => $testCases):
                $reqType = str_starts_with($reqCode, 'FR-') ? 'FR' : 'NFR';
            ?>
                <div class="test-case-container" data-req-code="<?php echo $reqCode; ?>" data-req-type="<?php echo $reqType; ?>">
                    <div class="requirement-header" onclick="toggleTestCases(this)">
                        <div class="requirement-title">
                            <span class="toggle-icon">▼</span>
                            <span><?php echo htmlspecialchars($reqCode); ?></span>
                        </div>
                        <span class="requirement-badge"><?php echo count($testCases); ?> test cases</span>
                    </div>
                    
                    <div class="test-cases-grid" style="display: grid;">
                        <?php foreach ($testCases as $tc): ?>
                            <div class="test-case-card <?php echo strtolower(str_replace(' ', '-', $tc['test_type'])); ?>" 
                                 data-test-type="<?php echo $tc['test_type']; ?>">
                                <span class="test-type-badge <?php echo strtolower(str_replace(' ', '-', $tc['test_type'])); ?>">
                                    <?php echo $tc['test_type']; ?>
                                </span>
                                <div class="test-case-title"><?php echo htmlspecialchars($tc['test_name']); ?></div>
                                <div class="test-case-description"><?php echo htmlspecialchars(substr($tc['description'], 0, 100)); ?>...</div>
                                <div class="test-case-meta">
                                    <div><strong>ID:</strong> <?php echo $tc['test_id']; ?></div>
                                    <div><strong>Persona:</strong> <?php echo $tc['user_persona']; ?></div>
                                    <div><strong>Priority:</strong> <?php echo $tc['priority']; ?></div>
                                    <div><strong>Steps:</strong> <?php echo count($tc['test_steps']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php
            endforeach;
            
            if (empty($allTestCases)):
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
                        <small>Compatible with QA tools and APIs</small>
                    </label>
                    <label class="export-option">
                        <input type="radio" name="export_format" value="csv">
                        <div><strong>CSV Format</strong></div>
                        <small>Open in Excel or spreadsheet tools</small>
                    </label>
                </div>
                <div class="dialog-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeExportDialog()">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="export_submit">Export</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleTestCases(element) {
            const grid = element.nextElementSibling;
            const icon = element.querySelector('.toggle-icon');
            
            if (grid.style.display === 'none') {
                grid.style.display = 'grid';
                icon.textContent = '▼';
                element.classList.remove('collapsed');
            } else {
                grid.style.display = 'none';
                icon.textContent = '▶';
                element.classList.add('collapsed');
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
                    const hasTestType = Array.from(container.querySelectorAll('.test-case-card'))
                        .some(card => card.dataset.testType === testTypeFilter);
                    if (!hasTestType) {
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
    </script>
</body>
</html>

<?php
// Helper function to filter test cases
function filterTestCases($allTestCases, $filterType, $filterTestType, $searchTerm) {
    $filtered = [];
    
    foreach ($allTestCases as $reqCode => $testCases) {
        // Filter by requirement type
        if ($filterType !== 'all') {
            $reqType = str_starts_with($reqCode, 'FR-') ? 'FR' : 'NFR';
            if ($reqType !== $filterType) {
                continue;
            }
        }
        
        // Filter by test type
        if ($filterTestType !== 'all') {
            $filteredByTestType = array_filter($testCases, fn($tc) => $tc['test_type'] === $filterTestType);
            if (empty($filteredByTestType)) {
                continue;
            }
            $testCases = $filteredByTestType;
        }
        
        // Filter by search term
        if ($searchTerm) {
            $filtered_cases = array_filter($testCases, function($tc) use ($searchTerm) {
                return stripos($tc['test_name'], $searchTerm) !== false ||
                       stripos($tc['description'], $searchTerm) !== false ||
                       stripos($tc['test_id'], $searchTerm) !== false;
            });
            if (empty($filtered_cases)) {
                continue;
            }
            $testCases = $filtered_cases;
        }
        
        if (!empty($testCases)) {
            $filtered[$reqCode] = $testCases;
        }
    }
    
    return $filtered;
}
?>
