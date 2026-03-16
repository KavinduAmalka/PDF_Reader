<?php

class SVOAnalyzer {
    private $apiKey;
    private $apiUrl;
    private $model;
    private $timeout;
    
    public function __construct() {
        require_once __DIR__ . '/../config.php';
        
        $this->apiKey = GEMINI_API_KEY;
        $this->apiUrl = GEMINI_API_URL;
        $this->model = GEMINI_MODEL;
        $this->timeout = API_TIMEOUT;
    }
    
    public function analyze($requirementText, $requirementCode = '') {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
            return [
                'success' => false,
                'error' => 'API key not configured. Please add your Gemini API key in config.php'
            ];
        }
        
        if (empty($requirementText)) {
            return [
                'success' => false,
                'error' => 'Requirement text is empty'
            ];
        }
        
        try {
            $prompt = $this->buildPrompt($requirementText, $requirementCode);
            $response = $this->callGeminiAPI($prompt);
            
            if ($response['success']) {
                $analysis = $this->parseAnalysis($response['content']);
                $analysis['success'] = true;
                $analysis['requirement_code'] = $requirementCode;
                $analysis['original_text'] = $requirementText;
                
                if (isset($analysis['error'])) {
                    $analysis['raw_ai_response'] = $response['content'];
                }
                
                return $analysis;
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Unknown error'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function buildPrompt($requirementText, $requirementCode) {
        $prompt = "You are an expert IEEE 29148 standards analyst. Analyze the following software functional requirement.\n\n";
        
        if (!empty($requirementCode)) {
            $prompt .= "Requirement Code: $requirementCode\n";
        }
        
        $prompt .= "Requirement Text: \"$requirementText\"\n\n";
        $prompt .= "Provide comprehensive SVO analysis with IEEE 29148 compliance checking in JSON format:\n";
        $prompt .= "{\n";
        $prompt .= "  \"subjects\": [\"subjects/actors - who/what performs the action\"],\n";
        $prompt .= "  \"verbs\": [\"main actions/verbs\"],\n";
        $prompt .= "  \"objects\": [\"objects/targets - what is acted upon\"],\n";
        $prompt .= "  \"modal_verbs\": [\"modal verbs: shall, should, may, must, will, can\"],\n";
        $prompt .= "  \"priority\": \"mandatory|recommended|optional\",\n";
        $prompt .= "  \"ieee_compliance\": {\n";
        $prompt .= "    \"is_compliant\": boolean (true if compliance_score >= 70),\n";
        $prompt .= "    \"compliance_score\": number (0-100),\n";
        $prompt .= "    \"issues\": [\"specific violations only, empty if none\"],\n";
        $prompt .= "    \"recommendations\": [\"specific improvements, empty if score >= 90\"],\n";
        $prompt .= "    \"checks\": {\n";
        $prompt .= "      \"has_modal_verb\": boolean,\n";
        $prompt .= "      \"correct_modal_verb\": boolean,\n";
        $prompt .= "      \"is_clear\": boolean,\n";
        $prompt .= "      \"is_testable\": boolean,\n";
        $prompt .= "      \"is_single_requirement\": boolean,\n";
        $prompt .= "      \"is_unambiguous\": boolean,\n";
        $prompt .= "      \"has_quantifiable_measures\": boolean\n";
        $prompt .= "    }\n";
        $prompt .= "  }\n";
        $prompt .= "}\n\n";
        $prompt .= "=== IEEE 29148 EVALUATION CRITERIA (BE REASONABLE) ===\n\n";
        
        $prompt .= "1. has_modal_verb (PASS if has: shall/should/may/must/will/can):\n";
        $prompt .= "   - TRUE: Contains any modal verb.\n";
        $prompt .= "   - FALSE: No modal verb present.\n\n";

        $prompt .= "2. correct_modal_verb (STRICT PASS if uses IEEE-preferred verbs):\n";
        $prompt .= "   - TRUE: Uses 'shall' (mandatory), 'should' (recommended), or 'may' (optional).\n";
        $prompt .= "   - FALSE: Uses non-preferred verbs like 'must', 'will', 'can'. IEEE prefers 'shall' for requirements.\n\n";

        $prompt .= "3. is_clear (STRICT PASS if well-structured):\n";
        $prompt .= "   - TRUE: Active voice (e.g., 'The system shall...'), clear Subject-Verb-Object (SVO) structure, no vague pronouns like 'it' or 'this'.\n";
        $prompt .= "   - FALSE: Passive voice (e.g., 'The file shall be uploaded...'), uses vague adjectives like 'user-friendly', 'fast', 'robust', or 'efficient'.\n\n";

        $prompt .= "4. is_testable (STRICT PASS if outcome is binary):\n";
        $prompt .= "   - TRUE: A tester can definitively say 'Pass' or 'Fail' based on observation or measurement.\n";
        $prompt .= "   - FALSE: Subjective requirements (e.g., 'The system should never crash' - impossible to prove it will *never* happen, or 'The UI must look good').\n\n";

        $prompt .= "5. is_single_requirement (STRICT PASS if Atomic):\n";
        $prompt .= "   - TRUE: Defines exactly ONE action or behavior.\n";
        $prompt .= "   - FALSE: Combined requirements using 'and', 'also', 'as well as', or 'along with' (e.g., 'The system shall login AND display the dashboard').\n\n";

        $prompt .= "6. is_unambiguous (STRICT PASS if only one interpretation exists):\n";
        $prompt .= "   - TRUE: No room for multiple meanings. Specific terms used.\n";
        $prompt .= "   - FALSE: Uses slashes (e.g., 'User/Admin shall...'), uses 'etc.', 'and/or', or words that depend on personal opinion.\n\n";
                
        $prompt .= "7. has_quantifiable_measures (STRICT - Check for specific measurable criteria):\n";
        $prompt .= "   - TRUE ONLY if requirement contains SPECIFIC quantifiable measures such as:\n";
        $prompt .= "     * Numeric limits: file size (e.g., '10MB max'), time (e.g., '3 seconds'), count (e.g., '5 items')\n";
        $prompt .= "     * Format specifications: (e.g., 'PDF format', 'JPG/PNG only')\n";
        $prompt .= "     * Range constraints: (e.g., 'between 1-100', 'at least 5', 'maximum 20')\n";
        $prompt .= "     * Percentage/ratio: (e.g., '95% accuracy', '100% complete')\n";
        $prompt .= "     * Dates/versions: (e.g., 'by 2024-12-31', 'version 2.0 or higher')\n";
        $prompt .= "     * Physical measurements: (e.g., '1920x1080 resolution', '60 FPS')\n";
        $prompt .= "   - FALSE if:\n";
        $prompt .= "     * Only describes action without specific criteria (e.g., 'upload file', 'display data')\n";
        $prompt .= "     * Uses vague terms like 'quickly', 'efficiently', 'small', 'large' without numbers\n";
        $prompt .= "     * Says 'appropriate', 'suitable', 'reasonable' without defining values\n";
        $prompt .= "   - NOTE: Being testable (is_testable=true) does NOT mean has_quantifiable_measures=true\n";
        $prompt .= "   - EXAMPLE TRUE: 'System shall allow upload of PDF files up to 10MB'\n";
        $prompt .= "   - EXAMPLE FALSE: 'System shall allow upload of files' (no size/format specified)\n\n";
        
        $prompt .= "PRIORITY MAPPING:\n";
        $prompt .= "- 'shall' or 'must' → mandatory\n";
        $prompt .= "- 'should' or 'will' → recommended\n";
        $prompt .= "- 'may' or 'can' → optional\n\n";
        
        $prompt .= "COMPLIANCE SCORE CALCULATION:\n";
        $prompt .= "compliance_score = (number of TRUE checks / 7) * 100\n";
        $prompt .= "Round to nearest integer.\n\n";
        
        $prompt .= "is_compliant = true if compliance_score >= 70, else false\n\n";
        
        $prompt .= "IMPORTANT NOTES:\n";
        $prompt .= "- Be reasonable and practical, but STRICT on quantifiable measures\n";
        $prompt .= "- 'Testable' (can verify it works) ≠ 'Quantifiable' (has specific numbers/limits)\n";
        $prompt .= "- Only add issues for ACTUAL violations, not for minor style preferences\n";
        $prompt .= "- If score >= 90, recommendations should be empty (already excellent)\n";
        $prompt .= "- Return ONLY valid JSON, no markdown, no extra text\n\n";
        
        $prompt .= "EXAMPLE 1 - Good requirement WITH quantifiable measures:\n";
        $prompt .= "Input: 'The system shall allow the Corresponding Author to upload a camera-ready file (PDF format, maximum 10MB) to the system.'\n";
        $prompt .= "{\n";
        $prompt .= "  \"subjects\": [\"system\", \"Corresponding Author\"],\n";
        $prompt .= "  \"verbs\": [\"shall allow\", \"upload\"],\n";
        $prompt .= "  \"objects\": [\"camera-ready file\"],\n";
        $prompt .= "  \"modal_verbs\": [\"shall\"],\n";
        $prompt .= "  \"priority\": \"mandatory\",\n";
        $prompt .= "  \"ieee_compliance\": {\n";
        $prompt .= "    \"is_compliant\": true,\n";
        $prompt .= "    \"compliance_score\": 100,\n";
        $prompt .= "    \"issues\": [],\n";
        $prompt .= "    \"recommendations\": [],\n";
        $prompt .= "    \"checks\": {\n";
        $prompt .= "      \"has_modal_verb\": true,\n";
        $prompt .= "      \"correct_modal_verb\": true,\n";
        $prompt .= "      \"is_clear\": true,\n";
        $prompt .= "      \"is_testable\": true,\n";
        $prompt .= "      \"is_single_requirement\": true,\n";
        $prompt .= "      \"is_unambiguous\": true,\n";
        $prompt .= "      \"has_quantifiable_measures\": true\n";
        $prompt .= "    }\n";
        $prompt .= "  }\n";
        $prompt .= "}\n\n";
        
        $prompt .= "EXAMPLE 2 - Requirement WITHOUT quantifiable measures:\n";
        $prompt .= "Input: 'The system allow the Corresponding Author to upload the camera-ready file to the system.'\n";
        $prompt .= "{\n";
        $prompt .= "  \"subjects\": [\"system\", \"Corresponding Author\"],\n";
        $prompt .= "  \"verbs\": [\"allow\", \"upload\"],\n";
        $prompt .= "  \"objects\": [\"camera-ready file\"],\n";
        $prompt .= "  \"modal_verbs\": [],\n";
        $prompt .= "  \"priority\": \"unknown\",\n";
        $prompt .= "  \"ieee_compliance\": {\n";
        $prompt .= "    \"is_compliant\": false,\n";
        $prompt .= "    \"compliance_score\": 57,\n";
        $prompt .= "    \"issues\": [\n";
        $prompt .= "      \"Missing modal verb (shall/should/may) - priority unclear\",\n";
        $prompt .= "      \"Grammar error: 'allow' should be 'allows' or use 'shall allow'\",\n";
        $prompt .= "      \"No quantifiable measures: missing file format, size limits, or other constraints\"\n";
        $prompt .= "    ],\n";
        $prompt .= "    \"recommendations\": [\n";
        $prompt .= "      \"Use 'shall' for mandatory requirements: 'The system shall allow...'\",\n";
        $prompt .= "      \"Add file specifications: format (e.g., PDF), maximum size (e.g., 10MB)\",\n";
        $prompt .= "      \"Consider adding supported formats and validation criteria\"\n";
        $prompt .= "    ],\n";
        $prompt .= "    \"checks\": {\n";
        $prompt .= "      \"has_modal_verb\": false,\n";
        $prompt .= "      \"correct_modal_verb\": false,\n";
        $prompt .= "      \"is_clear\": true,\n";
        $prompt .= "      \"is_testable\": true,\n";
        $prompt .= "      \"is_single_requirement\": true,\n";
        $prompt .= "      \"is_unambiguous\": true,\n";
        $prompt .= "      \"has_quantifiable_measures\": false\n";
        $prompt .= "    }\n";
        $prompt .= "  }\n";
        $prompt .= "}\n\n";
        $prompt .= "Now analyze the requirement above and return JSON in the EXACT same structure.\n";
        
        return $prompt;
    }
    
    private function callGeminiAPI($prompt) {
        $fullPrompt = "You are an expert software requirements analyst specializing in SVO (Subject-Verb-Object) analysis. You provide structured, accurate analysis of functional requirements.\n\n" . $prompt;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $fullPrompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 2000,
                'topP' => 0.9,
                'topK' => 20
            ]
        ];
        
