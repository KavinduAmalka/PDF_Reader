# UAT Testing Module - Complete Guide

## 🎯 Overview

The **UAT (User Acceptance Testing) Testing Module** is an integrated feature in the PDF Reader with SRS Parser that enables you to:

1. **Auto-Generate Test Cases** from SRS requirements (both FR and NFR)
2. **Execute Tests** with Pass/Fail tracking
3. **Upload Evidence** (screenshots/proofs)
4. **Track Coverage** across all requirements
5. **Generate Reports** (HTML, CSV, PDF)

---

## 📋 Features

### ✨ Auto-Generated Test Cases
- **Automatic generation** of test cases from requirements
- **Multiple test types:**
  - ✅ **Positive Tests** - Normal functionality
  - 🔄 **Edge Cases** - Boundary conditions
  - ❌ **Negative Tests** - Error handling
  - ⚡ **Performance Tests** - For NFR requirements
- **Pre-populated test steps** based on requirement analysis
- **Expected results** based on requirement text

### ▶️ Test Execution Interface
- **Simple Pass/Fail/Blocked buttons**
- **Tester information** capture
- **Screenshot/Evidence upload**
  - Supports: JPG, PNG, GIF, PDF
  - Max 5MB per file
- **Test notes/comments**
- **Timestamp tracking**

### 📊 Coverage Tracking
- **Requirement-wise coverage**
- **Pass percentage** per requirement
- **Overall UAT status** dashboard
- **Test statistics** (Passed/Failed/Blocked)
- **Real-time tracking** of test progress

### 📄 Report Generation
- **Comprehensive HTML Reports**
  - Executive Summary
  - Coverage Matrix
  - Detailed Results
  - Evidence References
- **CSV/Excel Export**
  - All test data in spreadsheet format
  - Compatible with Excel, Google Sheets
- **Coverage Dashboard**
  - Visual representation of pass rates
  - Requirement status overview

---

## 🚀 Getting Started

### Step 1: Upload & Parse SRS Document
```
1. Go to http://localhost/PDF_Reader/
2. Upload your SRS PDF document
3. Document is automatically parsed into sections
```

### Step 2: Navigate to UAT Testing
```
Once parsed, you'll see new options:
- 🧪 UAT Testing button in header
- 📊 UAT Results button in header
- UAT Testing menu in sidebar
```

### Step 3: View Auto-Generated Test Cases
```
1. Click "🧪 UAT Testing" or "Test Cases" from sidebar
2. Select a requirement (FR or NFR)
3. View auto-generated test cases
4. Each test has:
   - Test ID (e.g., FR-01.T1)
   - Test Type (Positive/Edge Case/Negative/Performance)
   - Description
   - Preconditions
   - Test Steps
   - Expected Results
```

---

## 🎬 How to Execute Tests

### Option A: Direct Execution
```
1. Go to "Execute Tests" page
2. Select requirement from sidebar
3. Select specific test case
4. Fill in form:
   - Tester Name (required)
   - Test Result: PASS / FAIL / BLOCKED (required)
   - Tester Notes (optional)
   - Upload Evidence (optional)
5. Click "Save Test Result"
```

### Option B: From Test Cases Page
```
1. Go to "Test Cases" page
2. Click "▶ Execute Test" button on any test case
3. Opens execution form pre-filled with test details
4. Execute same steps as Option A
```

### Test Result Options:
- **✅ PASS** - Functionality works as specified
- **❌ FAIL** - Functionality does not work
- **⏸️ BLOCKED** - Cannot test due to external reasons

---

## 📸 Uploading Evidence

### Accepted Formats:
- **Images:** JPG, PNG, GIF (screenshots)
- **Documents:** PDF (proof documents)
- **Max Size:** 5MB per file

### How to Upload:
```
1. During test execution, go to "Evidence" section
2. Click or drag screenshot to upload area
3. Supported: Screenshot, email proof, system output, etc.
4. Evidence is attached to test result
5. Appears in final reports
```

### Evidence Best Practices:
- ✓ Take clear screenshots
- ✓ Show relevant UI elements
- ✓ Include timestamps if visible
- ✓ Attach relevant logs if needed
- ✓ One evidence file per test case

---

## 📊 Viewing Results & Coverage

