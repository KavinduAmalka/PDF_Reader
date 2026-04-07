<?php
/**
 * UATTestCase - Auto-generates and manages UAT test cases from requirements
 * Generates test cases for both Functional Requirements (FR) and Non-Functional Requirements (NFR)
 */
class UATTestCase {
    private $testCases = [];
    private $requirementCode;
    private $requirementText;
    private $requirementType; // 'FR' or 'NFR'
    
    public function __construct($requirementCode, $requirementText, $requirementType = 'FR') {
        $this->requirementCode = $requirementCode;
        $this->requirementText = $requirementText;
        $this->requirementType = $requirementType;
    }
    
    /**
     * Generate test cases automatically from requirement text
     * @return array Array of auto-generated test cases
     */
    public function generateTestCases() {
        $this->testCases = [];
        
        // Extract key components from requirement
        $components = $this->extractRequirementComponents();
        
        // Generate basic test case
        $basicTC = $this->generateBasicTestCase($components);
        $this->testCases[] = $basicTC;
        
        // Generate edge case test
        if ($this->shouldGenerateEdgeCase($components)) {
            $edgeTC = $this->generateEdgeTestCase($components);
            $this->testCases[] = $edgeTC;
        }
        
        // Generate error/negative case test
        if ($this->shouldGenerateNegativeCase($components)) {
            $negativeTC = $this->generateNegativeTestCase($components);
            $this->testCases[] = $negativeTC;
        }
        
        // Generate performance test for NFR
        if ($this->requirementType === 'NFR' && $this->isPerformanceRequirement($this->requirementText)) {
            $perfTC = $this->generatePerformanceTestCase($components);
            $this->testCases[] = $perfTC;
        }
        
        return $this->testCases;
    }
    
    /**
     * Extract key components from requirement text
     * @return array Components (actors, actions, objects, conditions)
     */
    private function extractRequirementComponents() {
        $text = $this->requirementText;
        
        return [
            'actors' => $this->extractActors($text),
            'actions' => $this->extractActions($text),
            'objects' => $this->extractObjects($text),
            'conditions' => $this->extractConditions($text),
            'constraints' => $this->extractConstraints($text)
        ];
    }
    
    /**
     * Extract actors/users from requirement
     */
    private function extractActors($text) {
        $actors = [];
        
        if (preg_match_all('/(user|admin|system|customer|client|manager|operator|guest)/i', $text, $matches)) {
            $actors = array_unique(array_map('strtolower', $matches[1]));
        }
        
        // Default to 'user' if no specific actor found
        if (empty($actors)) {
            $actors = ['user'];
        }
        
        return $actors;
    }
    
    /**
     * Extract action verbs from requirement
     */
    private function extractActions($text) {
        $actions = [];
        
        $verbs = [
            'upload', 'download', 'create', 'delete', 'update', 'edit', 'view', 'display',
            'access', 'submit', 'retrieve', 'store', 'save', 'validate', 'authenticate',
            'authorize', 'analyze', 'parse', 'extract', 'generate', 'export', 'import',
            'filter', 'search', 'sort', 'navigate', 'browse', 'scan', 'process'
        ];
        
        foreach ($verbs as $verb) {
            if (stripos($text, $verb) !== false) {
                $actions[] = $verb;
            }
        }
        
        return array_unique($actions);
    }
    
    /**
     * Extract objects/resources from requirement
     */
    private function extractObjects($text) {
        $objects = [];
        
        $objectKeywords = [
            'file', 'pdf', 'document', 'requirement', 'section', 'page', 'data',
            'record', 'field', 'form', 'report', 'analysis', 'result', 'error',
            'permission', 'session', 'account', 'profile', 'notification', 'message'
        ];
        
        foreach ($objectKeywords as $obj) {
            if (stripos($text, $obj) !== false) {
                $objects[] = $obj;
            }
        }
        
        return array_unique($objects);
    }
    
    /**
     * Extract conditions from requirement (if, when, where, etc.)
     */
    private function extractConditions($text) {
        $conditions = [];
        
        // Look for conditional keywords
        if (preg_match_all('/(?:if|when|where|after|before|during|while|as soon as)\s+(.+?)(?:[,;]|and|or|then)/i', $text, $matches)) {
            $conditions = array_map('trim', $matches[1]);
            $conditions = array_unique($conditions);
        }
        
        return $conditions;
    }
    
    /**
     * Extract constraints (should, shall, must, may)
     */
    private function extractConstraints($text) {
        $constraints = [];
        
        if (preg_match_all('/(shall|should|must|may|can|will|must not)/i', $text, $matches)) {
            $constraints = array_unique(array_map('strtolower', $matches[1]));
        }
        
        return $constraints;
    }
    
