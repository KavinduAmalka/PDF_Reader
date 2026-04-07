# 🔧 Technical Implementation Notes

## Architecture Overview

The PDF Reader with UAT Testing consists of:

### 1. **Frontend Layer**
- HTML5 interface
- Bootstrap CSS for styling
- JavaScript for interactivity
- AJAX endpoints for real-time processing

### 2. **Backend Layer (PHP)**
- **config.php** - Configuration and API keys
- **index.php** - Main PDF upload interface
- **sections.php** - Navigation and requirements browsing
- **viewer.php** - PDF viewer
- **uat_test_cases_enhanced.php** - Test case generation UI
- **uat_test_execution.php** - Test execution UI
- **uat_results.php** - Results tracking UI

### 3. **Class Layer (Business Logic)**
```
classes/
├── PDFParser.php              - Wraps Smalot PDF parsing
├── SRSParser.php              - Parses SRS document structure
├── SVOAnalyzer.php            - Subject-Verb-Object analysis (AI)
├── ComprehensiveTestCaseGenerator.php  - Intelligent test generation
├── UATTestCase.php            - Test case model
├── UATReportGenerator.php      - Report generation
└── UATResultTracker.php        - Results persistence
```

### 4. **Storage Layer**
- `uploads/` - Uploaded PDF files
- `uat_results/` - Test execution results (JSON)
- Session variables - Temporary request data
- No database (file-based, simple structure)

---

## Key Classes

### ComprehensiveTestCaseGenerator.php

**Primary Method:** `generateTestCases()`
- Input: Requirement code, text, type
- Output: Array of 4 test cases (Positive, Negative, Edge, Alternate)

**Intelligent Text Analysis Methods:**

1. **`extractRequirementContext()`**
   - Analyzes requirement text
   - Extracts objects, actions, constraints
   - Returns semantic information
   - Used by all test generators

2. **`extractSpecificTitle()`**
   - Generates requirement-specific test names
   - Extracts key terms from description
   - Format: `[TYPE] {Action} {Object} - {Scenario}`

3. **Context Extraction Methods**
   - `extractObjects()` - Find system entities
   - `extractConstraints()` - Find limits (12 chars, 100MB, etc.)
   - `extractValidations()` - Find validation types
   - `extractKeywordsFromText()` - Find behavioral patterns
   - `extractFields()` - Find input parameters

4. **Test Generator Methods**
   - `generatePositiveContextualSteps()` - Happy path
   - `generateNegativeContextualSteps()` - Error handling
   - `generateEdgeContextualSteps()` - Boundary testing
   - `generateAlternateContextualSteps()` - Alternative flows
   - `generateContextualExpectedResult()` - Smart expected results

### SRSParser.php

**Methods:**
- `parseDocument()` - Extract sections from PDF text
- `extractSections()` - Split into FR, NFR, other sections
- `extractSubRequirements()` - Parse requirement hierarchy
- `extractRequirementCode()` - Get FR-XX.XX codes

### SVOAnalyzer.php

**Method:** `analyze($text, $code)`
- Uses Google Gemini API (free tier)
- Performs Subject-Verb-Object analysis
- Extracts semantic components
- Returns JSON with analysis results

### UATResultTracker.php

**Methods:**
- `saveTestResult()` - Records test execution
- `getResults()` - Retrieves results
- `getStatistics()` - Compilation pass/fail stats
- `generateTraceabilityMatrix()` - Coverage analysis

---

## Data Flow

### Test Case Generation Flow:
```
Upload PDF
    ↓
PDFParser extracts text
    ↓
SRSParser identifies sections and requirements
    ↓
For each requirement:
├─ ComprehensiveTestCaseGenerator.generateTestCases()
│  ├─ extractRequirementContext() - Analyze text
│  ├─ extractSpecificTitle() - Get smart name
│  ├─ generatePositiveContextualSteps() - Test 1
│  ├─ generateNegativeContextualSteps() - Test 2
│  ├─ generateEdgeContextualSteps() - Test 3
│  ├─ generateAlternateContextualSteps() - Test 4
│  └─ generateContextualExpectedResult() - Expected results
└─ Store in $_SESSION['test_cases']
    ↓
Display Test Cases UI
```

### Test Execution Flow:
```
Select Test Case
    ↓
Display Test Steps
    ↓
Tester Executes Steps
    ↓
Record Pass/Fail/Comments
    ↓
UATResultTracker.saveTestResult()
    ↓
Store in uat_results/{requirement_code}.json
    ↓
Update Statistics
    ↓
Display on Results Page
```

---

## Configuration (config.php)

### Required Environment Variables:
```php
GEMINI_API_KEY      // Google Gemini API key (free)
VERIFY_SSL          // SSL verification (can be false for local WAMP)
```

### Set in .env file:
```
GEMINI_API_KEY=your_api_key_here
VERIFY_SSL=false    // For local development
```

---

## Database/Storage Model

### No Database - File-Based Storage

**Advantages:**
- No database server required
- Simple deployment (just PHP + files)
- Easy to backup (copy folders)
- No authentication complexity
- Works in Docker/cloud instantly

**Files Structure:**
```
uploads/
├── document_01.pdf          # Uploaded SRS documents
├── document_02.pdf
└── ...

uat_results/
├── FR-10.01.json           # Test results per requirement
├── FR-20.02.json
├── NFR-05.01.json
└── ...
```

### Result JSON Structure:
```json
{
  "requirement_code": "FR-20.01",
  "test_cases": [
    {
      "test_id": "FR-20.01_TC_001",
      "name": "[POSITIVE] Validate user credentials - Happy Path",
      "status": "PASS",
      "tester": "John Doe",
      "date": "2024-01-15",
      "comments": "All steps executed successfully",
      "attachments": ["screenshot1.png"]
    }
  ],
  "summary": {
    "total": 4,
    "passed": 3,
    "failed": 1,
    "pending": 0
  }
}
```

