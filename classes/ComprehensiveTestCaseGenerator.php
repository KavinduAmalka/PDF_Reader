<?php
/**
 * ComprehensiveTestCaseGenerator - Expert QA Test Case Generation
 * Generates comprehensive, structured test cases for ALL requirements in SRS
 * Output: JSON format compatible with UAT system and external tools
 */
class ComprehensiveTestCaseGenerator {
    private $sections;
    private $testCases = [];
    
    public function __construct($sections) {
        $this->sections = $sections;
    }
    
    /**
     * Generate comprehensive test cases for ALL requirements
     * Including separate test cases for each sub-requirement (e.g., FR-20.01, FR-20.02)
     * @return array Array of test cases organized by requirement (main + sub-requirements)
     */
    public function generateAllTestCases() {
        $this->testCases = [];
        
        // Generate test cases for functional requirements
        if (isset($this->sections['subsections']['functional'])) {
            foreach ($this->sections['subsections']['functional'] as $code => $reqData) {
                $this->generateTestCasesWithSubrequirements($code, $reqData, 'FR');
            }
        }
        
        // Generate test cases for non-functional requirements
        if (isset($this->sections['subsections']['non_functional'])) {
            foreach ($this->sections['subsections']['non_functional'] as $code => $reqData) {
                $this->generateTestCasesWithSubrequirements($code, $reqData, 'NFR');
            }
        }
        
        return $this->testCases;
    }
    
    /**
     * Generate test cases for requirement and its sub-requirements (e.g., FR-20.01, FR-20.02)
     */
    private function generateTestCasesWithSubrequirements($code, $reqData, $type) {
        // Check if requirement has sub-requirements in the content array
        if (isset($reqData['content']) && is_array($reqData['content']) && !empty($reqData['content'])) {
            // Generate separate test cases for each sub-requirement
            foreach ($reqData['content'] as $subItem) {
                if (isset($subItem['code'])) {
                    // Sub-requirement like FR-20.01, FR-20.02
                    $subCode = $subItem['code'];
                    // Use sub-requirement specific description as title
                    $subTitle = $this->extractSpecificTitle($subItem['description'] ?? '');
                    $subText = $subItem['description'] ?? '';
                    
                    // Generate test cases for this sub-requirement
                    $tests = $this->generateTestCasesForRequirement($subCode, $subTitle, $subText, $type, $reqData, $subItem);
                    $this->testCases[$subCode] = $tests;
                }
            }
        } else {
            // No sub-requirements, generate for main requirement
            $reqText = $this->extractRequirementText($reqData);
            $reqTitle = $reqData['title'] ?? $code;
            $tests = $this->generateTestCasesForRequirement($code, $reqTitle, $reqText, $type, $reqData, null);
            $this->testCases[$code] = $tests;
        }
    }
    
    /**
     * Extract specific actionable title from requirement description
     */
    private function extractSpecificTitle($text) {
        if (empty($text)) return 'System Requirement';
        
        // Try to extract action verb + object pattern
        if (preg_match('/system\s+shall\s+([\w\s]+?)\s+(?:using|via|through|by|with|when|if|that|with|to)/i', $text, $matches)) {
            $title = trim($matches[1]);
            if (strlen($title) > 3 && strlen($title) < 50) {
                return ucfirst($title);
            }
        }
        
        // Try to find main action
        if (preg_match('/\b(validate|verify|allow|implement|maintain|authenticate|encrypt|send|upload|process|manage|generate)\s+([\w\s]+?)\b/i', $text, $matches)) {
            $action = ucfirst($matches[1]);
            $object = ucfirst(trim($matches[2]));
            if (strlen($object) > 2 && strlen($object) < 30) {
                return "$action $object";
            }
        }
        
        // Extract first meaningful phrase (first 5-8 words after "shall")
        if (preg_match('/shall\s+(.+?)[\.,;]/i', $text, $matches)) {
            $phrase = trim($matches[1]);
            $words = preg_split('/\s+/', $phrase);
            $title = implode(' ', array_slice($words, 0, min(5, count($words))));
            if (strlen($title) > 5) {
                return ucfirst($title);
            }
        }
        
        // Fallback: use first meaningful words
        $firstSentence = explode('.', $text)[0];
        $words = preg_split('/\s+/', trim($firstSentence));
        $title = implode(' ', array_slice($words, 0, min(7, count($words))));
        
        return strlen($title) > 5 ? $title : 'System Requirement';
    }
    