### Results Dashboard
```
Go to: UAT Results (📊 Results in header)

Shows:
1. Overall UAT Status (PASSED/FAILED/IN PROGRESS)
2. Summary Cards:
   - Total Tests
   - Tests Passed
   - Tests Failed
   - Tests Blocked
   - Requirements Tested
   - Pass Percentage

3. Requirement Coverage Table:
   - All requirements with status
   - Pass percentage per requirement
   - Color-coded status badges
```

### Status Meanings:
- **🟢 Passed** - All tests for requirement passed
- **🔴 Failed** - One or more tests failed
- **🟠 Blocked** - Tests blocked due to external issues
- **🔵 In Progress** - Some tests passed, some pending
- **⭕ Not Started** - No tests executed yet

---

## 📄 Generating Reports

### Report Types

#### 1. **Comprehensive Reports** (HTML/PDF)
```
Content:
✓ Title page
✓ Executive Summary
✓ Test Coverage Matrix
✓ Requirement vs Test Mapping
✓ Detailed Test Results
✓ Evidence References
✓ Pass/Fail Statistics

Download:
1. Go to "UAT Reports" page
2. Click "Generate HTML Report"
3. Report opens in browser
4. Use Ctrl+P to save as PDF
```

#### 2. **Excel/CSV Export**
```
Content:
✓ All test cases
✓ Test results
✓ Execution dates
✓ Tester notes
✓ Coverage percentages

Download:
1. Go to "UAT Reports" page
2. Click "Export to CSV"
3. Opens in Excel/Google Sheets
```

### Report Contents at a Glance:
```
┌─ Title & Date
├─ Executive Summary
│  ├─ Total Tests
│  ├─ Pass Rate
│  └─ Overall Status
├─ Coverage Summary
│  ├─ Per-requirement status
│  └─ Pass percentages
└─ Detailed Results
   ├─ Test case steps
   ├─ Execution results
   └─ Evidence references
```

---

## 🔄 Test Case Generation Logic

### Auto-Generated Test Types by Requirement

#### Functional Requirements (FR):
```
For EVERY FR:
1. ✅ Positive Test
   - Tests basic happy path functionality
   - Verifies expected behavior

2. 🔄 Edge Case Test
   - Boundary conditions
   - Special inputs
   - Limits and constraints

3. ❌ Negative Test
   - Invalid inputs
   - Error conditions
   - Error messages

Total: 3 tests per FR
```

#### Non-Functional Requirements (NFR):
```
For EVERY NFR:
1. ✅ Positive Test
   - Normal operation
   
2. 🔄 Edge Case Test
   - Boundary conditions
   
3. ❌ Negative Test
   - Error handling

4. ⚡ Performance Test (if performance-related NFR)
   - Load testing
   - Response time
   - Concurrency

Total: 3-4 tests per NFR
```

---

## 📈 Coverage Metrics

### Key Metrics Tracked:

```
1. Test Coverage
   - % of requirements with at least one test
   - Goal: 100%

2. Pass Rate
   - % of tests that passed
   - Formula: Passed / Total × 100

3. Requirement Status
   - Passed: all tests for requirement passed
   - Failed: any test for requirement failed
   - Blocked: any test blocked
   - In Progress: some tests remaining
   - Not Started: no tests executed

4. Test Completion
   - Total tests
   - Passed
   - Failed
   - Blocked
   - Not Tested
```

### Example Dashboard:
```
Overall Status: 🟢 IN PROGRESS
┌─────────────────────────────┐
│ Total Tests:        45      │ ✓ 60% Complete
│ Tests Passed:       27      │
│ Tests Failed:        5      │
│ Tests Blocked:       3      │
│ Pass Percentage:    85.7%   │
└─────────────────────────────┘

Requirements:       12/15 tested
Pass Rate:          80% (12 passed)
```

---

## 💾 Data Storage

### Where Data is Stored:
```
File-based storage (no database required):
- Test results: uat_results/{pdfHash}_results.json
- Evidence files: uat_results/{pdfHash}/evidence/
- Temp reports: uat_reports/
```

