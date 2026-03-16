<?php
session_start();

// Check if a PDF has been uploaded and parsed
if (!isset($_SESSION['uploaded_pdf']) || !file_exists($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No PDF file found. Please upload a PDF first.');
}

require_once 'classes/PDFParser.php';
require_once 'classes/SRSParser.php';

$pdfPath = $_SESSION['uploaded_pdf'];
$originalFilename = $_SESSION['original_filename'] ?? 'document.pdf';
$parserVersion = '2026-03-16-diagram-cutoff-v3-simplified';

// Parse PDF if not already parsed
if (!isset($_SESSION['srs_sections']) || ($_SESSION['srs_parser_version'] ?? '') !== $parserVersion) {
    try {
        $pdfParser = new PDFParser($pdfPath);
        $text = $pdfParser->extractText();
        
        $srsParser = new SRSParser($text);
        $sections = $srsParser->parse();
        
        $_SESSION['srs_sections'] = $sections;
        $_SESSION['pdf_text'] = $text;
        $_SESSION['srs_parser_version'] = $parserVersion;
    } catch (Exception $e) {
        $_SESSION['parse_error'] = $e->getMessage();
    }
}

$sections = $_SESSION['srs_sections'] ?? [];
$parseError = $_SESSION['parse_error'] ?? null;
$pdfText = $_SESSION['pdf_text'] ?? '';
$hasOverallDescription = !empty($sections['header']['overall_description']);
$functionalSectionNumber = $hasOverallDescription ? 3 : 2;
$nonFunctionalSectionNumber = $hasOverallDescription ? 4 : 3;

// Get selected section from query parameter
$selectedSection = $_GET['section'] ?? 'all';

// Debug mode
$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRS Sections - <?php echo htmlspecialchars($originalFilename); ?></title>
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
        }
        
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar h3 {
            margin-bottom: 15px;
            color: #667eea;
            font-size: 18px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-menu li {
            margin-bottom: 8px;
        }
        
        .nav-menu a {
            display: block;
            padding: 10px 15px;
            color: #555;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .nav-menu a:hover {
            background: #f0f0f0;
            color: #667eea;
        }
        
        .nav-menu a.active {
            background: #667eea;
            color: white;
        }
        
        .content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            min-height: 500px;
        }
        
        .section-header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .section-header p {
            color: #666;
            font-size: 14px;
        }
        
        .section-block {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        
        .section-block h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .section-block h4 {
            color: #667eea;
            margin: 15px 0 10px;
            font-size: 16px;
        }
        
        .line {
            padding: 8px 0;
            line-height: 1.6;
            color: #555;
            border-bottom: 1px solid #eee;
        }
        
        .line:last-child {
            border-bottom: none;
        }
        
        .subsection {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }
        
        .subsection-header {
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .requirement-item {
            padding: 10px 15px;
            margin: 8px 0;
            background: #fff;
            border-left: 3px solid #667eea;
            border-radius: 3px;
        }
        
        .requirement-item strong {
            display: inline-block;
            margin-right: 8px;
            font-weight: 600;
        }
        
        .requirement-item.nested {
            margin-left: 30px;
            padding-left: 20px;
            border-left-color: #9fa8da;
            background: #f8f9ff;
            border-left-width: 2px;
            flex-direction: column;
        }
        
        .requirement-item.nested > div:first-child {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .requirement-item.nested span {
            display: inline-block;
            margin-right: 8px;
            min-width: 20px;
        }
        
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }
        
        .info-card label {
            display: block;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .info-card value {
            display: block;
            color: #333;
            font-size: 16px;
        }
        
        .debug-section {
            background: #f9f9f9;
            border: 2px solid #ff9800;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .debug-section h3 {
            color: #ff9800;
            margin-bottom: 10px;
        }
        
        .raw-text {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 3px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        /* SVO Analysis Styles */
        .analyze-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .analyze-btn.sub-analyze {
            padding: 4px 8px;
            font-size: 10px;
            margin-left: 8px;
            background: linear-gradient(135deg, #48c6ef 0%, #6f86d6 100%);
        }
        
        .analyze-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .analyze-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .svo-analysis {
            background: #f8f9ff;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            display: none;
        }
        
        .svo-analysis.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.9);
            margin-left: 10px;
        }
        
        .priority-mandatory { color: #ff4444; }
        .priority-recommended { color: #ffbb33; }
        .priority-optional { color: #00C851; }
        .priority-unknown { color: #aaa; }
        
        .svo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .svo-header h4 {
            color: #667eea;
            margin: 0;
            font-size: 18px;
        }
        
        .close-analysis {
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 1;
        }
        
        .close-analysis:hover {
            color: #667eea;
        }
        
        .svo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .svo-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        
        .svo-box.subject {
            border-left-color: #4caf50;
        }
        
        .svo-box.verb {
            border-left-color: #ff9800;
        }
        
        .svo-box.object {
            border-left-color: #2196f3;
        }
        
        .svo-box h5 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #667eea;
        }
        
        .svo-box.subject h5 {
            color: #4caf50;
        }
        
        .svo-box.verb h5 {
            color: #ff9800;
        }
        
        .svo-box.object h5 {
            color: #2196f3;
        }
        
        .svo-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .svo-box li {
            padding: 5px 0;
            color: #333;
        }
        
        .svo-box li:before {
            content: "• ";
            color: #667eea;
            font-weight: bold;
        }
        
        /* IEEE Compliance Styles */
        .compliance-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .compliance-badge.compliance-pass {
            color: #00C851;
            border: 1px solid #00C851;
        }
        
        .compliance-badge.compliance-fail {
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        
        .ieee-compliance-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #ddd;
        }
        
        .ieee-compliance-section.compliance-pass {
            border-color: #00C851;
            background: #f0fff4;
        }
        
        .ieee-compliance-section.compliance-fail {
            border-color: #ff4444;
            background: #fff5f5;
        }
        
        .ieee-compliance-section h5 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 16px;
            font-weight: 600;
        }
        
        .compliance-checks {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .compliance-check {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: white;
            border-radius: 5px;
            font-size: 13px;
            border: 1px solid #ddd;
            cursor: help;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .compliance-check:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .compliance-check.check-pass {
            border-color: #00C851;
            background: #f0fff4;
        }
        
        .compliance-check.check-fail {
            border-color: #ff4444;
            background: #fff5f5;
        }
        
        .compliance-check span {
            font-size: 14px;
        }
        
        .compliance-issues {
            background: #fff5f5;
            border-left: 4px solid #ff4444;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .compliance-issues strong {
            color: #ff4444;
            display: block;
            margin-bottom: 8px;
        }
        
        .compliance-issues ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .compliance-issues li {
            margin: 5px 0;
            color: #666;
        }
        
        .compliance-recommendations {
            background: #e8f4fd;
            border-left: 4px solid #2196f3;
            padding: 12px 15px;
            border-radius: 4px;
        }
        
        .compliance-recommendations strong {
            color: #2196f3;
            display: block;
            margin-bottom: 8px;
        }
        
        .compliance-recommendations ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .compliance-recommendations li {
            margin: 5px 0;
            color: #666;
        }
        
        .svo-detail-section {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .svo-detail-section h5 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .svo-detail-section p {
            margin: 5px 0;
            color: #333;
            line-height: 1.6;
        }
        
        .svo-detail-section .label {
            font-weight: bold;
            color: #667eea;
            display: inline-block;
            min-width: 150px;
        }
        
        .svo-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .svo-badge.complexity-low {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .svo-badge.complexity-medium {
            background: #fff9c4;
            color: #f57f17;
        }
        
        .svo-badge.complexity-high {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .svo-badge.type {
            background: #e1bee7;
            color: #6a1b9a;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error-box {
            background: #ffebee;
            border: 1px solid #ef5350;
            color: #c62828;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .svo-sentence {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .dependencies-list {
            list-style: none;
            padding: 0;
        }
        
        .dependencies-list li {
            padding: 8px 12px;
            background: #f5f5f5;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid #667eea;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
            
            .svo-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📄 SRS Document Parser</h1>
                <p style="opacity: 0.9; font-size: 14px; margin-top: 5px;">
                    <?php echo htmlspecialchars($originalFilename); ?>
                </p>
            </div>
            <div class="header-buttons">
                <a href="viewer.php" class="btn">Original PDF</a>
                <a href="index.php" class="btn">Upload New</a>
            </div>
        </div>
    </div>

    <div class="container">
        <aside class="sidebar">
            <h3>Sections</h3>
            <ul class="nav-menu">
                <li><a href="?section=all" class="<?php echo $selectedSection === 'all' ? 'active' : ''; ?>">All Sections</a></li>
                <li><a href="?section=header" class="<?php echo $selectedSection === 'header' ? 'active' : ''; ?>">Header Info</a></li>
                <li><a href="?section=introduction" class="<?php echo $selectedSection === 'introduction' ? 'active' : ''; ?>">Introduction</a></li>
                <li><a href="?section=functional" class="<?php echo $selectedSection === 'functional' ? 'active' : ''; ?>">Functional Requirements</a></li>
                <li><a href="?section=non_functional" class="<?php echo $selectedSection === 'non_functional' ? 'active' : ''; ?>">Non-Functional Requirements</a></li>
            </ul>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">
            <h3 style="color: #ff9800; font-size: 14px;">Debug</h3>
            <ul class="nav-menu">
                <li><a href="?section=debug&debug=1" class="<?php echo $debugMode ? 'active' : ''; ?>" style="color: #ff9800;">🔍 View Raw Text</a></li>
            </ul>
        </aside>

        <main class="content">
            <?php if ($parseError): ?>
                <div class="error-message">
                    <strong>⚠ Parsing Error:</strong><br>
                    <?php echo htmlspecialchars($parseError); ?>
                </div>
            <?php elseif ($debugMode): ?>
                <div class="debug-section">
                    <h2>🔍 Debug Mode - Raw Extracted Text</h2>
                    <div class="debug-info">
                        <strong>📊 Text Statistics:</strong><br>
                        Total Characters: <?php echo strlen($pdfText); ?><br>
                        Total Lines: <?php echo count(explode("\n", $pdfText)); ?><br>
                        <br>
                        <strong>💡 Tip:</strong> Check if the text below contains section markers like "1. Introduction", "2. Functional Requirements", etc.
                    </div>
                    
                    <h3>Raw PDF Text:</h3>
                    <div class="raw-text"><?php echo htmlspecialchars($pdfText); ?></div>
                    
                    <br>
                    <h3>Parsed Sections Data:</h3>
                    <div class="raw-text"><?php echo htmlspecialchars(print_r($sections, true)); ?></div>
                </div>
            <?php elseif (empty($sections)): ?>
                <div class="error-message">
                    <strong>⚠ No sections found</strong><br>
                    The PDF could not be parsed. Please make sure it follows the SRS template structure.<br><br>
                    <a href="?section=debug&debug=1" style="color: #667eea; text-decoration: underline;">🔍 Click here to view raw extracted text and debug</a>
                </div>
            <?php else: ?>
                
                <?php if ($selectedSection === 'all' || $selectedSection === 'header'): ?>
                    <div class="section-header">
                        <h2>Document Information</h2>
                        <p>Project metadata and basic information</p>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <label>Project Name</label>
                            <value><?php echo htmlspecialchars($sections['header']['project_name'] ?: 'Not specified'); ?></value>
                        </div>
                        <div class="info-card">
                            <label>Module</label>
                            <value><?php echo htmlspecialchars($sections['header']['module'] ?: 'Not specified'); ?></value>
                        </div>
                        <div class="info-card">
                            <label>Client</label>
                            <value><?php echo htmlspecialchars($sections['header']['client'] ?: 'Not specified'); ?></value>
                        </div>
                        <div class="info-card">
                            <label>Technology Stack</label>
                            <value><?php echo htmlspecialchars($sections['header']['technology_stack'] ?: 'Not specified'); ?></value>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($selectedSection === 'all' || $selectedSection === 'introduction'): ?>
                    <div class="section-block">
                        <h3>1. Introduction</h3>
                        <?php if (!empty($sections['introduction'])): ?>
                            <?php foreach ($sections['introduction'] as $line): ?>
                                <?php if (!empty(trim($line))): ?>
                                    <div class="line"><?php echo htmlspecialchars($line); ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #999;">No introduction content found.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($sections['header']['overall_description'])): ?>
                        <div class="section-block">
                            <h3>2. Overall Description</h3>
                            <?php foreach ($sections['header']['overall_description'] as $line): ?>
                                <?php if (!empty(trim($line))): ?>
                                    <div class="line"><?php echo htmlspecialchars($line); ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($selectedSection === 'all' || $selectedSection === 'functional'): ?>
                    <div class="section-block">
                        <h3><?php echo $functionalSectionNumber; ?>. Functional Requirements</h3>
                        
                        <?php 
                        $frSubsections = $sections['subsections']['functional'] ?? [];
                        if (!empty($frSubsections)): 
                        ?>
                            <?php foreach ($frSubsections as $code => $fr): ?>
                                <div class="subsection">
                                    <div class="subsection-header"><?php echo htmlspecialchars($code . ': ' . $fr['title']); ?></div>
                                    <?php if (!empty($fr['content'])): ?>
                                        <?php foreach ($fr['content'] as $item): ?>
                                            <?php if (is_array($item)): ?>
                                                <div class="requirement-item">
                                                    <div>
                                                        <strong style="color: #764ba2;"><?php echo htmlspecialchars($item['code']); ?>:</strong>
                                                        <?php echo htmlspecialchars($item['description']); ?>
                                                        <button class="analyze-btn" onclick="analyzeSVO('<?php echo htmlspecialchars($item['code']); ?>', this)" data-text="<?php echo htmlspecialchars($item['description']); ?>" data-original-text="🔍 Analyze SVO">
                                                            🔍 Analyze SVO
                                                        </button>
                                                    </div>
                                                    <div class="svo-analysis" id="svo-<?php echo htmlspecialchars($item['code']); ?>">
                                                        <!-- SVO Analysis will be loaded here -->
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($item['items'])): ?>
                                                    <?php 
                                                    $itemType = $item['item_type'] ?? 'numbered';
                                                    foreach ($item['items'] as $idx => $nestedItem): 
                                                        $nestedCode = $item['code'] . '-' . ($idx + 1);
                                                    ?>
                                                        <div class="requirement-item nested">
                                                            <div>
                                                                <?php if ($itemType === 'bullet'): ?>
                                                                    <span style="color: #9fa8da; font-weight: 600;">○</span>
                                                                <?php else: ?>
                                                                    <span style="color: #9fa8da; font-weight: 600;"><?php echo ($idx + 1); ?>.</span>
                                                                <?php endif; ?>
                                                                <?php echo htmlspecialchars($nestedItem); ?>
                                                                <button class="analyze-btn sub-analyze" onclick="analyzeSVO('<?php echo htmlspecialchars($nestedCode); ?>', this)" data-text="<?php echo htmlspecialchars($nestedItem); ?>" data-original-text="🔍 Analyze" title="Analyze this sub-requirement">
                                                                    🔍 Analyze
                                                                </button>
                                                            </div>
                                                            <div class="svo-analysis" id="svo-<?php echo htmlspecialchars($nestedCode); ?>">
                                                                <!-- SVO Analysis will be loaded here -->
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="requirement-item"><?php echo htmlspecialchars($item); ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php if (!empty($sections['functional_requirements'])): ?>
                                <?php foreach ($sections['functional_requirements'] as $line): ?>
                                    <?php if (!empty(trim($line))): ?>
                                        <div class="line"><?php echo htmlspecialchars($line); ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #999;">No functional requirements found.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($selectedSection === 'all' || $selectedSection === 'non_functional'): ?>
                    <div class="section-block">
                        <h3><?php echo $nonFunctionalSectionNumber; ?>. Non-Functional Requirements</h3>
                        
                        <?php 
                        $nfrSubsections = $sections['subsections']['non_functional'] ?? [];
                        if (!empty($nfrSubsections)): 
                        ?>
                            <?php foreach ($nfrSubsections as $code => $nfr): ?>
                                <div class="subsection">
                                    <div class="subsection-header"><?php echo htmlspecialchars($code . ' (' . $nfr['type'] . ')'); ?></div>
                                    <div class="requirement-item"><?php echo htmlspecialchars($nfr['content']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php if (!empty($sections['non_functional_requirements'])): ?>
                                <?php foreach ($sections['non_functional_requirements'] as $line): ?>
                                    <?php if (!empty(trim($line))): ?>
                                        <div class="line"><?php echo htmlspecialchars($line); ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #999;">No non-functional requirements found.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>

    <script>
        // SVO Analysis functionality
        let currentlyOpenAnalysis = null;
        
        async function analyzeSVO(requirementCode, buttonElement) {
            const analysisDiv = document.getElementById('svo-' + requirementCode);
            
            // If this analysis is already open, close it
            if (currentlyOpenAnalysis === requirementCode) {
                analysisDiv.classList.remove('show');
                currentlyOpenAnalysis = null;
                // Restore original button text
                const originalText = buttonElement.getAttribute('data-original-text') || '🔍 Analyze';
                buttonElement.innerHTML = originalText;
                return;
            }
            
            // Close any currently open analysis
            if (currentlyOpenAnalysis) {
                const prevAnalysis = document.getElementById('svo-' + currentlyOpenAnalysis);
                if (prevAnalysis) {
                    prevAnalysis.classList.remove('show');
                }
                // Reset previous button text
                const prevButtons = document.querySelectorAll('.analyze-btn');
                prevButtons.forEach(btn => {
                    if (btn.innerHTML.includes('Hide')) {
                        // Restore original button text
                        const originalText = btn.getAttribute('data-original-text') || '🔍 Analyze';
                        btn.innerHTML = originalText;
                    }
                });
            }
            
            // Get requirement text from data attribute
            const requirementText = buttonElement.getAttribute('data-text');
            
            // Show loading state
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<span class="loading-spinner"></span> Analyzing...';
            
            // Show the analysis div with loading message
            analysisDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: #667eea;"><div class="loading-spinner" style="width: 30px; height: 30px; border-width: 3px; margin: 0 auto 10px;"></div><p>Analyzing requirement structure...</p></div>';
            analysisDiv.classList.add('show');
            
            try {
                // Call the analysis API
                const response = await fetch('analyze_requirement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        requirement_code: requirementCode,
                        requirement_text: requirementText
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Display the analysis
                    displayAnalysis(analysisDiv, result, requirementCode);
                    buttonElement.innerHTML = '✓ Hide Analysis';
                    currentlyOpenAnalysis = requirementCode;
                } else {
                    // Display error
                    analysisDiv.innerHTML = `
                        <div class="error-box">
                            <strong>⚠ Analysis Error:</strong><br>
                            ${escapeHtml(result.error || 'Unknown error occurred')}
                            ${result.config_needed ? '<br><br><small>Please add your OpenAI API key in <strong>config.php</strong></small>' : ''}
                        </div>
                    `;
                    const originalText = buttonElement.getAttribute('data-original-text') || '🔍 Analyze';
                    buttonElement.innerHTML = originalText;
                }
                
            } catch (error) {
                analysisDiv.innerHTML = `
                    <div class="error-box">
                        <strong>⚠ Network Error:</strong><br>
                        ${escapeHtml(error.message)}
                    </div>
                `;
                const originalText = buttonElement.getAttribute('data-original-text') || '🔍 Analyze';
                buttonElement.innerHTML = originalText;
            } finally {
                buttonElement.disabled = false;
            }
        }
        
        function displayAnalysis(container, analysis, code) {
            // Priority badge styling
            const priorityConfig = {
                'mandatory': { icon: '🔴', label: 'Mandatory', class: 'priority-mandatory' },
                'recommended': { icon: '🟡', label: 'Recommended', class: 'priority-recommended' },
                'optional': { icon: '🟢', label: 'Optional', class: 'priority-optional' },
                'unknown': { icon: '⚪', label: 'Unknown', class: 'priority-unknown' }
            };
            
            const priority = priorityConfig[analysis.priority] || priorityConfig['unknown'];
            
            // IEEE Compliance badge
            const ieee = analysis.ieee_compliance || {};
            // Handle both boolean and numeric (PHP may send 1/0 or true/false)
            const isCompliant = ieee.is_compliant === true || ieee.is_compliant === 1 || ieee.is_compliant === "1";
            // Handle score - default to 0 if not provided or null
            const complianceScore = (ieee.compliance_score !== undefined && ieee.compliance_score !== null) ? parseInt(ieee.compliance_score) : 0;
            const complianceClass = isCompliant ? 'compliance-pass' : 'compliance-fail';
            const complianceIcon = isCompliant ? '✅' : '⚠️';
            
            // Debug log
            console.log('IEEE Compliance Data:', {
                ieee: ieee,
                isCompliant: isCompliant,
                complianceScore: complianceScore,
                raw_is_compliant: ieee.is_compliant,
                raw_score: ieee.compliance_score
            });
            
            let html = `
                <div class="svo-header">
                    <h4>📊 SVO Analysis: ${escapeHtml(code)}</h4>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span class="priority-badge ${priority.class}">${priority.icon} ${priority.label}</span>
                        <span class="compliance-badge ${complianceClass}">${complianceIcon} IEEE: ${complianceScore}%</span>
                    </div>
                    <button class="close-analysis" onclick="closeAnalysis('${escapeHtml(code)}')" title="Close">×</button>
                </div>
            `;
            
            // IEEE Compliance Section (shown first, before SVO)
            if (ieee && Object.keys(ieee).length > 0) {
                html += `<div class="ieee-compliance-section ${complianceClass}">`;
                html += `<h5>📋 IEEE 29148 Standards Compliance</h5>`;
                
                // Compliance checks
                const checks = ieee.checks || {};
                html += '<div class="compliance-checks">';
                const checkLabels = {
                    'has_modal_verb': 'Has Modal Verb',
                    'correct_modal_verb': 'Correct Modal Verb (shall/should/may)',
                    'is_clear': 'Clear & Understandable',
                    'is_testable': 'Testable/Verifiable',
                    'is_single_requirement': 'Single Requirement (not compound)',
                    'is_unambiguous': 'Unambiguous',
                    'has_quantifiable_measures': 'Has Quantifiable Measures'
                };
                
                const checkTooltips = {
                    'has_modal_verb': 'Contains modal verbs like shall, should, may, must, will, can',
                    'correct_modal_verb': 'Uses IEEE-preferred modal verbs: shall (mandatory), should (recommended), may (optional)',
                    'is_clear': 'Requirement is clear with well-defined subject, verb, and object',
                    'is_testable': 'Can be tested or verified through observation/measurement',
                    'is_single_requirement': 'Expresses only one requirement (not multiple combined with and/or)',
                    'is_unambiguous': 'Has only one clear interpretation, not open to multiple meanings',
                    'has_quantifiable_measures': 'Contains specific measurable criteria: numbers (10MB, 5 items), time limits (3 seconds), formats (PDF), ranges (1-100), percentages (95%), or physical measurements (1920x1080)'
                };
                
                for (const [key, label] of Object.entries(checkLabels)) {
                    // Handle both boolean and numeric values (PHP may send 1/0 or true/false)
                    const passed = checks[key] === true || checks[key] === 1 || checks[key] === "1";
                    const checkIcon = passed ? '✅' : '❌';
                    const checkClass = passed ? 'check-pass' : 'check-fail';
                    const tooltip = checkTooltips[key] || '';
                    
                    // Add info icon for quantifiable measures to indicate more details
                    const infoIcon = key === 'has_quantifiable_measures' ? ' <span style="color: #667eea; font-weight: bold;"></span>' : '';
                    
                    html += `<div class="compliance-check ${checkClass}" title="${escapeHtml(tooltip)}"><span>${checkIcon}</span> ${label}${infoIcon}</div>`;
                }
                html += '</div>';
                
                // Issues
                if (ieee.issues && ieee.issues.length > 0) {
                    html += '<div class="compliance-issues"><strong>⚠️ Issues Found:</strong><ul>';
                    ieee.issues.forEach(issue => {
                        html += `<li>${escapeHtml(issue)}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                // Recommendations
                if (ieee.recommendations && ieee.recommendations.length > 0) {
                    html += '<div class="compliance-recommendations"><strong>💡 Recommendations:</strong><ul>';
                    ieee.recommendations.forEach(rec => {
                        html += `<li>${escapeHtml(rec)}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                html += '</div>';
            }
            
            // Simple SVO components grid - only 3 boxes
            html += '<div class="svo-grid">';
            
            // Subjects
            html += '<div class="svo-box subject"><h5>📍 Subject</h5><ul>';
            if (analysis.subjects && analysis.subjects.length > 0) {
                analysis.subjects.forEach(subject => {
                    html += `<li>${escapeHtml(subject)}</li>`;
                });
            } else {
                html += '<li style="color: #999;">-</li>';
            }
            html += '</ul></div>';
            
            // Verbs (with modal verbs highlighted)
            html += '<div class="svo-box verb"><h5>⚡ Verb</h5><ul>';
            if (analysis.verbs && analysis.verbs.length > 0) {
                analysis.verbs.forEach(verb => {
                    html += `<li>${escapeHtml(verb)}</li>`;
                });
            } else {
                html += '<li style="color: #999;">-</li>';
            }
            // Show modal verbs separately if present
            if (analysis.modal_verbs && analysis.modal_verbs.length > 0) {
                html += '<li style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd; font-weight: 600; color: #d63384;">Modal: ';
                html += analysis.modal_verbs.map(m => escapeHtml(m)).join(', ');
                html += '</li>';
            }
            html += '</ul></div>';
            
            // Objects
            html += '<div class="svo-box object"><h5>🎯 Object</h5><ul>';
            if (analysis.objects && analysis.objects.length > 0) {
                analysis.objects.forEach(obj => {
                    html += `<li>${escapeHtml(obj)}</li>`;
                });
            } else {
                html += '<li style="color: #999;">-</li>';
            }
            html += '</ul></div>';
            
            html += '</div>'; // End svo-grid
            
            container.innerHTML = html;
        }
        
        function closeAnalysis(code) {
            const analysisDiv = document.getElementById('svo-' + code);
            if (analysisDiv) {
                analysisDiv.classList.remove('show');
            }
            
            // Reset button text to original
            const buttons = document.querySelectorAll('.analyze-btn');
            buttons.forEach(btn => {
                const btnCode = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
                if (btnCode === code) {
                    const originalText = btn.getAttribute('data-original-text') || '🔍 Analyze';
                    btn.innerHTML = originalText;
                }
            });
            
            currentlyOpenAnalysis = null;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