    /**
     * Generate 3-4 test cases for a single requirement or sub-requirement
     */
    private function generateTestCasesForRequirement($code, $title, $text, $type, $reqData, $subItem = null) {
        $tests = [];
        
        // Extract contextual details from requirement text for more specific test generation
        $context = $this->extractRequirementContext($text, $code);
        
        // 1. POSITIVE TEST CASE - Happy path
        $tests[] = $this->generatePositiveTestCase($code, $title, $text, $type, $reqData, $context);
        
        // 2. NEGATIVE TEST CASE - Error handling
        $tests[] = $this->generateNegativeTestCase($code, $title, $text, $type, $reqData, $context);
        
        // 3. EDGE CASE TEST - Boundary conditions
        $tests[] = $this->generateEdgeCaseTestCase($code, $title, $text, $type, $reqData, $context);
        
        // 4. PERFORMANCE TEST (for NFR) or Additional scenario (for FR)
        if ($type === 'NFR') {
            $tests[] = $this->generatePerformanceTestCase($code, $title, $text, $reqData);
        } else {
            $tests[] = $this->generateAdditionalScenarioTestCase($code, $title, $text, $reqData, $context);
        }
        
        return $tests;
    }
    
    /**
     * Extract requirement context for more intelligent test generation
     */
    private function extractRequirementContext($text, $code) {
        $context = [
            'actions' => $this->extractActions($text),
            'objects' => $this->extractObjects($text),
            'constraints' => $this->extractConstraints($text),
            'validations' => $this->extractValidations($text),
            'keywords' => $this->extractKeywordsFromText($text),
            'fields' => $this->extractFields($text)
        ];
        return $context;
    }
    
    /**
     * Extract keywords from requirement text
     */
    private function extractKeywordsFromText($text) {
        $keywords = [];
        
        // Extract key system behaviors
        if (preg_match('/(?:validate|verify|check)/i', $text)) {
            $keywords[] = 'validation';
        }
        if (preg_match('/(?:encrypt|hash|secure|security)/i', $text)) {
            $keywords[] = 'security';
        }
        if (preg_match('/(?:error|fail|exception|retry)/i', $text)) {
            $keywords[] = 'error_handling';
        }
        if (preg_match('/(?:notify|send|email|alert|message)/i', $text)) {
            $keywords[] = 'notification';
        }
        if (preg_match('/(?:persist|save|store|database|record)/i', $text)) {
            $keywords[] = 'data_persistence';
        }
        
        return $keywords;
    }
    
    /**
     * Extract objects/entities mentioned in requirement
     */
    private function extractObjects($text) {
        $objects = [];
        
        // Common system objects
        $patterns = [
            'user', 'password', 'email', 'document', 'file', 'form', 'login', 'account',
            'database', 'record', 'session', 'token', 'permission', 'role', 'profile',
            'notification', 'report', 'template', 'configuration', 'audit', 'log'
        ];
        
        foreach ($patterns as $obj) {
            if (preg_match('/\b' . preg_quote($obj) . '\b/i', $text)) {
                $objects[] = $obj;
            }
        }
        
        return array_unique($objects);
    }
    
    /**
     * Extract constraints/requirements from text
     */
    private function extractConstraints($text) {
        $constraints = [];
        
        // Look for specific numeric constraints
        if (preg_match('/(\d+)\s*(seconds?|minutes?|hours?|days?|characters?|fields?|attempts?)/i', $text, $matches)) {
            $constraints[] = $matches[0];
        }
        
        if (preg_match('/maximum|minimum|must|shall|required|forbidden/i', $text)) {
            preg_match_all('/(maximum|minimum|must|shall|required|forbidden|cannot|must not)\s+([^.!?;]+)/i', $text, $matches);
            $constraints = array_merge($constraints, $matches[0]);
        }
        
        return array_slice(array_unique($constraints), 0, 3);
    }
    
    /**
     * Extract validation rules from text
     */
    private function extractValidations($text) {
        $validations = [];
        
        if (preg_match('/(?:valid|invalid|format|pattern|length|type)/i', $text)) {
            $validations[] = 'format_validation';
        }
        
        if (preg_match('/(?:secure|encrypt|hash|password|authorization|permission)/i', $text)) {
            $validations[] = 'security_validation';
        }
        
        if (preg_match('/(?:unique|duplicate|exist|integrity|consistent)/i', $text)) {
            $validations[] = 'data_integrity';
        }
        
        if (preg_match('/(?:timeout|retry|attempt|error|fail)/i', $text)) {
            $validations[] = 'error_handling';
        }
        
        return $validations;
    }
    