---

## API Endpoints

### AJAX Endpoints:

#### 1. analyze_requirement.php
**POST** `/analyze_requirement.php`
```
Input JSON:
{
  "requirement_text": "System shall validate...",
  "requirement_code": "FR-20.01"
}

Output JSON:
{
  "requirement_code": "FR-20.01",
  "fields": ["password", "email"],
  "actions": ["validate", "store"],
  "constraints": ["12 characters", "encryption"],
  "svo_analysis": {...}
}
```

---

## Session Management

### Session Variables Used:

```php
$_SESSION['uploaded_pdf']        // Current PDF filename
$_SESSION['srs_sections']        // Parsed sections
$_SESSION['requirements']        // Extracted requirements
$_SESSION['sub_requirements']    // Sub-requirement hierarchy
$_SESSION['test_cases']          // Generated test cases
$_SESSION['selected_requirement'] // Currently selected requirement
```

### Clearing Session:
```php
// Manual clear
include 'clear_session.php';

// Or programmatically
session_destroy();
```

---

## Performance Optimization

### Caching:
- PDF parsing cached in session (survives page reloads)
- Test cases generated once, displayed multiple times
- Results cached until new tests run

### Lazy Loading:
- Requirements loaded as needed
- Test details only displayed when selected
- Results API calls use filtered queries

### File Operations:
- Results stored as JSON (fast read/write)
- Minimal disk I/O
- No external database queries

---

## Security Considerations

### Input Validation:
- File type validation (PDF only)
- File size limits (10MB max)
- Extension checking (.pdf)
- MIME type validation

### Session Security:
- Session-based access (no login required for simplicity)
- Files cleared on browser close
- Upload directory outside web root (best practice)

### API Key Security:
- Gemini API key stored in .env (not in code)
- .env file excluded from git
- Only used server-side (not exposed to client)

### File Upload Security:
- Uploads in restricted directory
- Only 2 most recent PDFs kept (cleanup)
- Direct file access prevented
- Served through serve_pdf.php endpoint

---

## Dependencies

### Composer Packages (vendor/):
```json
{
  "smalot/pdfparser": "^0.x",
  "symfony/polyfill-mbstring": "^1.x"
}
```

### External APIs:
- Google Generative AI API (Gemini) - FREE tier
- Used for SVO analysis only
- No cost unless quota exceeded (very unlikely)

### PHP Requirements:
- PHP 7.4+ (tested on 7.4, 8.0, 8.1)
- cURL extension (for API calls)
- JSON extension (for data handling)
- Session support enabled

---

## Deployment

### Local Development:
```
1. Clone repository
2. composer install
3. Set .env variables
4. Run in WAMP/XAMPP
5. Access via http://localhost/PDF_Reader/
```

### Docker Deployment:
```
docker-compose up
# Access via http://localhost:8080
```

### Production Checklist:
- ✅ Set VERIFY_SSL=true
- ✅ Secure .env file
- ✅ Configure uploads/ directory permissions (755)
- ✅ Configure uat_results/ directory permissions (755)
- ✅ Enable HTTPS
- ✅ Set session cookie secure flag
- ✅ Configure file upload size limits

---

## Debugging & Logging

### Enable Debug Mode:
In `config.php`:
```php
define('DEBUG_MODE', true);
```

### Check Logs:
```bash
tail -f /var/log/apache2/error.log      # Apache
tail -f /var/log/php/error.log          # PHP
tail -f /var/log/nginx/error.log        # Nginx
```

### Browser Console:
- F12 → Console tab
- Check for JavaScript errors
- Network tab for AJAX debugging

### PHP Errors:
- Check `error_reporting` setting
- Look in `display_errors` output
- Check error logs in `php.ini`

---

## Version Control

### Files Tracked (in git):
- ✅ All PHP source files
- ✅ Configuration templates (.env.example)
- ✅ Dockerfile and docker-compose.yml
- ✅ composer.json and composer.lock
- ✅ Documentation files

### Files NOT Tracked:
- ❌ uploads/ (uploaded PDFs)
- ❌ uat_results/ (test results)
- ❌ vendor/ (composer dependencies)
- ❌ .env (actual API keys)
- ❌ Session files

---

## Troubleshooting Guide

### "Uploaded PDF not parsed"
- Check PDF is valid and not corrupted
- Check file size (< 10MB)
- Check server PHP error logs
- Try with sample PDF from templates/

### "Test case generation returns empty"
- Ensure PDF uploaded and parsed
- Check that requirements extracted
- Look at extracted sections in sections.php
- Verify Gemini API key is correct

### "Results not saving"
- Check uat_results/ directory permissions (755)
- Check disk space available
- Look at PHP error logs
- Verify browser allows file operations

### "API Key errors"
- Verify key in .env file
- Check key is not expired
- Verify API enabled in Google Cloud Console
- Test with test_analysis.php script

---

## Future Enhancements

### Planned Features:
- 🔄 Database backend (MySQL/PostgreSQL)
- 👥 Multi-user support with authentication
- 📧 Email test report distribution
- 📱 Mobile app for test execution
- 🔌 Integration with test management systems (TestRail, Jira)
- 🤖 Improved AI analysis (custom models)
- 📊 Advanced analytics dashboard
- 🔐 Role-based access control

---

## Contributing

To contribute improvements:
1. Create feature branch
2. Make changes
3. Test thoroughly
4. Submit pull request
5. Include documentation updates

**Code Standards:**
- PSR-12 style guide
- Comments for complex logic
- Error handling for all file operations
- Input validation for all user inputs

---

