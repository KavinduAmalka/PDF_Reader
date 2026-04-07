# 🎯 Expert QA Test Case Generator - Complete Guide

## Overview

The **Comprehensive Test Case Generator** is an expert-level QA testing module that automatically generates structured, professional-grade test cases for **EVERY requirement** in your SRS document.

### Key Features

✅ **3-4 Test Cases Per Requirement**
- Positive (happy path)
- Negative (error handling)
- Edge Case (boundary conditions)
- Performance (for NFRs) / Alternate Flow (for FRs)

✅ **Smart Test Case Generation**
- Auto-extracts personas/actors from requirements
- Intelligently generates preconditions
- Creates realistic test steps
- Specifies measurable expected results

✅ **Professional Structuring**
- Test ID mapping (e.g., FR-20.01_TC_001)
- Priority levels (P0 Critical, P1 High, P2 Medium)
- Detailed acceptance criteria
- Test data specifications

✅ **Multiple Export Formats**
- JSON (for tools, APIs, database import)
- CSV (for Excel, spreadsheet analysis)
- HTML Dashboard (for stakeholders)

✅ **Advanced Filtering**
- Filter by requirement type (FR/NFR)
- Filter by test type (Positive/Negative/Edge/Performance)
- Full-text search across test cases
- Expandable/collapsible requirement groups

---

## How to Access

### From Sections Page
1. **Upload & Parse PDF** → Sections page loads
2. **Click one of:**
   - `📊 Expert QA Test Cases` button (header)
   - `🎯 Expert QA Test Cases` (sidebar menu)

### Direct URL
```
http://localhost/PDF_Reader/uat_comprehensive_test_cases.php
```

---

## How It Works

### Step 1: Automatic Test Case Generation
When you open the page, the system automatically:
- Reads all requirements from parsed SRS
- Extracts personas/actors from requirement text
- Identifies action verbs (upload, send, approve, etc.)
- Generates 3-4 comprehensive test cases per requirement

### Step 2: View & Filter Test Cases
- **View all requirements** with collapsible test case cards
- **Filter by requirement type:** All / FR / NFR
- **Filter by test type:** All / Positive / Negative / Edge Case / Performance
- **Search:** Find specific test cases by keyword

### Step 3: Export for Tool Integration
- **JSON Export:** Import into QA tools, test management systems, APIs
- **CSV Export:** Open in Excel for further analysis

---

## Test Case Structure

Each test case includes:

### 1. **Identification**
- `test_id`: Unique identifier (e.g., `FR-20.01_TC_001`)
- `requirement_id`: SRS requirement mapping (e.g., `FR-20.01`)
- `test_name`: Human-readable title
- `test_type`: One of [Positive, Negative, Edge Case, Performance, Alternate Flow]

### 2. **Context**
- `description`: What the test verifies
- `user_persona`: Who performs the test
- `priority`: P0 (Critical), P1 (High), P2 (Medium)

### 3. **Execution Details**
- `pre_conditions`: What must be true before test
- `test_steps`: Step-by-step instructions
- `expected_result`: Specific outcome from SRS

### 4. **Validation**
- `acceptance_criteria`: Checkable success criteria
- `test_data`: Sample input values
- `automation_ready`: Whether test can be automated

---

## Test Case Types Explained

### ✅ POSITIVE Test Case (TC_001)
**Purpose:** Verify happy path execution

**Example:**
- **Requirement:** FR-20.01 - Author registers and completes payment
- **Test:** Author enters valid payment info, system accepts, confirmation sent
- **Expected:** Author receives confirmation, account created, payment processed

### ❌ NEGATIVE Test Case (TC_002)
**Purpose:** Verify error handling

**Example:**
- **Requirement:** FR-20.01 - Author registration with payment
- **Test:** Author enters invalid card number
- **Expected:** System rejects, error message shown, no data processed

### 🔄 EDGE CASE Test Case (TC_003)
**Purpose:** Verify boundary conditions

**Example:**
- **Requirement:** FR-20.01 - Payment processing
- **Test:** Payment exact at deadline, concurrent registrations, max file size
- **Expected:** System handles limits correctly, no overflow, proper queuing

### ⚡ PERFORMANCE Test Case (TC_004) [NFR only]
**Purpose:** Validate performance specifications

**Example:**
- **Requirement:** NFR-02 - Track merge within 6 minutes
- **Test:** Execute 4-track merge with 1000+ papers
- **Expected:** Merge completes in ≤360 seconds

### 🔀 ALTERNATE FLOW Test Case (TC_004) [FR only]
**Purpose:** Test variations of primary flow

**Example:**
- **Requirement:** FR-20.04 - Email notification on upload
- **Test:** Upload with multiple co-authors
- **Expected:** Email sent to all, audit logged, status updated

---

## Statistical Summary

The dashboard shows:
- **Total Requirements:** Count of all FR + NFR
- **Total Test Cases:** Count of all generated tests
- **Functional Requirements (FR):** Count of feature requirements
- **Non-Functional Requirements (NFR):** Count of quality requirements

**Example:**
- 50 Total Requirements
- 200 Total Test Cases (4 per requirement)
- 35 Functional Requirements
- 15 Non-Functional Requirements

---

## Filtering & Searching

