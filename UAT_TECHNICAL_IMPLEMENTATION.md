# UAT Testing Module - Technical Implementation

## 🏗️ Architecture Overview

```
PDF_Reader/
├── classes/
│   ├── UATTestCase.php              [Test Case Generator]
│   ├── UATResultTracker.php         [Result Persistence]
│   └── UATReportGenerator.php       [Report Creation]
│
├── uat_test_cases.php               [Display Test Cases]
├── uat_test_execution.php           [Execute & Record Tests]
├── uat_results.php                  [Coverage Dashboard]
├── uat_reports.php                  [Report Generation]
│
├── uat_results/                     [Storage Directory]
│   ├── {pdfHash}_results.json       [Test Results]
│   └── {pdfHash}/evidence/          [Screenshots]
│
└── sections.php                     [Updated with UAT links]
```

---

## 🔧 Core Components

### 1. **UATTestCase Class**
**File:** `classes/UATTestCase.php`

**Purpose:** Auto-generates test cases from requirements

**Key Methods:**
```php
generateTestCases()           → Generate all test types
extractRequirementComponents() → Parse requirement text
generateBasicTestCase()       → Create positive test
generateEdgeTestCase()        → Create boundary test
generateNegativeTestCase()    → Create error handling test
generatePerformanceTestCase() → Create performance test (NFR)
```

**Algorithm:**
```
Input: Requirement Code, Requirement Text, Type (FR/NFR)
├─ Extract components (actors, actions, objects, conditions)
├─ Generate Basic Test
├─ If conditions exist → Generate Edge Case Test
├─ Always → Generate Negative Test
├─ If NFR & performance related → Generate Performance Test
Output: Array of Test Cases
```

**Test Case Structure:**
```php
[
    'test_id'        => 'FR-01.T1',
    'requirement_code' => 'FR-01',
    'test_type'      => 'Positive',        // Positive, Edge Case, Negative, Performance
    'title'          => 'Test title',
    'description'    => 'What this tests',
    'preconditions'  => [...],
    'test_steps'     => [['step', 'action', 'expectedResult']],
    'expected_result' => 'Expected outcome',
    'priority'       => 'High',            // High, Medium, Low
    'status'         => 'Not Tested'       // Not Tested, PASS, FAIL, BLOCKED
]
```

---

### 2. **UATResultTracker Class**
**File:** `classes/UATResultTracker.php`

**Purpose:** Persists test results and calculates coverage

**Key Methods:**
```php
saveTestResult($pdfHash, $reqCode, $testId, $result)
loadResults($pdfHash)
getTestResult($pdfHash, $reqCode, $testId)
getRequirementCoverage($pdfHash, $reqCode)
getUATSummary($pdfHash, $requirements)
saveScreenshot($pdfHash, $reqCode, $testId, $file)
exportResults($pdfHash)
```

**Data Persistence:**
```
File: uat_results/{pdfHash}_results.json

Structure:
{
    "FR-01": {
        "FR-01.T1": {
            "status": "PASS|FAIL|BLOCKED",
            "tester_name": "Name",
            "execution_date": "2024-01-15 10:30:45",
            "notes": "Comments",
            "evidence_file": "evidence/path.jpg"
        }
    }
}
```

**Coverage Calculation:**
```
For Each Requirement:
├─ Count total tests
├─ Count passed tests
├─ Calculate pass_percentage = (passed / total) * 100
└─ Determine status based on results

Coverage Status Logic:
├─ If total == 0 → "Not Started"
├─ If not_tested > 0 → "In Progress"
├─ If failed > 0 → "Failed"
├─ If blocked > 0 → "Blocked"
└─ If all passed → "Passed"
```

---

### 3. **UATReportGenerator Class**
**File:** `classes/UATReportGenerator.php`

**Purpose:** Generates HTML and CSV reports

**Key Methods:**
```php
generateHTMLReport()         → Full HTML report
generateCSVData()            → CSV export
generateTitlePage()
generateExecutiveSummary()
generateCoverageSummary()
generateDetailedResults()
```

**Report Sections:**
1. **Title Page**
   - Report title
   - Document date
   - Project info

2. **Executive Summary**
   - Key metrics (total tests, passed, failed, pass rate)
   - Overall status indicator
   - High-level findings

3. **Coverage Summary**
   - Requirement vs test mapping
   - Pass percentage per requirement
   - Status indicators

4. **Detailed Results**
   - Full test case results
   - Test steps
   - Tester notes
   - Evidence references

---

## 🌐 Page Flow Architecture

