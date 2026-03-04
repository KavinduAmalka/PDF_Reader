<?php
/**
 * PDFParser - Extracts text content from PDF files
 * Uses Smalot\PdfParser library for text extraction
 */
class PDFParser {
    private $pdfPath;
    private $textContent;
    
    public function __construct($pdfPath) {
        $this->pdfPath = $pdfPath;
        $this->textContent = '';
        
        // Load Composer autoloader if available
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
    }
    
    /**
     * Extract text from PDF using available methods
     * @return string Extracted text content
     */
    public function extractText() {
        // Method 1: Try using Smalot\PdfParser (Composer package) - Most reliable
        $this->textContent = $this->extractWithSmalot();
        if ($this->isValidText($this->textContent)) {
            return $this->textContent;
        }
        
        // Method 2: Try using pdftotext command (if available)
        if ($this->hasPdfToText()) {
            $this->textContent = $this->extractWithPdfToText();
            if ($this->isValidText($this->textContent)) {
                return $this->textContent;
            }
        }
        
        // Method 3: Basic fallback extraction
        $this->textContent = $this->extractBasic();
        
        // If still garbage, return error message
        if (!$this->isValidText($this->textContent)) {
            return "ERROR: Unable to extract readable text from PDF.\n\n" .
                   "Possible reasons:\n" .
                   "1. PDF is a scanned image (requires OCR)\n" .
                   "2. PDF has special encoding or encryption\n" .
                   "3. PDF format is not supported\n\n" .
                   "Please try converting your PDF to a text-based format.";
        }
        
        return $this->textContent;
    }
    
    /**
     * Check if extracted text is valid (not binary garbage)
     * @param string $text
     * @return bool
     */
    private function isValidText($text) {
        if (empty($text) || strlen($text) < 10) {
            return false;
        }
        
        // Check if text contains readable characters
        // A valid text should have at least 50% printable ASCII characters
        $printableCount = 0;
        $totalCount = min(strlen($text), 1000); // Check first 1000 chars
        
        for ($i = 0; $i < $totalCount; $i++) {
            $char = ord($text[$i]);
            // Printable ASCII: space(32) to ~(126), plus newline(10), tab(9), return(13)
            if (($char >= 32 && $char <= 126) || $char == 10 || $char == 9 || $char == 13) {
                $printableCount++;
            }
        }
        
        $ratio = $printableCount / $totalCount;
        return $ratio > 0.7; // At least 70% readable characters
    }
    
    /**
     * Check if pdftotext command is available
     */
    private function hasPdfToText() {
        $output = [];
        $return = 0;
        exec('pdftotext -v 2>&1', $output, $return);
        return $return === 0 || $return === 99; // 99 is typical for version check
    }
    
    /**
     * Extract text using pdftotext command line tool
     */
    private function extractWithPdfToText() {
        $tempFile = sys_get_temp_dir() . '/pdf_text_' . uniqid() . '.txt';
        $command = sprintf('pdftotext "%s" "%s" 2>&1', 
            escapeshellarg($this->pdfPath), 
            escapeshellarg($tempFile)
        );
        
        exec($command, $output, $return);
        
        if ($return === 0 && file_exists($tempFile)) {
            $text = file_get_contents($tempFile);
            unlink($tempFile);
            return $text;
        }
        
        return '';
    }
    
    /**
     * Extract text using Smalot\PdfParser library
     * @return string
     */
    private function extractWithSmalot() {
        // Check if the Smalot library is available
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            return '';
        }
        
        try {
            // @phpstan-ignore-next-line - Optional library, may not be installed
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($this->pdfPath);
            $text = $pdf->getText();
            return $text;
        } catch (Exception $e) {
            error_log("Smalot PDF Parser Error: " . $e->getMessage());
            return '';
        } catch (Error $e) {
            // Handle case where class doesn't exist
            error_log("Smalot PDF Parser not available: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Basic text extraction (fallback method)
     * This is a simple implementation and may not work with all PDFs
     */
    private function extractBasic() {
        $content = file_get_contents($this->pdfPath);
        $text = '';
        
        // Try to extract text objects
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
            foreach ($matches[1] as $textBlock) {
                // Extract text from Tj and TJ operators
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $textBlock, $tjMatches)) {
                    $text .= implode(' ', $tjMatches[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $textBlock, $tjMatches)) {
                    foreach ($tjMatches[1] as $tj) {
                        if (preg_match_all('/\((.*?)\)/', $tj, $strings)) {
                            $text .= implode(' ', $strings[1]) . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }
        
        // Clean up the text
        $text = preg_replace('/\\\(\d{3})/', '', $text); // Remove octal codes
        $text = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $text);
        $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
        
        return trim($text);
    }
    
    /**
     * Get extracted text content
     */
    public function getText() {
        if (empty($this->textContent)) {
            $this->extractText();
        }
        return $this->textContent;
    }
    
    /**
     * Get text as array of lines
     */
    public function getLines() {
        $text = $this->getText();
        $lines = explode("\n", $text);
        return array_map('trim', $lines);
    }
}
