<?php
/**
 * Quick test script for SVO Analysis
 * Run this directly to test a requirement
 */

require_once 'classes/SVOAnalyzer.php';

// Test requirement
$requirement = "The system shall allow the Corresponding Author to upload the camera-ready file to the system";
$code = "TEST-01";

echo "<h1>SVO Analysis Test</h1>";
echo "<p><strong>Testing Requirement:</strong> $requirement</p>";
echo "<hr>";

try {
    $analyzer = new SVOAnalyzer();
    
    if (!$analyzer->isAvailable()) {
        die("<p style='color: red;'>ERROR: API key not configured!</p>");
    }
    
    echo "<p style='color: blue;'>⏳ Analyzing... (this may take a few seconds)</p>";
    
    $result = $analyzer->analyze($requirement, $code);
    
    echo "<h2>Result:</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto;'>";
    print_r($result);
    echo "</pre>";
    
    echo "<h2>JSON Output:</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto;'>";
    echo json_encode($result, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Show compliance details
    if (isset($result['ieee_compliance'])) {
        $ieee = $result['ieee_compliance'];
        $score = $ieee['compliance_score'] ?? 0;
        $isCompliant = $ieee['is_compliant'] ?? false;
        
        echo "<h2>IEEE Compliance Summary:</h2>";
        echo "<div style='padding: 15px; border-radius: 5px; background: " . 
             ($isCompliant ? "#e8f5e9" : "#ffebee") . "; border: 2px solid " . 
             ($isCompliant ? "#4caf50" : "#f44336") . ";'>";
        
        echo "<p><strong>Score:</strong> $score%</p>";
        echo "<p><strong>Status:</strong> " . ($isCompliant ? "✅ Compliant" : "⚠️ Non-compliant") . "</p>";
        
        if (isset($ieee['checks'])) {
            echo "<h3>Checks:</h3><ul>";
            foreach ($ieee['checks'] as $check => $passed) {
                $icon = $passed ? "✅" : "❌";
                echo "<li>$icon $check: " . ($passed ? "PASS" : "FAIL") . "</li>";
            }
            echo "</ul>";
        }
        
        if (isset($ieee['issues']) && count($ieee['issues']) > 0) {
            echo "<h3>Issues:</h3><ul>";
            foreach ($ieee['issues'] as $issue) {
                echo "<li style='color: #f44336;'>$issue</li>";
            }
            echo "</ul>";
        }
        
        if (isset($ieee['recommendations']) && count($ieee['recommendations']) > 0) {
            echo "<h3>Recommendations:</h3><ul>";
            foreach ($ieee['recommendations'] as $rec) {
                echo "<li style='color: #2196f3;'>$rec</li>";
            }
            echo "</ul>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