### Data Format:
```json
{
  "FR-01": {
    "FR-01.T1": {
      "status": "PASS",
      "tester_name": "John Doe",
      "execution_date": "2024-01-15 10:30:45",
      "notes": "Test passed successfully",
      "evidence_file": "evidence/FR-01.T1_1234567890_screenshot.png"
    }
  }
}
```

---

## 🔍 Advanced Features

### Filtering & Sorting
- Filter by requirement type (FR/NFR)
- Filter by test status (Pass/Fail/Blocked)
- Sort by test ID, status, date

### Tester Management
- Track which tester executed each test
- Multiple testers supported
- Timestamp automatic capture

### Evidence Management
- Upload screenshots with tests
- View evidence in reports
- Reference evidence in test notes

---

## ✅ Best Practices

### Before Testing:
- [ ] Understand all requirements
- [ ] Review test cases
- [ ] Set up test environment
- [ ] Prepare test data

### During Testing:
- [ ] Follow test steps exactly
- [ ] Take clear screenshots
- [ ] Document all failures
- [ ] Note any blockers
- [ ] Record tester name

### After Testing:
- [ ] Review all failed tests
- [ ] Investigate root causes
- [ ] Document solutions
- [ ] Re-test failed cases
- [ ] Generate final reports

### Sign-Off Process:
1. QA Lead reviews results
2. Business Analyst verifies coverage
3. Client/Stakeholder approves
4. Archive reports for compliance
5. Document sign-off date

---

## 📌 Example Workflow

### Complete UAT Cycle:
```
Step 1: Upload SRS
   └─> PDF_Reader/index.php

Step 2: View Test Cases
   └─> Sections.php → UAT Testing → Test Cases

Step 3: Execute Tests
   └─> UAT Testing → Execute Tests
       ├─ Pass/Fail button
       ├─ Upload screenshot
       └─ Save result

Step 4: Track Progress
   └─> View Results → Coverage Dashboard
       ├─ Overall status
       ├─ Per-requirement status
       └─ Pass percentages

Step 5: Generate Reports
   └─> UAT Reports → Generate
       ├─ HTML/PDF Report
       ├─ CSV Export
       └─ Share with stakeholders
```

---

## 🎯 Test Case ID Format

### ID Convention:
```
{REQUIREMENT}.[TEST_NUMBER]

Examples:
- FR-01.T1 → Functional Requirement 01, Test 1 (Positive)
- FR-01.T2 → Functional Requirement 01, Test 2 (Edge)
- FR-01.T3 → Functional Requirement 01, Test 3 (Negative)
- NFR-05.T4 → Non-Functional Requirement 05, Test 4 (Perf)
```

---

## 🛠️ Troubleshooting

### Issue: Test Cases Not Generating
**Solution:**
- Verify PDF was parsed correctly
- Check sections.php shows requirements
- Clear session and try again

### Issue: Screenshots Not Uploading
**Solution:**
- Check file size < 5MB
- Verify file type (JPG, PNG, GIF, PDF)
- Check browser permissions for upload

### Issue: Poor Report Quality
**Solution:**
- Use Print to PDF for better formatting
- Ensure all tests have results
- Attach evidence for clarity

---

## 📚 Resources

### Related Files:
```
classes/
├── UATTestCase.php           → Test generation logic
├── UATResultTracker.php      → Result storage
└── UATReportGenerator.php    → Report generation

Pages:
├── uat_test_cases.php        → View test cases
├── uat_test_execution.php    → Execute tests
├── uat_results.php           → View coverage
└── uat_reports.php           → Generate reports

Data:
├── uat_results/              → Test results storage
└── uat_reports/              → Generated reports
```

---

## 🚀 Quick Links

- **🏠 Home** → `index.php`
- **📄 View PDF** → `viewer.php`
- **📋 View Sections** → `sections.php`
- **🧪 Test Cases** → `uat_test_cases.php`
- **▶️ Execute Tests** → `uat_test_execution.php`
- **📊 View Results** → `uat_results.php`
- **📄 Generate Reports** → `uat_reports.php`

---

## 📞 Support

For issues or questions:
1. Check this documentation
2. Review test case details
3. Verify requirements interpretation
4. Test environment setup

---

**Last Updated:** April 2, 2026  
**Module Version:** 1.0  
**Status:** Production Ready

Happy Testing! 🎉
