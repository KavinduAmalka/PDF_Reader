<?php
/**
 * SRSParser - Parses Software Requirements Specification documents
 * Extracts sections using regex patterns
 */
class SRSParser {
    private $text;
    private $lines;
    private $sections;
    
    public function __construct($text) {
        $this->text = $text;
        $this->lines = explode("\n", $text);
        $this->sections = [];
    }
    
    /**
     * Parse the SRS document and extract all sections
     */
    public function parse() {
        $this->sections = [
            'header' => $this->extractHeader(),
            'introduction' => $this->extractSection('1\.\s*Introduction', '2\.\s*Functional Requirements'),
            'functional_requirements' => $this->extractSection('2\.\s*Functional Requirements', '3\.\s*Non-Functional Requirements'),
            'non_functional_requirements' => $this->extractSection('3\.\s*Non-Functional Requirements', null),
            'subsections' => $this->extractSubsections()
        ];
        
        return $this->sections;
    }
    
    /**
     * Extract header information (Project Name, Module, Client, Tech Stack)
     */
    private function extractHeader() {
        $header = [
            'project_name' => '',
            'module' => '',
            'client' => '',
            'technology_stack' => ''
        ];
        
        // Extract Project Name
        if (preg_match('/Project Name:\s*(.+?)(?:\n|Module:|$)/is', $this->text, $matches)) {
            $value = trim($matches[1] ?? '');
            $value = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $value ?? '');
            $header['project_name'] = trim($value ?? '');
        }
        
