<?php
/**
 * UATResultTracker - Tracks and manages UAT test execution results
 * Stores test results in JSON files for session-based storage
 */
class UATResultTracker {
    private $resultsDir = 'uat_results/';
    private $sessionId;
    
    public function __construct() {
        $this->sessionId = session_id();
        
        // Create results directory if it doesn't exist
        if (!file_exists($this->resultsDir)) {
            mkdir($this->resultsDir, 0755, true);
        }
    }
    
    /**
     * Save test result
     */
    public function saveTestResult($pdfHash, $requirementCode, $testId, $result) {
        $resultsFile = $this->getResultsFile($pdfHash);
        
        // Load existing results
        $results = $this->loadResults($pdfHash);
        
        // Initialize requirement results if not exists
        if (!isset($results[$requirementCode])) {
            $results[$requirementCode] = [];
        }
        
        // Save test result with timestamp and evidence
        $result['timestamp'] = date('Y-m-d H:i:s');
        $result['test_id'] = $testId;
        $results[$requirementCode][$testId] = $result;
        
        // Save to file
        file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return true;
    }
    
    /**
     * Load all test results for a PDF
     */
    public function loadResults($pdfHash) {
        $resultsFile = $this->getResultsFile($pdfHash);
        
        if (!file_exists($resultsFile)) {
            return [];
        }
        
        $content = file_get_contents($resultsFile);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * Get single test result
     */
    public function getTestResult($pdfHash, $requirementCode, $testId) {
        $results = $this->loadResults($pdfHash);
        
        return $results[$requirementCode][$testId] ?? null;
    }
    
    /**
     * Get test coverage for a requirement
     */
    public function getRequirementCoverage($pdfHash, $requirementCode) {
        $results = $this->loadResults($pdfHash);
        
        if (!isset($results[$requirementCode])) {
            return [
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'blocked' => 0,
                'not_tested' => 0,
                'pass_percentage' => 0,
                'coverage_status' => 'Not Started'
            ];
        }
        
        $tests = $results[$requirementCode];
        $total = count($tests);
        
        $passed = 0;
        $failed = 0;
        $blocked = 0;
        
        foreach ($tests as $test) {
            if (isset($test['status'])) {
                if ($test['status'] === 'PASS') $passed++;
                elseif ($test['status'] === 'FAIL') $failed++;
                elseif ($test['status'] === 'BLOCKED') $blocked++;
            }
        }
        
        $not_tested = $total - ($passed + $failed + $blocked);
        $passPercentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;
        
        return [
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'blocked' => $blocked,
            'not_tested' => $not_tested,
            'pass_percentage' => $passPercentage,
            'coverage_status' => $this->getCoverageStatus($passed, $failed, $blocked, $not_tested, $total)
        ];
    }
    
    /**
     * Determine coverage status
     */
    private function getCoverageStatus($passed, $failed, $blocked, $notTested, $total) {
        if ($total === 0) return 'Not Started';
        if ($notTested > 0) return 'In Progress';
        if ($failed > 0) return 'Failed';
        if ($blocked > 0) return 'Blocked';
        if ($passed === $total) return 'Passed';
        
        return 'In Progress';
    }
    
    /**
     * Get overall UAT summary
     */
    public function getUATSummary($pdfHash, $requirements) {
        $summary = [
            'total_requirements' => count($requirements),
            'requirements_tested' => 0,
            'requirements_passed' => 0,
            'requirements_failed' => 0,
            'total_tests' => 0,
            'tests_passed' => 0,
            'tests_failed' => 0,
            'tests_blocked' => 0,
            'overall_pass_percentage' => 0,
            'requirement_details' => []
        ];
        
        foreach ($requirements as $req) {
            $reqCode = $req['code'];
            $coverage = $this->getRequirementCoverage($pdfHash, $reqCode);
            
            $summary['requirement_details'][$reqCode] = $coverage;
            
            if ($coverage['total_tests'] > 0) {
                $summary['requirements_tested']++;
                
                if ($coverage['failed'] === 0 && $coverage['blocked'] === 0) {
                    $summary['requirements_passed']++;
                } else {
                    $summary['requirements_failed']++;
                }
            }
            
            $summary['total_tests'] += $coverage['total_tests'];
            $summary['tests_passed'] += $coverage['passed'];
            $summary['tests_failed'] += $coverage['failed'];
            $summary['tests_blocked'] += $coverage['blocked'];
        }
        
        if ($summary['total_tests'] > 0) {
            $summary['overall_pass_percentage'] = round(($summary['tests_passed'] / $summary['total_tests']) * 100, 2);
        }
        
        return $summary;
    }
    
    /**
     * Save screenshot evidence
     */
    public function saveScreenshot($pdfHash, $requirementCode, $testId, $uploadedFile) {
        $evidenceDir = $this->resultsDir . $pdfHash . '/evidence/';
        
        if (!file_exists($evidenceDir)) {
            mkdir($evidenceDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = $testId . '_' . time() . '_' . basename($uploadedFile['name']);
        $destination = $evidenceDir . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        
        if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
            return 'evidence/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        }
        
        return null;
    }
    
    /**
     * Get evidence file path
     */
    public function getEvidencePath($pdfHash, $evidenceFile) {
        return $this->resultsDir . $pdfHash . '/' . $evidenceFile;
    }
    
    /**
     * Get results file path
     */
    private function getResultsFile($pdfHash) {
        return $this->resultsDir . $pdfHash . '_results.json';
    }
    
    /**
     * Export results to array for report generation
     */
    public function exportResults($pdfHash) {
        return $this->loadResults($pdfHash);
    }
}
?>