    /**
     * Extract input fields/parameters from text
     */
    private function extractFields($text) {
        $fields = [];
        
        // Look for field-like patterns
        $patterns = ['username', 'password', 'email', 'phone', 'address', 'name', 'id', 'code', 'status', 'date', 'time'];
        
        foreach ($patterns as $field) {
            if (preg_match('/\b' . preg_quote($field) . '\b/i', $text)) {
                $fields[] = $field;
            }
        }
        
        return array_unique($fields);
    }
    
    /**
     * Extract requirement text from data structure
     */
    private function extractRequirementText($reqData) {
        if (is_array($reqData)) {
            if (isset($reqData['content']) && is_array($reqData['content'])) {
                // Combine all descriptions from sub-items
                $texts = [];
                foreach ($reqData['content'] as $item) {
                    if (isset($item['description']) && !empty($item['description'])) {
                        $texts[] = $item['description'];
                    }
                }
                return implode(' ', $texts);
            } elseif (isset($reqData['content'])) {
                return $reqData['content'];
            } elseif (isset($reqData['title'])) {
                return $reqData['title'];
            }
        }
        return '';
    }
    
    /**
     * Generate POSITIVE test case (happy path)
     */
    private function generatePositiveTestCase($code, $title, $text, $type, $reqData, $context = []) {
        $personas = $this->extractPersonas($text);
        $primaryPersona = $personas[0] ?? 'User';
        $actions = $context['actions'] ?? $this->extractActions($text);
        $preconditions = $this->generatePreconditions($code, $text, $primaryPersona);
        
        // Generate requirement-specific test name
        $testName = $this->generateSpecificTestName($title, 'Positive', $context);
        
        return [
            'requirement_id' => $code,
            'requirement_type' => $type,
            'test_id' => $code . '_TC_001',
            'test_name' => '[POSITIVE] ' . $testName . ' - Happy Path',
            'test_type' => 'Positive',
            'description' => 'Verify that ' . $primaryPersona . ' can successfully ' . strtolower($testName) . ' with valid inputs',
            'user_persona' => $primaryPersona,
            'priority' => 'P0 - Critical',
            'pre_conditions' => $preconditions,
            'test_steps' => $this->generateContextualTestSteps($code, $text, $primaryPersona, 'positive', $context),
            'expected_result' => $this->generateContextualExpectedResult($code, $text, 'SUCCESS', $context),
            'acceptance_criteria' => [
                '✓ All required actions completed successfully',
                '✓ System accepts valid input',
                '✓ ' . $primaryPersona . ' receives confirmation',
                '✓ Data is persisted correctly'
            ],
            'test_data' => [
                'input_type' => 'valid',
                'sample_values' => $this->generateSampleData($text, 'valid')
            ],
            'automation_ready' => true
        ];
    }
    
    /**
     * Generate requirement-specific test name
     */
    private function generateSpecificTestName($title, $type, $context) {
        // Use title as base
        $baseName = trim($title);
        
        // Add action context if available
        if (!empty($context['actions']) && $type === 'Positive') {
            $action = ucfirst($context['actions'][0] ?? '');
            if ($action && strlen($action) > 2) {
                return $action . ' - ' . $baseName;
            }
        }
        
        return $baseName;
    }
    
    /**
     * Generate contextual test steps based on requirement analysis
     */
    private function generateContextualTestSteps($code, $text, $persona, $stepType, $context) {
        if ($stepType === 'positive') {
            return $this->generatePositiveContextualSteps($code, $text, $persona, $context);
        } elseif ($stepType === 'negative') {
            return $this->generateNegativeContextualSteps($code, $text, $persona, $context);
        } elseif ($stepType === 'edge') {
            return $this->generateEdgeContextualSteps($code, $text, $persona, $context);
        } elseif ($stepType === 'alternate') {
            return $this->generateAlternateContextualSteps($code, $text, $persona, $context);
        }
        return [];
    }
    
