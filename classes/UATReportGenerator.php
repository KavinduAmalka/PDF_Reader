<?php
/**
 * UATReportGenerator - Generates PDF and Excel reports from UAT test results
 * Creates comprehensive UAT reports with requirement vs test case mapping
 */
class UATReportGenerator {
    private $results;
    private $sections;
    private $pdfFile;
    private $tracker;
    
    public function __construct($results, $sections, $pdfFile, $tracker) {
        $this->results = $results;
        $this->sections = $sections;
        $this->pdfFile = $pdfFile;
        $this->tracker = $tracker;
    }
    
    /**
     * Generate HTML report (for preview/PDF conversion)
     */
    public function generateHTMLReport() {
        $summary = $this->tracker->getUATSummary(md5($this->pdfFile), $this->getRequirementsList());
        
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>UAT Test Report</title>';
        $html .= '<style>';
        $html .= $this->getCSS();
        $html .= '</style>';
        $html .= '</head>';
        $html .= '<body>';
        
        // Title Page
        $html .= $this->generateTitlePage();
        
        // Executive Summary
        $html .= $this->generateExecutiveSummary($summary);
        
        // Test Coverage Summary
        $html .= $this->generateCoverageSummary($summary);
        
        // Detailed Results
        $html .= $this->generateDetailedResults();
        
        $html .= '</body>';
        $html .= '</html>';
        
        return $html;
    }
    