### 1. **uat_test_cases.php**
**Purpose:** Display auto-generated test cases

**Flow:**
```
SESSION Check
├─ Load parsed sections
├─ Generate test cases for each requirement
├─ Display requirement sidebar
└─ Show test cases for selected requirement
```

**Key Features:**
- Filter by requirement
- Display test details
- Links to execution page
- Test case cards with steps

---

### 2. **uat_test_execution.php**
**Purpose:** Execute tests and record results

**Flow:**
```
SESSION Check
├─ Load requirements & test cases
├─ POST: Save test result
│   ├─ Validate input
│   ├─ Handle screenshot upload
│   ├─ Save result to JSON
│   └─ Show success message
└─ GET: Display execution form
    ├─ Show test details
    ├─ Form for result input
    └─ Screenshot upload area
```

**Form Fields:**
- Tester Name (required)
- Test Result (required) - PASS/FAIL/BLOCKED
- Tester Notes (optional)
- Evidence File (optional)

---

### 3. **uat_results.php**
**Purpose:** Display coverage tracking dashboard

**Flow:**
```
SESSION Check
├─ Load all requirements
├─ Calculate summary metrics
├─ Build coverage table
└─ Display dashboard
    ├─ Overall status
    ├─ Summary cards
    ├─ Coverage table
    └─ Distribution stats
```

**Metrics Displayed:**
- Total tests / Passed / Failed / Blocked
- Requirements tested / Total requirements
- Pass percentage per requirement
- Progress bars

---

### 4. **uat_reports.php**
**Purpose:** Generate and download reports

**Flow:**
```
SESSION Check
├─ Display report options
├─ POST: Generate report
│   ├─ If 'html' → Generate & display HTML
│   ├─ If 'csv' → Download CSV file
│   └─ If 'pdf' → Generate HTML & provide print option
└─ Display report UI
```

**Report Options:**
1. HTML Report (Print to PDF)
2. CSV Export (Excel)
3. Summary Report

---

## 📊 Data Flow Diagram

```
PDF Upload (index.php)
    ↓
Parse Sections (sections.php)
    ↓
├─→ View Test Cases (uat_test_cases.php)
│       ↓
│   Generate Tests (UATTestCase)
│       ↓
│   Display to User
│
├─→ Execute Tests (uat_test_execution.php)
│       ↓
│   User runs test
│       ↓
│   Save Result (UATResultTracker)
│       ↓
│   JSON File Storage
│
├─→ View Results (uat_results.php)
│       ↓
│   Load Results
│       ↓
│   Calculate Coverage (UATResultTracker)
│       ↓
│   Display Dashboard
│
└─→ Generate Reports (uat_reports.php)
        ↓
    Load Results (UATReportGenerator)
        ↓
    Generate Report
        ↓
    Download/Display
```

---

## 🗂️ File Structure

### Test Case Auto-Generation:
```
Requirement Text
    ↓
Extract Components:
├─ Actors (user, system, admin)
├─ Actions (create, update, delete, upload, download)
├─ Objects (file, document, requirement, section)
├─ Conditions (if, when, where)
└─ Constraints (shall, should, must, may)
    ↓
Generate Test Cases:
├─ T1: Basic positive test
├─ T2: Edge case test (if conditions exist)
├─ T3: Negative test
└─ T4: Performance test (if NFR performance)
```

### Result Persistence:
```
Test Execution
    ↓
Save to uat_results/{pdfHash}_results.json
    ↓
Upload Evidence to uat_results/{pdfHash}/evidence/
    ↓
Link evidence in JSON
```

### Report Generation:
```
Load Results (JSON)
    ↓
Calculate Metrics
    ↓
Generate HTML/CSV
    ↓
Download or Display
```

---

## 🔐 Security Considerations

### Input Validation:
```php
// Tester name
$testerName = trim($_POST['tester_name'] ?? '');

// Test status
if (!in_array($status, ['PASS', 'FAIL', 'BLOCKED'])) {
    // Invalid status
}

// File upload
if ($_FILES['evidence']['size'] > 5 * 1024 * 1024) {
    // File too large
}
```

### File Upload Safety:
```php
$validMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($_FILES['evidence']['type'], $validMimes)) {
    // Invalid file type
}
```

### XSS Prevention:
```php
// Always escape output
htmlspecialchars($userInput)
htmlspecialchars($result['notes'])
htmlspecialchars($evidenceFile)
```

---

## 📈 Performance Considerations