    /**
     * Generate basic positive test case
     */
    private function generateBasicTestCase($components) {
        $actors = $components['actors'][0] ?? 'user';
        $actions = $components['actions'][0] ?? 'action';
        $objects = $components['objects'][0] ?? 'object';
        
        return [
            'test_id' => $this->requirementCode . '.T1',
            'requirement_code' => $this->requirementCode,
            'requirement_type' => $this->requirementType,
            'test_type' => 'Positive',
            'title' => "Verify {$actions} of {$objects} by {$actors}",
            'description' => "Test basic functionality: {$actions} {$objects}",
            'preconditions' => [
                "User is logged in",
                "Required permissions are granted",
                "{$objects} exists in the system"
            ],
            'test_steps' => [
                ["step" => 1, "action" => "Navigate to {$objects} management page", "expectedResult" => "Page loads successfully"],
                ["step" => 2, "action" => "Initiate {$actions} action", "expectedResult" => "Action form/dialog appears"],
                ["step" => 3, "action" => "Complete the {$actions} process", "expectedResult" => "{$actions} is successful"],
                ["step" => 4, "action" => "Verify the {$objects} is updated", "expectedResult" => "Changes are saved and visible"]
            ],
            'expected_result' => ucfirst($actions) . " operation completes successfully",
            'priority' => 'High',
            'status' => 'Not Tested'
        ];
    }
    
    /**
     * Check if edge case should be generated
     */
    private function shouldGenerateEdgeCase($components) {
        return count($components['conditions']) > 0 || count($components['constraints']) > 1;
    }
    
    /**
     * Generate edge case test
     */
    private function generateEdgeTestCase($components) {
        return [
            'test_id' => $this->requirementCode . '.T2',
            'requirement_code' => $this->requirementCode,
            'requirement_type' => $this->requirementType,
            'test_type' => 'Edge Case',
            'title' => "Verify {$this->requirementCode} with boundary values",
            'description' => "Test edge cases and boundary conditions",
            'preconditions' => [
                "User is logged in",
                "System is in a valid state"
            ],
            'test_steps' => [
                ["step" => 1, "action" => "Prepare edge case data (empty, max size, min size, etc.)", "expectedResult" => "Data is prepared"],
                ["step" => 2, "action" => "Execute the operation with edge case data", "expectedResult" => "System handles edge case gracefully"],
                ["step" => 3, "action" => "Verify system behavior", "expectedResult" => "System returns appropriate result or error"]
            ],
            'expected_result' => "System handles edge cases correctly",
            'priority' => 'Medium',
            'status' => 'Not Tested'
        ];
    }
    
    /**
     * Check if negative case should be generated
     */
    private function shouldGenerateNegativeCase($components) {
        return true; // Generate negative case for all requirements
    }
    
    /**
     * Generate negative/error case test
     */
    private function generateNegativeTestCase($components) {
        return [
            'test_id' => $this->requirementCode . '.T3',
            'requirement_code' => $this->requirementCode,
            'requirement_type' => $this->requirementType,
            'test_type' => 'Negative',
            'title' => "Verify error handling for {$this->requirementCode}",
            'description' => "Test system behavior when operation fails or invalid data is provided",
            'preconditions' => [
                "User is logged in",
                "Test with invalid or missing data"
            ],
            'test_steps' => [
                ["step" => 1, "action" => "Attempt operation with invalid/missing data", "expectedResult" => "System captures error"],
                ["step" => 2, "action" => "Verify error message is displayed", "expectedResult" => "Clear error message is shown"],
                ["step" => 3, "action" => "Verify system remains in stable state", "expectedResult" => "No data corruption or system crash"]
            ],
            'expected_result' => "System handles errors gracefully with appropriate feedback",
            'priority' => 'High',
            'status' => 'Not Tested'
        ];
    }
    
    /**
     * Check if requirement is performance-related (NFR)
     */
    private function isPerformanceRequirement($text) {
        $perfKeywords = ['performance', 'response time', 'load time', 'throughput', 'latency', 'speed', 'concurrent', 'capacity'];
        
        foreach ($perfKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate performance test case for NFR
     */
    private function generatePerformanceTestCase($components) {
        return [
            'test_id' => $this->requirementCode . '.T4',
            'requirement_code' => $this->requirementCode,
            'requirement_type' => $this->requirementType,
            'test_type' => 'Performance',
            'title' => "Verify {$this->requirementCode} performance requirements",
            'description' => "Test performance, response time, and load handling",
            'preconditions' => [
                "System is configured for load testing",
                "Performance monitoring tools are active"
            ],
            'test_steps' => [
                ["step" => 1, "action" => "Start performance monitoring", "expectedResult" => "Monitoring tools are active"],
                ["step" => 2, "action" => "Execute operation under normal load", "expectedResult" => "Operation completes within acceptable time"],
                ["step" => 3, "action" => "Execute operation under peak load", "expectedResult" => "Performance meets SLA requirements"],
                ["step" => 4, "action" => "Analyze performance metrics", "expectedResult" => "All metrics are within acceptable range"]
            ],
            'expected_result' => "System performs according to performance requirements",
            'priority' => 'High',
            'status' => 'Not Tested'
        ];
    }
    
    /**
     * Get generated test cases
     */
    public function getTestCases() {
        return $this->testCases;
    }
    
    /**
     * Get test count
     */
    public function getTestCount() {
        return count($this->testCases);
    }
}
?>