    /**
     * Generate positive test steps based on requirement context
     */
    private function generatePositiveContextualSteps($code, $text, $persona, $context) {
        $steps = [];
        $actions = $context['actions'] ?? [];
        $fields = $context['fields'] ?? [];
        $objects = $context['objects'] ?? [];
        
        // Step 1: Navigation/Setup
        if (!empty($objects)) {
            $object = reset($objects);
            $steps[] = '1. ' . $persona . ' navigates to the ' . ucfirst($object) . ' module/page';
        } else {
            $steps[] = '1. ' . $persona . ' navigates to the relevant module/page';
        }
        
        // Step 2: Input data
        if (!empty($fields)) {
            $fieldList = implode(', ', array_slice($fields, 0, 3));
            $steps[] = '2. ' . $persona . ' enters valid data in ' . $fieldList . ' field(s)';
        } else {
            $steps[] = '2. ' . $persona . ' provides all required valid information';
        }
        
        // Step 3: Trigger action
        if (!empty($actions)) {
            $action = reset($actions);
            $steps[] = '3. ' . $persona . ' clicks "' . ucfirst($action) . '" button or triggers the ' . $action . ' action';
        } else {
            $steps[] = '3. ' . $persona . ' submits the form or initiates the process';
        }
        
        // Step 4: System validation
        if (!empty($context['validations'])) {
            $validation = reset($context['validations']);
            $steps[] = '4. System validates ' . str_replace('_', ' ', $validation) . ' successfully';
        } else {
            $steps[] = '4. System validates input and processes request';
        }
        
        // Step 5: Business logic execution
        if (preg_match('/send|email|notify/i', $text)) {
            $steps[] = '5. System processes the request and sends notification/email if required';
        } else {
            $steps[] = '5. System executes business logic and updates relevant data';
        }
        
        // Step 6: Confirmation
        $steps[] = '6. ' . $persona . ' receives success confirmation message with transaction/action ID';
        
        return $steps;
    }
    
    /**
     * Generate negative test steps based on requirement context
     */
    private function generateNegativeContextualSteps($code, $text, $persona, $context) {
        $steps = [];
        $objects = $context['objects'] ?? [];
        $fields = $context['fields'] ?? [];
        
        if (!empty($objects)) {
            $object = reset($objects);
            $steps[] = '1. ' . $persona . ' navigates to ' . ucfirst($object) . ' module';
        } else {
            $steps[] = '1. ' . $persona . ' navigates to the relevant form/page';
        }
        
        if (!empty($fields)) {
            $field = reset($fields);
            $steps[] = '2. ' . $persona . ' enters invalid/missing data in ' . ucfirst($field) . ' field';
        } else {
            $steps[] = '2. ' . $persona . ' attempts action with invalid or incomplete data';
        }
        
        $steps[] = '3. ' . $persona . ' submits the form';
        $steps[] = '4. System detects validation error(s)';
        $steps[] = '5. System displays specific error message highlighting invalid field(s)';
        $steps[] = '6. ' . $persona . ' is prevented from proceeding; data is NOT saved';
        
        return $steps;
    }
    
    /**
     * Generate edge case test steps based on requirement context
     */
    private function generateEdgeContextualSteps($code, $text, $persona, $context) {
        $steps = [];
        $constraints = $context['constraints'] ?? [];
        $objects = $context['objects'] ?? [];
        
        if (!empty($objects)) {
            $object = reset($objects);
            $steps[] = '1. ' . $persona . ' accesses ' . $object . ' at boundary condition (min/max/deadline)';
        } else {
            $steps[] = '1. ' . $persona . ' attempts operation at system boundary (max/min limits)';
        }
        
        if (!empty($constraints)) {
            $steps[] = '2. ' . $persona . ' tests with constraint: ' . reset($constraints);
        } else {
            $steps[] = '2. ' . $persona . ' provides input at maximum or minimum acceptable limit';
        }
        
        $steps[] = '3. System receives boundary value input';
        $steps[] = '4. System validates and processes boundary condition correctly';
        $steps[] = '5. System handles concurrent/simultaneous requests if applicable';
        $steps[] = '6. System remains stable and responsive within acceptable performance parameters';
        
        return $steps;
    }
    
