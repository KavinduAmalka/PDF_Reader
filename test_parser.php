<?php
session_start();
require_once 'classes/PDFParser.php';
require_once 'classes/SRSParser.php';

if (!isset($_SESSION['pdf_text'])) {
    die('Please upload a PDF first at <a href="index.php">index.php</a>');
}

$text = $_SESSION['pdf_text'];

// Test Pattern 1: "2.X Module (FR-XX)"
echo "<h2>Pattern 1: '2.X Module Name (FR-XX)'</h2>";
preg_match_all('/^(?:●\s*)?(?:2\.\d+)\s+(.+?)\s*\((FR-\d+)\)/m', $text, $matches1, PREG_SET_ORDER);
echo "<pre>";
echo "Found " . count($matches1) . " matches:\n\n";
foreach ($matches1 as $match) {
    echo "Code: {$match[2]}, Title: {$match[1]}\n";
}
echo "</pre>";

// Test Pattern 2: "FR-XX: Title"
echo "<h2>Pattern 2: 'FR-XX: Title'</h2>";
preg_match_all('/^(?:●\s*)?(FR-\d+):\s*(.+?)$/m', $text, $matches2, PREG_SET_ORDER);
echo "<pre>";
echo "Found " . count($matches2) . " matches:\n\n";
foreach ($matches2 as $idx => $match) {
    if ($idx < 20) { // Show first 20 only
        echo "Code: {$match[1]}, Title: " . substr($match[2], 0, 60) . "...\n";
    }
}
echo "\nTotal: " . count($matches2) . " matches";
echo "</pre>";

// Show portion of text around FR-08 and FR-09
echo "<h2>Text around FR-08 and FR-09:</h2>";
$startPos = strpos($text, 'FR-08');
if ($startPos !== false) {
    $excerpt = substr($text, max(0, $startPos - 100), 500);
    echo "<pre style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
    echo htmlspecialchars($excerpt);
    echo "</pre>";
}

echo '<br><a href="sections.php?section=functional">View Functional Requirements</a>';
?>