### Test Case Generation:
- **Time:** O(n) where n = number of requirements
- **Memory:** Minimal, generated on-request
- **Caching:** Not cached, generated each page load

### Result Loading:
- **Time:** O(1) JSON file read
- **Memory:** Proportional to test count
- **Optimization:** Direct JSON parsing, no extra processing

### Report Generation:
- **Time:** O(n) where n = number of test results
- **Memory:** Depends on report size
- **Optimization:** Streaming for large reports

---

## 🧪 Test Case Generation Examples

### Example 1: Functional Requirement
```
Input:
  Code: "FR-01"
  Text: "The system shall allow authorized users to upload PDF documents"
  Type: "FR"

Generated Tests:
1. FR-01.T1 (Positive)
   - Title: "Verify upload of PDF documents by authorized user"
   - Steps: Navigate → Select file → Upload → Verify
   
2. FR-01.T2 (Edge Case)
   - Title: "Verify FR-01 with boundary values"
   - Steps: Edge case data → Upload → Verify
   
3. FR-01.T3 (Negative)
   - Title: "Verify error handling for FR-01"
   - Steps: Invalid data → Upload → Verify error message
```

### Example 2: Non-Functional Requirement
```
Input:
  Code: "NFR-02"
  Text: "System response time shall not exceed 3 seconds"
  Type: "NFR"

Generated Tests:
1. NFR-02.T1 (Positive)
   - Title: "Verify NFR-02 normal operation"
   
2. NFR-02.T2 (Edge Case)
   - Title: "Verify boundary conditions"
   
3. NFR-02.T3 (Negative)
   - Title: "Verify error handling"
   
4. NFR-02.T4 (Performance)
   - Title: "Verify performance requirements"
   - Steps: Monitor performance → Load test → Verify SLA
```

---

## 🔄 Session Management

### Session Variables Used:
```php
$_SESSION['uploaded_pdf']      // Path to uploaded PDF
$_SESSION['original_filename']  // Original file name
$_SESSION['srs_sections']      // Parsed sections
$_SESSION['pdf_text']          // Extracted text
$_SESSION['srs_parser_version']// Parser version
$_SESSION['parse_error']       // Any parse errors
```

---

## 📋 Database Alternative

If you want to migrate to database storage:

```sql
-- Create tables
CREATE TABLE uat_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pdf_hash VARCHAR(32),
    requirement_code VARCHAR(50),
    test_id VARCHAR(50),
    test_type VARCHAR(50),
    title VARCHAR(255),
    status VARCHAR(20),
    UNIQUE(pdf_hash, test_id)
);

CREATE TABLE uat_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT,
    tester_name VARCHAR(100),
    execution_date DATETIME,
    status VARCHAR(20),
    notes TEXT,
    evidence_file VARCHAR(255),
    FOREIGN KEY (test_id) REFERENCES uat_tests(id)
);
```

---

## 🚀 Deployment

### Requirements:
- PHP 7.4+
- Write permissions for `uat_results/` directory
- Write permissions for `uat_reports/` directory

### Setup:
```bash
# Create directories
mkdir -p uat_results/
mkdir -p uat_reports/
chmod 755 uat_results/
chmod 755 uat_reports/

# Verify write permissions
ls -la uat_results/
```

---

## 🐛 Debugging

### Enable Debug Mode:
```php
// In sections.php sidebar
?section=debug&debug=1
```

### Check File Writes:
```bash
# Check results files
ls -la uat_results/

# Check specific results
cat uat_results/{pdfHash}_results.json | php -m
```

### Clear Session:
```
Goto: http://localhost/PDF_Reader/clear_session.php
```

---

## 📝 Future Enhancements

1. **Database Integration**
   - Replace JSON with database
   - Better scalability
   - User authentication

2. **Advanced Reporting**
   - Statistical analysis
   - Trend tracking
   - Failure pattern detection

3. **Automation**
   - Automated test execution
   - Integration with CI/CD
   - Webhook notifications

4. **Collaboration**
   - Multiple testers
   - Comments and discussions
   - Version control

5. **Mobile Support**
   - Mobile-friendly interface
   - Offline testing
   - App version

---

## 📞 Support

**For technical issues:**
1. Check PHP error logs
2. Verify file permissions
3. Clear session and reload
4. Check browser console

**Documentation:**
- `UAT_TESTING_GUIDE.md` - User guide
- `classes/UATTestCase.php` - Code comments
- This file - Technical reference

---

**Last Updated:** April 2, 2026  
**Version:** 1.0  
**Status:** Production Ready  
**Compatibility:** PHP 7.4+