    /**
     * Generate alternate flow test steps based on requirement context
     */
    private function generateAlternateContextualSteps($code, $text, $persona, $context) {
        $steps = [
            '1. ' . $persona . ' initiates alternate or exceptional workflow path',
            '2. System validates prerequisites for alternate flow',
            '3. System routes request through alternate business logic',
            '4. System maintains data consistency throughout alternate path',
            '5. System notifies relevant stakeholders or systems of alternate flow execution',
            '6. Audit trail records alternate flow with timestamp and ' . $persona . ' details'
        ];
        return $steps;
    }
    
    /**
     * Generate contextual expected result
     */
    private function generateContextualExpectedResult($code, $text, $scenario, $context) {
        $objects = $context['objects'] ?? [];
        $object = !empty($objects) ? reset($objects) : 'system';
        
        if ($scenario === 'SUCCESS') {
            if (preg_match('/persist|save|store|database|record/i', $text)) {
                return 'System successfully processes request, persists data to database, and displays confirmation to ' . $object . '.';
            } elseif (preg_match('/send|email|notify|alert/i', $text)) {
                return 'System successfully processes request, sends appropriate notification/email, and confirms completion to user.';
            } else {
                return 'System successfully processes ' . $code . ' requirement, updates state, and confirms completion.';
            }
        } elseif ($scenario === 'FAILURE') {
            return 'System rejects invalid input, displays specific error message, prevents data corruption, and maintains system integrity.';
        } elseif ($scenario === 'BOUNDARY_PASS') {
            return 'System handles boundary condition correctly, processes request within limits, and maintains performance tolerance.';
        }
        return 'System behavior aligns with ' . $code . ' specification.';
    }
    
    /**
     * Generate NEGATIVE test case (error handling)
     */
    private function generateNegativeTestCase($code, $title, $text, $type, $reqData, $context = []) {
        $personas = $this->extractPersonas($text);
        $primaryPersona = $personas[0] ?? 'User';
        
        $testName = $this->generateSpecificTestName($title, 'Negative', $context);
        
        return [
            'requirement_id' => $code,
            'requirement_type' => $type,
            'test_id' => $code . '_TC_002',
            'test_name' => '[NEGATIVE] ' . $testName . ' - Invalid Input',
            'test_type' => 'Negative',
            'description' => 'Verify that system properly rejects invalid input and prevents unauthorized access for ' . $testName,
            'user_persona' => $primaryPersona,
            'priority' => 'P1 - High',
            'pre_conditions' => $this->generatePreconditions($code, $text, $primaryPersona),
            'test_steps' => $this->generateContextualTestSteps($code, $text, $primaryPersona, 'negative', $context),
            'expected_result' => $this->generateContextualExpectedResult($code, $text, 'FAILURE', $context),
            'acceptance_criteria' => [
                '✓ System rejects invalid input gracefully',
                '✓ Appropriate error message is displayed',
                '✓ System does not process invalid data',
                '✓ Security is maintained'
            ],
            'test_data' => [
                'input_type' => 'invalid',
                'error_scenarios' => [
                    'Missing required fields',
                    'Invalid format or data type',
                    'Unauthorized access attempt',
                    'Null or empty input'
                ]
            ],
            'automation_ready' => true
        ];
    }
    
    /**
     * Generate EDGE CASE test case (boundary conditions)
     */
    private function generateEdgeCaseTestCase($code, $title, $text, $type, $reqData, $context = []) {
        $personas = $this->extractPersonas($text);
        $primaryPersona = $personas[0] ?? 'User';
        
        $testName = $this->generateSpecificTestName($title, 'Edge', $context);
        
        return [
            'requirement_id' => $code,
            'requirement_type' => $type,
            'test_id' => $code . '_TC_003',
            'test_name' => '[EDGE CASE] ' . $testName . ' - Boundary Conditions',
            'test_type' => 'Edge Case',
            'description' => 'Verify behavior under boundary conditions and stress scenarios for ' . $testName,
            'user_persona' => $primaryPersona,
            'priority' => 'P1 - High',
            'pre_conditions' => $this->generatePreconditions($code, $text, $primaryPersona),
            'test_steps' => $this->generateContextualTestSteps($code, $text, $primaryPersona, 'edge', $context),
            'expected_result' => $this->generateContextualExpectedResult($code, $text, 'BOUNDARY_PASS', $context),
            'acceptance_criteria' => [
                '✓ System handles boundary conditions correctly',
                '✓ Performance remains acceptable under load',
                '✓ Data integrity is maintained at limits',
                '✓ Concurrent operations are handled safely'
            ],
            'test_data' => [
                'input_type' => 'boundary',
                'boundary_values' => [
                    'max_length' => 'Test with maximum allowed characters',
                    'min_length' => 'Test with minimum allowed characters',
                    'max_file_size' => 'Test with maximum file size',
                    'concurrent_users' => 'Test with multiple simultaneous users'
                ]
            ],
            'automation_ready' => true
        ];
    }
    