### By Requirement Type
```
All Requirements → Shows FR + NFR
Functional (FR) → Shows only FR test cases
Non-Functional (NFR) → Shows only NFR test cases
```

### By Test Type
```
All Types → Shows all test case types
Positive → Shows only positive test cases
Negative → Shows only negative test cases
Edge Case → Shows only edge case tests
Performance → Shows only performance tests
Alternate Flow → Shows alternate flow tests
```

### By Search Term
```
Enter keywords to filter test cases
Examples:
- "email" → Finds all email-related tests
- "upload" → Finds all file upload tests
- "FR-20" → Finds tests for requirement FR-20
```

---

## Exporting Test Cases

### JSON Export
**Best for:** Tool integration, API import, database storage

**Includes:**
- Metadata (generation timestamp, format version)
- Summary statistics
- Complete test case structures
- All details for automation

**Usage:**
```json
{
  "metadata": {
    "generated_at": "2026-04-03 14:30:00",
    "generator": "ComprehensiveTestCaseGenerator",
    "format_version": "1.0"
  },
  "summary": {
    "total_requirements": 50,
    "total_test_cases": 200
  },
  "test_cases": { ... }
}
```

### CSV Export
**Best for:** Excel analysis, stakeholder reporting, simple spreadsheets

**Columns:**
- Requirement ID
- Test ID
- Test Type
- Description
- User Persona
- Priority
- Pre-conditions
- Test Steps
- Expected Result

---

## Integration Examples

### QA Test Management Tool
```php
// Import JSON test cases into tool
$json = json_decode(file_get_contents('comprehensive_test_cases.json'), true);
foreach ($json['test_cases'] as $reqId => $tests) {
    foreach ($tests as $test) {
        // Create test in tool
        $tool->createTestCase($test);
    }
}
```

### Excel/Sheets Analysis
1. Export as CSV
2. Open in Excel/Google Sheets
3. Add custom columns (Status, Tester, Date)
4. Track execution progress

### Automation Framework
```python
# Load test cases for automatic test generation
test_cases = json.load(open('comprehensive_test_cases.json'))
for req_id, tests in test_cases.items():
    for test in tests:
        generate_selenium_test(test)
```

---

## Test Case Statistics

### By Requirement Type
- **FR (Functional):** 3-4 tests per requirement
- **NFR (Non-Functional):** 3-4 tests per requirement (includes Performance)

### By Test Type Distribution
- **Positive:** 25% (1 per requirement)
- **Negative:** 25% (1 per requirement)
- **Edge Case:** 25% (1 per requirement)
- **Performance/Alternate:** 25% (1 per requirement)

### Typical Coverage
- **100% Requirement Coverage:** Every FR and NFR has tests
- **Happy Path Coverage:** Positive test per requirement
- **Error Handling:** Negative test per requirement
- **Boundary Testing:** Edge case test per requirement
- **Performance Validation:** Performance test for NFRs

---

## Best Practices

### ✅ DO
- Export in JSON for tool integration
- Review test cases with team
- Use as baseline for manual testing
- Automate positive test cases first
- Track execution results over time

### ❌ DON'T
- Skip edge case testing
- Ignore performance requirements
- Export without reviewing
- Assume all tests will be automated
- Ignore negative test results

---

## Troubleshooting

### Issue: No test cases showing
**Solution:** 
1. Verify PDF was uploaded and parsed
2. Check that PDF contains valid requirements
3. Ensure sections.php successfully parsed the document

### Issue: Missing personas
**Solution:**
1. Verify requirement text contains actor keywords (Author, Admin, Manager, etc.)
2. Generator uses pattern matching on requirement text
3. Check "Alternate Flow" test case for secondary personas

### Issue: Export not working
**Solution:**
1. Ensure browser allows file downloads
2. Check file permissions on server
3. Try different export format (JSON vs CSV)

---

## Next Steps

1. **Generate Test Cases** → Open Expert QA Test Cases page
2. **Review & Filter** → Use filters to find specific tests
3. **Export Data** → Choose JSON or CSV format
4. **Integrate with Tools** → Import into your test management system
5. **Execute Tests** → Use traditional UAT Testing module
6. **Track Results** → Monitor coverage and pass rates

---

## Technical Details

### Generator Algorithm

The `ComprehensiveTestCaseGenerator` class:

1. **Reads SRS Requirements**
   - Parses all FR and NFR from session
   - Extracts requirement text from nested arrays

2. **Generates Test Cases**
   - For each requirement, creates 4 test cases
   - Applies intelligent pattern matching
   - Generates realistic personas and steps

3. **Structures Output**
   - Creates standardized JSON structure
   - Includes metadata and statistics
   - Prepares for tool integration

### Data Source

Test cases are generated from:
- `$_SESSION['srs_sections']['subsections']['functional']`
- `$_SESSION['srs_sections']['subsections']['non_functional']`
- Automatically extracted during PDF parsing

### Performance

- Generation time: < 2 seconds (typical SRS)
- Memory usage: < 10MB
- JSON file size: ~50-100KB (typical 50 requirements)

---

## Support & Contact

For issues or questions:
- Check troubleshooting section above
- Review test case structure documentation
- Verify PDF was properly parsed before generating test cases

---

**Last Updated:** April 3, 2026  
**Version:** 1.0  
**Status:** Production Ready