    /**
     * Get CSS styles for report
     */
    private function getCSS() {
        return '
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .page-break {
                page-break-after: always;
                margin: 50px 0;
                padding-top: 50px;
                border-top: 2px solid #667eea;
            }
            .title-page {
                text-align: center;
                padding: 100px 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .title-page h1 {
                font-size: 48px;
                margin-bottom: 20px;
            }
            .title-page h2 {
                font-size: 24px;
                margin-bottom: 40px;
                opacity: 0.9;
            }
            .title-page .meta {
                margin-top: 100px;
                font-size: 14px;
                opacity: 0.8;
            }
            h1, h2, h3 {
                color: #667eea;
                margin: 20px 0 10px 0;
            }
            h1 {
                font-size: 32px;
                border-bottom: 3px solid #667eea;
                padding-bottom: 10px;
            }
            h2 {
                font-size: 24px;
                margin-top: 30px;
            }
            h3 {
                font-size: 18px;
            }
            .container {
                padding: 40px;
                max-width: 1000px;
                margin: 0 auto;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th {
                background-color: #667eea;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .status-pass {
                color: #4caf50;
                font-weight: bold;
            }
            .status-fail {
                color: #f44336;
                font-weight: bold;
            }
            .status-blocked {
                color: #ff9800;
                font-weight: bold;
            }
            .status-not-tested {
                color: #999;
                font-weight: bold;
            }
            .progress-bar {
                background-color: #e0e0e0;
                border-radius: 10px;
                height: 20px;
                overflow: hidden;
                margin: 10px 0;
            }
            .progress-fill {
                background-color: #4caf50;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: bold;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin: 20px 0;
            }
            .summary-card {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 5px;
                border-left: 4px solid #667eea;
            }
            .summary-card h3 {
                margin-top: 0;
                color: #333;
            }
            .summary-card .value {
                font-size: 32px;
                font-weight: bold;
                color: #667eea;
            }
            .requirement-block {
                background: #f9f9f9;
                padding: 15px;
                border-left: 4px solid #667eea;
                margin: 15px 0;
                page-break-inside: avoid;
            }
            .test-case {
                background: white;
                padding: 15px;
                border: 1px solid #ddd;
                margin: 10px 0;
                border-radius: 3px;
            }
            .test-case-header {
                font-weight: bold;
                color: #333;
                margin-bottom: 10px;
            }
            .evidence {
                margin-top: 10px;
                padding: 10px;
                background: #e8f5e9;
                border-left: 3px solid #4caf50;
            }
            .footer {
                margin-top: 50px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                color: #999;
                font-size: 12px;
            }
        ';
    }
    
    /**
     * Generate title page
     */
    private function generateTitlePage() {
        $projectName = $this->sections['header']['project_name'] ?? 'PDF Reader';
        $currentDate = date('F j, Y');
        
        $html = '<div class="title-page">';
        $html .= '<h1>UAT Test Report</h1>';
        $html .= '<h2>' . htmlspecialchars($projectName) . '</h2>';
        $html .= '<div class="meta">';
        $html .= '<p><strong>Document Type:</strong> User Acceptance Testing Report</p>';
        $html .= '<p><strong>Generated Date:</strong> ' . $currentDate . '</p>';
        $html .= '<p><strong>Test Execution Framework:</strong> PDF Reader with SRS Parser</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary($summary) {
        $pdfHash = md5($this->pdfFile);
        $requirements = $this->getRequirementsList();
        $currentSummary = $this->tracker->getUATSummary($pdfHash, $requirements);
        
        $html = '<div class="page-break"></div>';
        $html .= '<div class="container">';
        $html .= '<h1>Executive Summary</h1>';
        $html .= '<p>This report documents the results of User Acceptance Testing (UAT) performed on the software requirements specification.</p>';
        
        $html .= '<div class="summary-grid">';
        $html .= '<div class="summary-card">';
        $html .= '<h3>Total Tests</h3>';
        $html .= '<div class="value">' . $currentSummary['total_tests'] . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="summary-card">';
        $html .= '<h3>Passed</h3>';
        $html .= '<div class="value" style="color: #4caf50;">' . $currentSummary['tests_passed'] . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="summary-card">';
        $html .= '<h3>Failed</h3>';
        $html .= '<div class="value" style="color: #f44336;">' . $currentSummary['tests_failed'] . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="summary-card">';
        $html .= '<h3>Pass Rate</h3>';
        $html .= '<div class="value" style="color: #667eea;">' . $currentSummary['overall_pass_percentage'] . '%</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<h2>Overall Status</h2>';
        $status = $this->getOverallStatus($currentSummary);
        $statusColor = ($status === '✅ PASSED') ? '#4caf50' : (($status === '❌ FAILED') ? '#f44336' : '#ff9800');
        $html .= '<p style="font-size: 24px; font-weight: bold; color: ' . $statusColor . ';">' . $status . '</p>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate coverage summary table
     */
    private function generateCoverageSummary($summary) {
        $html = '<div class="page-break"></div>';
        $html .= '<div class="container">';
        $html .= '<h1>Test Coverage Summary</h1>';
        
        $html .= '<table>';
        $html .= '<tr><th>Requirement Code</th><th>Type</th><th>Total Tests</th><th>Passed</th><th>Failed</th><th>Blocked</th><th>Pass %</th><th>Status</th></tr>';
        
        foreach ($summary['requirement_details'] as $code => $details) {
            $statusClass = 'status-not-tested';
            $statusText = '⭕ Not Started';
            
            if ($details['total_tests'] > 0) {
                if ($details['failed'] === 0 && $details['blocked'] === 0) {
                    $statusClass = 'status-pass';
                    $statusText = '✅ Passed';
                } elseif ($details['failed'] > 0) {
                    $statusClass = 'status-fail';
                    $statusText = '❌ Failed';
                } elseif ($details['blocked'] > 0) {
                    $statusClass = 'status-blocked';
                    $statusText = '⏸️ Blocked';
                }
            }
            
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($code) . '</strong></td>';
            $html .= '<td>' . $this->getRequirementType($code) . '</td>';
            $html .= '<td>' . $details['total_tests'] . '</td>';
            $html .= '<td><span class="status-pass">' . $details['passed'] . '</span></td>';
            $html .= '<td><span class="status-fail">' . $details['failed'] . '</span></td>';
            $html .= '<td><span class="status-blocked">' . $details['blocked'] . '</span></td>';
            $html .= '<td>' . $details['pass_percentage'] . '%</td>';
            $html .= '<td><span class="' . $statusClass . '">' . $statusText . '</span></td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate detailed results section
     */
    private function generateDetailedResults() {
        $html = '<div class="page-break"></div>';
        $html .= '<div class="container">';
        $html .= '<h1>Detailed Test Results</h1>';
        
        $results = $this->tracker->exportResults(md5($this->pdfFile));
        
        foreach ($results as $requirementCode => $testResults) {
            $html .= '<div class="requirement-block">';
            $html .= '<h2>' . htmlspecialchars($requirementCode) . '</h2>';
            $html .= '<p><strong>Requirement:</strong> ' . htmlspecialchars($this->getRequirementText($requirementCode)) . '</p>';
            
            foreach ($testResults as $testId => $result) {
                $html .= '<div class="test-case">';
                $html .= '<div class="test-case-header">' . htmlspecialchars($testId) . ' - ' . htmlspecialchars($result['title'] ?? 'N/A') . '</div>';
                
                $statusClass = 'status-not-tested';
                if (isset($result['status'])) {
                    if ($result['status'] === 'PASS') {
                        $statusClass = 'status-pass';
                    } elseif ($result['status'] === 'FAIL') {
                        $statusClass = 'status-fail';
                    } elseif ($result['status'] === 'BLOCKED') {
                        $statusClass = 'status-blocked';
                    }
                }
                
                $html .= '<p><strong>Status:</strong> <span class="' . $statusClass . '">' . ($result['status'] ?? 'Not Tested') . '</span></p>';
                $html .= '<p><strong>Test Type:</strong> ' . htmlspecialchars($result['test_type'] ?? 'N/A') . '</p>';
                
                if (isset($result['execution_date'])) {
                    $html .= '<p><strong>Executed:</strong> ' . htmlspecialchars($result['execution_date']) . '</p>';
                }
                
                if (isset($result['notes']) && !empty($result['notes'])) {
                    $html .= '<p><strong>Tester Notes:</strong> ' . htmlspecialchars($result['notes']) . '</p>';
                }
                
                if (isset($result['evidence_file'])) {
                    $html .= '<div class="evidence">';
                    $html .= '<p><strong>📎 Evidence Attached:</strong> ' . htmlspecialchars(basename($result['evidence_file'])) . '</p>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '<div class="footer">';
        $html .= '<p>Generated by PDF Reader with SRS Parser - UAT Testing Module</p>';
        $html .= '<p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate CSV/Excel data
     */
    public function generateCSVData() {
        $csv = "Requirement Code,Test ID,Test Type,Priority,Title,Description,Status,Pass %,Execution Date,Tester Notes\n";
        
        $results = $this->tracker->exportResults(md5($this->pdfFile));
        $summary = $this->tracker->getUATSummary(md5($this->pdfFile), $this->getRequirementsList());
        
        foreach ($results as $requirementCode => $testResults) {
            $passPercentage = $summary['requirement_details'][$requirementCode]['pass_percentage'] ?? 0;
            
            foreach ($testResults as $testId => $result) {
                $csv .= '"' . $requirementCode . '",';
                $csv .= '"' . $testId . '",';
                $csv .= '"' . ($result['test_type'] ?? '') . '",';
                $csv .= '"' . ($result['priority'] ?? 'N/A') . '",';
                $csv .= '"' . str_replace('"', '""', $result['title'] ?? '') . '",';
                $csv .= '"' . str_replace('"', '""', $result['description'] ?? '') . '",';
                $csv .= '"' . ($result['status'] ?? 'Not Tested') . '",';
                $csv .= '"' . $passPercentage . '%",';
                $csv .= '"' . ($result['execution_date'] ?? '') . '",';
                $csv .= '"' . str_replace('"', '""', $result['notes'] ?? '') . '"';
                $csv .= "\n";
            }
        }
        
        return $csv;
    }
    
    /**
     * Helper: Get requirement text
     */
    private function getRequirementText($code) {
        foreach ($this->sections as $section) {
            if (is_array($section) && isset($section['subsections'])) {
                foreach ($section['subsections'] as $sub) {
                    if ($sub['code'] === $code) {
                        return $sub['text'] ?? '';
                    }
                }
            }
        }
        return '';
    }
    
    /**
     * Helper: Get requirement type
     */
    private function getRequirementType($code) {
        foreach ($this->sections as $section) {
            if (is_array($section) && isset($section['subsections'])) {
                foreach ($section['subsections'] as $sub) {
                    if ($sub['code'] === $code) {
                        return $sub['type'] ?? 'FR';
                    }
                }
            }
        }
        return 'FR';
    }
    
    /**
     * Helper: Get requirements list
     */
    private function getRequirementsList() {
        $requirements = [];
        
        // Get functional requirements
        if (isset($this->sections['subsections']['functional']) && is_array($this->sections['subsections']['functional'])) {
            foreach ($this->sections['subsections']['functional'] as $code => $reqData) {
                // Handle both string and array structures
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
                    $requirements[] = [
                        'code' => $code,
                        'text' => $reqText,
                        'type' => 'FR'
                    ];
                }
            }
        }
        
        // Get non-functional requirements
        if (isset($this->sections['subsections']['non_functional']) && is_array($this->sections['subsections']['non_functional'])) {
            foreach ($this->sections['subsections']['non_functional'] as $code => $reqData) {
                // Handle both string and array structures
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
                    $requirements[] = [
                        'code' => $code,
                        'text' => $reqText,
                        'type' => 'NFR'
                    ];
                }
            }
        }
        
        return $requirements;
    }
    
    /**
     * Helper: Get overall status
     */
    private function getOverallStatus($summary) {
        if ($summary['total_tests'] === 0) {
            return '⭕ NOT STARTED';
        }
        
        if ($summary['tests_failed'] > 0) {
            return '❌ FAILED';
        }
        
        if ($summary['tests_blocked'] > 0) {
            return '⏸️ BLOCKED';
        }
        
        if ($summary['tests_passed'] === $summary['total_tests']) {
            return '✅ PASSED';
        }
        
        return '🔄 IN PROGRESS';
    }
}
?>