        $url = $this->apiUrl . $this->model . ':generateContent?key=' . $this->apiKey;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        if (defined('VERIFY_SSL') && VERIFY_SSL === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'HTTP Error: ' . $httpCode;
            
            if (isset($errorData['error']['status'])) {
                $errorMsg .= ' (Status: ' . $errorData['error']['status'] . ')';
            }
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'debug_url' => $url,
                'debug_response' => $response
            ];
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => false,
                'error' => 'Invalid API response format'
            ];
        }
        
        return [
            'success' => true,
            'content' => $result['candidates'][0]['content']['parts'][0]['text']
        ];
    }
    
    private function parseAnalysis($content) {
        $content = trim($content);
        $content = preg_replace('/^```json\s*/s', '', $content);
        $content = preg_replace('/\s*```$/s', '', $content);
        $content = trim($content);
        
        $analysis = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => 'Failed to parse AI response: ' . json_last_error_msg(),
                'raw_response' => $content
            ];
        }
        
        $defaultAnalysis = [
            'subjects' => [],
            'verbs' => [],
            'objects' => [],
            'modal_verbs' => [],
            'priority' => 'unknown',
            'ieee_compliance' => [
                'is_compliant' => false,
                'compliance_score' => 0,
                'issues' => [],
                'recommendations' => [],
                'checks' => [
                    'has_modal_verb' => false,
                    'correct_modal_verb' => false,
                    'is_clear' => false,
                    'is_testable' => false,
                    'is_single_requirement' => false,
                    'is_unambiguous' => false,
                    'has_quantifiable_measures' => false
                ]
            ]
        ];
        
        $result = $this->arrayMergeRecursive($defaultAnalysis, $analysis);
        
        if (isset($result['ieee_compliance'])) {
            if (isset($result['ieee_compliance']['is_compliant'])) {
                $result['ieee_compliance']['is_compliant'] = (bool) $result['ieee_compliance']['is_compliant'];
            }
            
            if (isset($result['ieee_compliance']['compliance_score'])) {
                $result['ieee_compliance']['compliance_score'] = (int) $result['ieee_compliance']['compliance_score'];
            }
            
            if (isset($result['ieee_compliance']['checks']) && is_array($result['ieee_compliance']['checks'])) {
                foreach ($result['ieee_compliance']['checks'] as $key => $value) {
                    $result['ieee_compliance']['checks'][$key] = (bool) $value;
                }
            }
        }
        
        if (isset($result['ieee_compliance']['checks'])) {
            $checks = $result['ieee_compliance']['checks'];
            $passedChecks = count(array_filter($checks, function($v) { return $v === true; }));
            $totalChecks = count($checks);
            
            if ($totalChecks > 0) {
                $calculatedScore = round(($passedChecks / $totalChecks) * 100);
                
                if (!isset($result['ieee_compliance']['compliance_score']) || 
                    $result['ieee_compliance']['compliance_score'] == 0) {
                    $result['ieee_compliance']['compliance_score'] = $calculatedScore;
                }
                
                $result['ieee_compliance']['is_compliant'] = $calculatedScore >= 70;
            }
        }
        
        return $result;
    }
    
    private function arrayMergeRecursive($default, $custom) {
        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($default[$key]) && is_array($default[$key])) {
                $default[$key] = $this->arrayMergeRecursive($default[$key], $value);
            } else {
                $default[$key] = $value;
            }
        }
        return $default;
    }
    
    public function batchAnalyze($requirements) {
        $results = [];
        
        foreach ($requirements as $requirement) {
            $code = $requirement['code'] ?? '';
            $text = $requirement['text'] ?? $requirement['description'] ?? '';
            
            $results[] = $this->analyze($text, $code);
            usleep(100000);
        }
        
        return $results;
    }
    
    public function isAvailable() {
        return !empty($this->apiKey) && $this->apiKey !== 'YOUR_GEMINI_API_KEY_HERE';
    }
}