    /**
     * Generate PERFORMANCE test case (for NFR)
     */
    private function generatePerformanceTestCase($code, $title, $text, $reqData) {
        return [
            'requirement_id' => $code,
            'requirement_type' => 'NFR',
            'test_id' => $code . '_TC_004',
            'test_name' => '[PERFORMANCE] ' . $this->shortenTitle($title),
            'test_type' => 'Performance',
            'description' => 'Verify system meets performance and load requirements',
            'user_persona' => 'QA Performance Engineer',
            'priority' => 'P1 - High',
            'pre_conditions' => [
                'Test environment is isolated and stable',
                'Load testing tools are configured',
                'Baseline metrics are established'
            ],
            'test_steps' => [
                '1. Set up load testing environment with realistic data volume',
                '2. Initiate concurrent user sessions matching expected load',
                '3. Monitor system response times and resource utilization',
                '4. Execute operations as per requirement specification',
                '5. Record metrics: response time, throughput, error rate',
                '6. Validate against defined performance criteria',
                '7. Analyze bottlenecks if thresholds exceeded'
            ],
            'expected_result' => $this->extractPerformanceExpectation($text),
            'acceptance_criteria' => [
                '✓ Response time meets SLA',
                '✓ Throughput meets minimum requirement',
                '✓ Error rate below threshold',
                '✓ Resource utilization acceptable',
                '✓ No memory leaks detected'
            ],
            'test_data' => [
                'load_profile' => 'Realistic user distribution',
                'data_volume' => 'Production-equivalent dataset',
                'metrics_to_track' => [
                    'Response Time (ms)',
                    'Throughput (requests/sec)',
                    'Error Rate (%)',
                    'CPU Usage (%)',
                    'Memory Usage (MB)'
                ]
            ],
            'automation_ready' => true
        ];
    }
    
    /**
     * Generate ADDITIONAL SCENARIO test case (for FR when not NFR)
     */
    private function generateAdditionalScenarioTestCase($code, $title, $text, $reqData, $context = []) {
        $personas = $this->extractPersonas($text);
        
        $testName = $this->generateSpecificTestName($title, 'Alternate', $context);
        
        return [
            'requirement_id' => $code,
            'requirement_type' => 'FR',
            'test_id' => $code . '_TC_004',
            'test_name' => '[ALTERNATE FLOW] ' . $testName . ' - Alternate Scenario',
            'test_type' => 'Alternate Flow',
            'description' => 'Verify alternate scenarios, exception handling, or workflow variations for ' . $testName,
            'user_persona' => count($personas) > 1 ? $personas[1] : 'Manager',
            'priority' => 'P2 - Medium',
            'pre_conditions' => $this->generatePreconditions($code, $text, count($personas) > 1 ? $personas[1] : 'Manager'),
            'test_steps' => $this->generateContextualTestSteps($code, $text, count($personas) > 1 ? $personas[1] : 'Manager', 'alternate', $context),
            'expected_result' => 'System successfully processes alternate flow, maintains consistency, and notifies relevant parties',
            'acceptance_criteria' => [
                '✓ Alternate workflow completes successfully',
                '✓ System maintains data consistency',
                '✓ All stakeholders notified appropriately',
                '✓ Audit trail records alternate flow execution'
            ],
            'test_data' => [
                'input_type' => 'valid',
                'scenario' => !empty($context['actions']) ? 'Alternate ' . reset($context['actions']) . ' flow' : 'Alternate business process flow'
            ],
            'automation_ready' => true
        ];
    }
    
    /**
     * Helper: Extract personas from requirement text
     */
    private function extractPersonas($text) {
        $personas = [];
        $personaPatterns = [
            'author' => 'Author|Corresponding Author',
            'admin' => 'Administrator|Admin|System Administrator',
            'manager' => 'Manager|Editorial Manager|Track Manager|Chief Editor',
            'editor' => 'Editor|Track Editor',
            'reviewer' => 'Reviewer',
            'user' => 'User|Guest|Participant',
            'system' => 'System'
        ];
        
        $text_lower = strtolower($text);
        
        foreach ($personaPatterns as $persona => $pattern) {
            if (preg_match('/' . $pattern . '/i', $text)) {
                $personas[] = ucfirst($persona);
            }
        }
        
        return !empty($personas) ? $personas : ['User'];
    }
    