        // Extract Module
        if (preg_match('/Module:\s*(.+?)(?:\n|Client:|$)/is', $this->text, $matches)) {
            $value = trim($matches[1] ?? '');
            $value = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $value ?? '');
            $header['module'] = trim($value ?? '');
        }
        
        // Extract Client
        if (preg_match('/Client:\s*(.+?)(?:\n|Technology Stack:|$)/is', $this->text, $matches)) {
            $value = trim($matches[1] ?? '');
            $value = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $value ?? '');
            $header['client'] = trim($value ?? '');
        }
        
        // Extract Technology Stack
        if (preg_match('/Technology Stack:\s*(.+?)(?:\n|1\.|$)/is', $this->text, $matches)) {
            $value = trim($matches[1] ?? '');
            $value = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $value ?? '');
            $header['technology_stack'] = trim($value ?? '');
        }
        
        return $header;
    }
    
    /**
     * Extract a section between two markers
     * @param string $startPattern Regex pattern for section start
     * @param string|null $endPattern Regex pattern for section end (null = till end of document)
     */
    private function extractSection($startPattern, $endPattern = null) {
        $content = [];
        $capturing = false;
        $startFound = false;
        
        foreach ($this->lines as $line) {
            $line = trim($line);
            
            // Check if we've reached the start of the section
            if (!$startFound && preg_match('/' . $startPattern . '/i', $line)) {
                $startFound = true;
                $capturing = true;
                $content[] = $line;
                continue;
            }
            
            // Check if we've reached the end of the section
            if ($endPattern && $capturing && preg_match('/' . $endPattern . '/i', $line)) {
                break;
            }
            
            // Capture lines within the section
            if ($capturing && !empty($line)) {
                // Skip lines that are:
                // 1. Numbered items like "1.", "2.", "3." (these belong to FR subsections)
                // 2. FR code items like "FR-07.1:", "FR-07.2:" (these are in subsections)
                // 3. Lines starting with bullets followed by FR codes
                if (preg_match('/^(?:●\s*)?\d+\.\s/', $line)) {
                    continue; // Skip numbered items
                }
                if (preg_match('/^(?:●\s*)?FR-\d+\.\d+:/i', $line)) {
                    continue; // Skip FR sub-items
                }
                if (preg_match('/^(?:●\s*)?(?:\d+\.\d+)\s+.+?\s*\(FR-\d+\)/i', $line)) {
                    continue; // Skip section headers like "2.1 Module Name (FR-07)"
                }
                
                // Clean up bullet points at the end of lines
                $cleanLine = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $line ?? '');
                $cleanLine = trim($cleanLine ?? '');
                if (!empty($cleanLine)) {
                    $content[] = $cleanLine;
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Extract specific subsections (like FR-01, FR-02, NFR-01, etc.)
     */
    private function extractSubsections() {
        $subsections = [
            'functional' => [],
            'non_functional' => []
        ];
        
        // Extract Functional Requirements subsections (FR-01, FR-02, FR-07, FR-08, etc.)
        // Pattern 1: "2.1 Module Name (FR-XX)"
        preg_match_all('/^(?:●\s*)?(?:2\.\d+)\s+(.+?)\s*\((FR-\d+)\)/m', $this->text, $frMatches, PREG_SET_ORDER);
        
        // Pattern 2: "FR-XX: Description" or "FR-XX.YY: Description"  
        if (empty($frMatches)) {
            preg_match_all('/^(?:●\s*)?(FR-\d+):\s*(.+?)$/m', $this->text, $altMatches, PREG_SET_ORDER);
            foreach ($altMatches as $match) {
                $frMatches[] = [
                    0 => $match[0],
                    1 => $match[2],
                    2 => $match[1]
                ];
            }
        }
        
        foreach ($frMatches as $match) {
            $frCode = $match[2] ?? ''; // FR-07, FR-08, etc.
            $frTitle = trim($match[1] ?? '');
            
            // Clean up bullet points from title
            $frTitle = preg_replace('/^[●○■▪•]\s*/', '', $frTitle ?? '');
            $frTitle = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $frTitle ?? '');
            $frTitle = trim($frTitle ?? '');
            
            $frContent = $this->extractSubsectionContent($frCode);
            
            $subsections['functional'][$frCode] = [
                'title' => $frTitle,
                'code' => $frCode,
                'content' => $frContent
            ];
        }
        
        // Extract Non-Functional Requirements (NFR-01, NFR-02, etc.)
        // Pattern: "NFR-01 (Security): Description" or "●​NFR-01 (Security): Description"
        preg_match_all('/(?:●\s*)?(NFR-\d+)\s*\(([^)]+)\):\s*(.+?)(?=(?:●\s*)?NFR-\d+|\Z)/s', $this->text, $nfrMatches, PREG_SET_ORDER);
        
        foreach ($nfrMatches as $match) {
            $nfrCode = $match[1] ?? ''; // NFR-01, NFR-02, etc.
            $nfrType = trim($match[2] ?? ''); // Security, Performance, etc.
            $nfrContent = trim($match[3] ?? '');
            
            // Clean up bullet points at the beginning and end
            $nfrContent = preg_replace('/^[●○■▪•]\s*/', '', $nfrContent ?? '');
            $nfrContent = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $nfrContent ?? '');
            $nfrContent = preg_replace('/\s+/', ' ', $nfrContent ?? ''); // Normalize whitespace
            $nfrContent = trim($nfrContent ?? '');
            
            $subsections['non_functional'][$nfrCode] = [
                'type' => $nfrType,
                'code' => $nfrCode,
                'content' => $nfrContent
            ];
        }
        
        return $subsections;
    }
    
    /**
     * Extract detailed content for a specific FR subsection
     */
    private function extractSubsectionContent($code) {
        $content = [];
        $processedCodes = [];
        
        // Pattern to find FR-XX.YY items with their full content including nested lists
        // Updated to better detect section headers (2.2, 2.3, 3., etc.) that end subsections
        // Stops at: next FR subsection, FR/NFR module header, OR section/subsection headers (2.2, 3.)
        $pattern = '/(?:●\s*)?(' . preg_quote($code, '/') . '\.\d+):\s*(.+?)(?=(?:●\s*)?' . preg_quote($code, '/') . '\.\d+:|(?:●\s*)?(?:FR-\d+|NFR-\d+)|\n\s*\d+\.\d+\s|\n\s*\d+\.\s+[A-Z]|\Z)/s';
        
        if (preg_match_all($pattern, $this->text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $subCode = $match[1] ?? ''; // FR-07.1, FR-07.05, etc.
                $fullContent = trim($match[2] ?? ''); // Full content including description and numbered lists
                
                // First, try to extract numbered items (1., 2., 3.) from the content
                $nestedItems = [];
                
                // Pattern to find numbered items: "1. something", "2. something", etc.
                // OR hollow bullet items: "○ something", "○ something", etc.
                // This handles both inline and multi-line nested lists
                
                // Track item type for display (numbered vs bullet)
                $itemType = 'numbered'; // default
                
                // Check if content has numbered items (1., 2., 3.)
                // Must start with "1." to be a valid numbered list (not section headers like "3. Non-Functional")
                if (preg_match('/[\s:]1\.[\s\x{200B}\x{FEFF}]/u', $fullContent)) {
                    // Split content by numbered item markers while keeping the numbers
                    $pattern = '/([\s:]+)(\d+)\.[\s\x{200B}\x{FEFF}]*/u';
                    $parts = preg_split($pattern, $fullContent, -1, PREG_SPLIT_DELIM_CAPTURE);
                    
                    // Process the split parts
                    // Format: [before, separator, number, text, separator, number, text, ...]
                    for ($i = 0; $i < count($parts); $i++) {
                        // Check if this part is a number (item number)
                        if ($i > 0 && isset($parts[$i]) && is_numeric($parts[$i]) && $parts[$i] >= 1 && $parts[$i] <= 50) {
                            $itemNum = $parts[$i];
                            // Get the text that follows this number
                            $itemText = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';
                            
                            if (!empty($itemText)) {
                                // Clean up bullet points and zero-width spaces
                                $itemText = preg_replace('/^[●○■▪•\s\x{200B}\x{FEFF}]+/u', '', $itemText);
                                $itemText = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $itemText);
                                // Normalize whitespace (including newlines within item)
                                $itemText = preg_replace('/\s+/', ' ', $itemText);
                                $itemText = trim($itemText);
                                
                                if (!empty($itemText) && strlen($itemText) > 2) {
                                    $nestedItems[] = $itemText;
                                }
                            }
                        }
                    }
                }
                // Check for hollow bullet items (○) if no numbered items found
                elseif (preg_match('/○[\s\x{200B}\x{FEFF}]/u', $fullContent)) {
                    $itemType = 'bullet'; // Mark as bullet type
                    
                    // Split content by hollow bullet markers
                    $pattern = '/(○[\s\x{200B}\x{FEFF}]*)/u';
                    $parts = preg_split($pattern, $fullContent, -1, PREG_SPLIT_DELIM_CAPTURE);
                    
                    // Process the split parts
                    // Format: [before, marker, text, marker, text, ...]
                    for ($i = 0; $i < count($parts); $i++) {
                        // Check if this part is a hollow bullet marker
                        if (isset($parts[$i]) && preg_match('/^○[\s\x{200B}\x{FEFF}]*$/u', $parts[$i])) {
                            // Get the text that follows this marker
                            $itemText = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';
                            
                            if (!empty($itemText)) {
                                // Clean up other bullet points and zero-width spaces
                                $itemText = preg_replace('/^[●■▪•\s\x{200B}\x{FEFF}]+/u', '', $itemText);
                                $itemText = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $itemText);
                                // Normalize whitespace (including newlines within item)
                                $itemText = preg_replace('/\s+/', ' ', $itemText);
                                $itemText = trim($itemText);
                                
                                if (!empty($itemText) && strlen($itemText) > 2) {
                                    $nestedItems[] = $itemText;
                                }
                            }
                        }
                    }
                }
                
                // Now extract the description (text before the first nested item or all text if no nested items)
                $description = $fullContent;
                
                // If we found nested items, extract only the text before the first item as description
                if (!empty($nestedItems)) {
                    if ($itemType === 'numbered') {
                        // For numbered items: Split at the first "1."
                        if (preg_match('/^(.*?)[\s:]*1\.[\s\x{200B}\x{FEFF}]/su', $fullContent, $descMatch)) {
                            $description = trim($descMatch[1] ?? '');
                        } else {
                            // Fallback: remove all numbered patterns from description
                            $description = preg_replace('/[\s:]+\d+\.[\s\x{200B}\x{FEFF}]*.+?(?=[\s:]+\d+\.|\Z)/su', '', $fullContent);
                            $description = trim($description ?? '');
                        }
                    } elseif ($itemType === 'bullet') {
                        // For bullet items: Split at the first ○
                        if (preg_match('/^(.*?)○[\s\x{200B}\x{FEFF}]/su', $fullContent, $descMatch)) {
                            $description = trim($descMatch[1] ?? '');
                        } else {
                            // Fallback: remove all bullet patterns from description
                            $description = preg_replace('/○[\s\x{200B}\x{FEFF}]*.+?(?=○|\Z)/su', '', $fullContent);
                            $description = trim($description ?? '');
                        }
                    }
                }
                
                // Clean up the main description
                $description = preg_replace('/^[●○■▪•]\s*/', '', $description ?? '');
                $description = preg_replace('/[\s\x{200B}\x{FEFF}]*[●○■▪•][\s\x{200B}\x{FEFF}]*$/u', '', $description ?? '');
                $description = preg_replace('/\s+/', ' ', $description ?? '');
                $description = trim($description ?? '');
                
                if (!empty($description) || !empty($nestedItems)) {
                    $item = [
                        'code' => $subCode,
                        'description' => $description
                    ];
                    
                    // Add nested items if they exist
                    if (!empty($nestedItems)) {
                        $item['items'] = $nestedItems;
                        $item['item_type'] = $itemType; // Store the item type
                    }
                    
                    $content[] = $item;
                    $processedCodes[] = $subCode;
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Get all sections
     */
    public function getSections() {
        if (empty($this->sections)) {
            $this->parse();
        }
        return $this->sections;
    }
    
    /**
     * Get specific section
     */
    public function getSection($sectionName) {
        if (empty($this->sections)) {
            $this->parse();
        }
        return $this->sections[$sectionName] ?? null;
    }
    
    /**
     * Get functional requirements only
     */
    public function getFunctionalRequirements() {
        return $this->getSection('functional_requirements');
    }
    
    /**
     * Get non-functional requirements only
     */
    public function getNonFunctionalRequirements() {
        return $this->getSection('non_functional_requirements');
    }
    
    /**
     * Get all functional requirement subsections
     */
    public function getFunctionalSubsections() {
        $subsections = $this->getSection('subsections');
        return $subsections['functional'] ?? [];
    }
    
    /**
     * Get all non-functional requirement subsections
     */
    public function getNonFunctionalSubsections() {
        $subsections = $this->getSection('subsections');
        return $subsections['non_functional'] ?? [];
    }
}