    /**
     * Helper: Extract action verbs from requirement
     */
    private function extractActions($text) {
        $actions = [];
        $actionPatterns = [
            'upload' => 'upload|submit',
            'download' => 'download|export',
            'register' => 'register|sign up',
            'login' => 'login|authenticate',
            'approve' => 'approve|approve|accept',
            'reject' => 'reject|deny|decline',
            'send' => 'send|transmit|email',
            'create' => 'create|generate|make',
            'delete' => 'delete|remove|discard'
        ];
        
        $text_lower = strtolower($text);
        
        foreach ($actionPatterns as $action => $pattern) {
            if (preg_match('/' . $pattern . '/i', $text)) {
                $actions[] = $action;
            }
        }
        
        return $actions;
    }
    
    /**
     * Helper: Generate preconditions
     */
    private function generatePreconditions($code, $text, $persona) {
        $preconditions = [
            $persona . ' is logged in',
            'User has required permissions',
            'System is operational',
            'Database is accessible'
        ];
        
        // Add specific preconditions based on requirement text
        if (preg_match('/upload|submit/i', $text)) {
            $preconditions[] = 'Valid file/document is available';
            $preconditions[] = 'File meets format requirements';
        }
        
        if (preg_match('/approve|reject/i', $text)) {
            $preconditions[] = 'Document is in pending review status';
            $preconditions[] = 'Reviewer has access rights';
        }
        
        if (preg_match('/payment|register/i', $text)) {
            $preconditions[] = 'Valid account exists';
            $preconditions[] = 'Payment method is configured';
        }
        
        return $preconditions;
    }
    
    /**
     * Helper: Generate positive test steps
     */
    private function generatePositiveTestSteps($code, $text, $persona, $actions) {
        $steps = [
            '1. ' . $persona . ' navigates to the relevant module/page',
            '2. ' . $persona . ' provides all required information/uploads valid document',
            '3. System validates input and provides confirmation'
        ];
        
        if (in_array('upload', $actions)) {
            $steps[] = '4. System processes file upload successfully';
            $steps[] = '5. File is stored in appropriate location';
            $steps[] = '6. Confirmation notification is sent';
        }
        
        if (in_array('send', $actions) || preg_match('/email|notify/i', $text)) {
            $steps[] = '4. System generates notification/email';
            $steps[] = '5. Notification is sent to recipient(s)';
            $steps[] = '6. Delivery is confirmed';
        }
        
        if (empty(array_filter($steps, fn($s) => preg_match('/^[4-9]/', $s)))) {
            $steps[] = '4. System processes request successfully';
            $steps[] = '5. Data is saved to database';
            $steps[] = '6. Success confirmation is displayed to ' . $persona;
        }
        
        return $steps;
    }
    
    /**
     * Helper: Generate negative test steps
     */
    private function generateNegativeTestSteps($code, $text, $persona) {
        return [
            '1. ' . $persona . ' attempts to perform action with invalid data',
            '2. System detects invalid input/missing required fields',
            '3. System displays appropriate error message',
            '4. ' . $persona . ' is prevented from proceeding',
            '5. Invalid data is NOT processed or stored',
            '6. System remains in stable state'
        ];
    }
    
    /**
     * Helper: Generate edge case test steps
     */
    private function generateEdgeCaseTestSteps($code, $text, $persona) {
        return [
            '1. ' . $persona . ' attempts operation at boundary condition (max/min/deadline)',
            '2. System processes request at stated boundary',
            '3. System handles concurrent access attempt',
            '4. Large dataset/file is processed',
            '5. System validates resource constraints',
            '6. System responds within acceptable parameters'
        ];
    }
    
    /**
     * Helper: Generate alternate flow steps
     */
    private function generateAlternateFlowSteps($code, $text) {
        return [
            '1. User initiates alternate workflow path',
            '2. System validates alternate scenario requirements',
            '3. System processes alternate flow logic',
            '4. System maintains data consistency across alternate path',
            '5. System notifies stakeholders per alternate requirements',
            '6. Audit trail records alternate flow execution'
        ];
    }
    
    /**
     * Helper: Generate expected result
     */
    private function generateExpectedResult($code, $text, $scenario) {
        if ($scenario === 'SUCCESS') {
            return 'System successfully processes the requirement as specified in ' . $code . '. User receives confirmation, data is stored, and appropriate notifications are sent.';
        } elseif ($scenario === 'FAILURE') {
            return 'System rejects invalid input with appropriate error message. No invalid data is processed. System maintains security and integrity.';
        } elseif ($scenario === 'BOUNDARY_PASS') {
            return 'System handles boundary conditions correctly. Operation succeeds at limits. Concurrent access is managed properly.';
        }
        return 'System behavior aligns with ' . $code . ' specification';
    }
    
    /**
     * Helper: Extract performance expectation from NFR text
     */
    private function extractPerformanceExpectation($text) {
        if (preg_match('/(\d+)\s*(sec|second|minute|min|hour|ms|millisecond)/i', $text, $matches)) {
            $value = $matches[1];
            $unit = strtolower($matches[2]);
            return sprintf('System completes operation within %s %s', $value, $unit);
        }
        
        if (preg_match('/(\d+)\s*(request|user|connection|concurrent|load)/i', $text, $matches)) {
            $value = $matches[1];
            return sprintf('System handles up to %s concurrent operations', $value);
        }
        
        return 'System meets performance requirements as specified in SRS';
    }
    
    /**
     * Helper: Generate sample test data
     */
    private function generateSampleData($text, $dataType) {
        $data = [];
        
        if ($dataType === 'valid') {
            if (preg_match('/email|mail/i', $text)) {
                $data['email'] = 'author@example.com';
            }
            if (preg_match('/file|upload|document/i', $text)) {
                $data['file'] = 'sample_document.pdf';
                $data['file_size'] = '2.5 MB';
            }
            if (preg_match('/payment|amount|fee/i', $text)) {
                $data['amount'] = '$100.00';
                $data['currency'] = 'USD';
            }
            if (preg_match('/name|title/i', $text)) {
                $data['name'] = 'Sample Document Title';
            }
            if (preg_match('/code|id|reference/i', $text)) {
                $data['reference_id'] = 'FR-001-2024';
            }
        }
        
        return !empty($data) ? $data : ['valid_input' => 'Standard test data'];
    }
    
    /**
     * Helper: Shorten title for display
     */
    private function shortenTitle($title, $maxLength = 60) {
        if (strlen($title) > $maxLength) {
            return substr($title, 0, $maxLength) . '...';
        }
        return $title;
    }
    
    /**
     * Export all test cases as JSON
     */
    public function exportAsJSON() {
        return json_encode([
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'generator' => 'ComprehensiveTestCaseGenerator',
                'format_version' => '1.0'
            ],
            'summary' => [
                'total_requirements' => count($this->testCases),
                'total_test_cases' => array_sum(array_map('count', $this->testCases))
            ],
            'test_cases' => $this->testCases
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Save test cases to file
     */
    public function saveToFile($filePath) {
        $json = $this->exportAsJSON();
        $result = file_put_contents($filePath, $json);
        return $result !== false ? true : false;
    }
    
    /**
     * Export test cases as CSV
     */
    public function exportAsCSV() {
        $csv = "Requirement ID,Test ID,Test Type,Test Name,Description,User Persona,Priority,Pre-conditions,Test Steps,Expected Result\n";
        
        foreach ($this->testCases as $reqId => $tests) {
            foreach ($tests as $test) {
                $preconditions = is_array($test['pre_conditions']) ? implode('; ', $test['pre_conditions']) : $test['pre_conditions'];
                $testSteps = is_array($test['test_steps']) ? implode('; ', $test['test_steps']) : $test['test_steps'];
                
                $row = [
                    $test['requirement_id'],
                    $test['test_id'],
                    $test['test_type'],
                    $test['test_name'],
                    $test['description'],
                    $test['user_persona'],
                    $test['priority'],
                    $preconditions,
                    $testSteps,
                    $test['expected_result']
                ];
                
                // Escape CSV values
                $row = array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row);
                
                $csv .= implode(',', $row) . "\n";
            }
        }
        
        return $csv;
    }
    
    /**
     * Get test cases formatted for display
     */
    public function getFormattedTestCases() {
        return $this->testCases;
    }
}
