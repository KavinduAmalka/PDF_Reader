# 🚀 Testing the Sub-Requirements Feature Fix

## Quick Test Walkthrough

### Prerequisites
- Ensure a PDF has been uploaded and parsed in the system
- The PDF should have requirements with sub-requirements (e.g., FR-20.01, FR-20.02)

### Test Scenario: Execute a Negative Test for Sub-Requirement

#### Step 1: Open Dashboard
```
URL: http://localhost/PDF_Reader/uat_test_cases_enhanced.php
```
**Expected Result:**
- Shows hierarchical structure: `FR-20` > [`FR-20.01`, `FR-20.02`, `FR-20.03`]
- Each sub-requirement shows 4 test cards
- Test names vary: `[POSITIVE] ...`, `[NEGATIVE] ...`, `[EDGE CASE] ...`, `[ALTERNATE] ...`

#### Step 2: Find Sub-Requirement with Multiple Tests
Look for requirement like `FR-20.01` with 4 test cards below it.

#### Step 3: Click Execute on Negative Test
- Find the red test card with `[NEGATIVE]` label
- Click the "Execute" button (might be a link or button on the card)

**Expected Result:**
- Navigates to: `http://localhost/PDF_Reader/uat_test_execution.php?req=FR-20.01&tc=FR-20.01_TC_002`

#### Step 4: Verify Execution Page Loaded Correctly
Check the following:

**Sidebar:**
- [ ] Shows `FR-20` (parent) highlighted
- [ ] Shows sub-requirements under it: `FR-20.01`, `FR-20.02`, `FR-20.03`
- [ ] `FR-20.01` is highlighted/active

**Main Content - Test Type Selector:**
- [ ] Shows 4 colored buttons:
  - [ ] `✓ Positive` (green)
  - [ ] `✗ Negative` (red) - **should be active/highlighted**
  - [ ] `🔄 Edge Case` (orange)
  - [ ] `🔀 Alternate` (purple)

**Main Content - Test Case Header:**
- [ ] Title shows: `[NEGATIVE] ... - Invalid Input` (not `[POSITIVE]`)
- [ ] ID shows: `FR-20.01_TC_002` (not `FR-20.01_TC_001`)

#### Step 5: Switch to Different Test Type
- Click the `✓ Positive` button

**Expected Result:**
- Test details change
- Header shows: `[POSITIVE] ... - Happy Path`
- ID shows: `FR-20.01_TC_001`

#### Step 6: Switch to Edge Case
- Click the `🔄 Edge Case` button

**Expected Result:**
- Test details change
- Header shows: `[EDGE CASE] ... - Boundary Conditions`
- ID shows: `FR-20.01_TC_003`

#### Step 7: Switch to Alternate Flow
- Click the `🔀 Alternate` button

**Expected Result:**
- Test details change  
- Header shows: `[ALTERNATE FLOW] ... - Alternate Scenario`
- ID shows: `FR-20.01_TC_004`

#### Step 8: Execute Test
1. Enter your name in "Tester Name" field
2. Select result: "✓ Pass"
3. Add note: "Test executed successfully"
4. Click "💾 Save Result"

**Expected Result:**
- Shows: `✅ Test result saved successfully!`
- Result saved for `FR-20.01_TC_002` (negative test)

#### Step 9: Test Different Sub-Requirement
- Click `FR-20.02` in the sidebar

**Expected Result:**
- Loads all 4 tests for `FR-20.02`
- Shows 4 test type buttons for `FR-20.02` tests
- Can execute `FR-20.02_TC_001`, `TC_002`, `TC_003`, `TC_004` independently

---

## Checklist: What Should Work

### Dashboard (uat_test_cases_enhanced.php)
- [ ] Loads all test cases from ComprehensiveTestCaseGenerator
- [ ] Groups sub-requirements under parent (FR-20 > FR-20.01)
- [ ] Shows 4 test cards per sub-requirement
- [ ] Each test card has unique name: [POSITIVE], [NEGATIVE], [EDGE CASE], [ALTERNATE]
- [ ] Execute button works and links to correct test

### Execution Page (uat_test_execution.php)
- [ ] Shows hierarchical sidebar: Parent > Sub-requirements
- [ ] Shows 4 test type buttons (Positive, Negative, Edge, Alternate)
- [ ] Each button is clickable and switches test details
- [ ] Test name changes when switching test type
- [ ] Test ID format correct: `FR-20.01_TC_001`, `TC_002`, `TC_003`, `TC_004`
- [ ] Test description varies per type (Happy Path, Invalid Input, Boundary, Alternate)
- [ ] Form pre-fills with test data
- [ ] "Save Result" button works
- [ ] Results saved with correct sub-requirement code

### Test Generator (ComprehensiveTestCaseGenerator)
- [ ] Detects sub-requirements from content array
- [ ] Generates 4 tests per sub-requirement
- [ ] Test IDs include sub-requirement code: FR-20.01_TC_001
- [ ] Test names start with: [POSITIVE], [NEGATIVE], [EDGE CASE], [ALTERNATE]
- [ ] Test names are unique and descriptive
- [ ] Test types vary: Positive, Negative, Edge Case, Alternate Flow

---

## What Was Fixed

| Issue | Before | After | Status |
|-------|--------|-------|--------|
| Test names identical | All `[POSITIVE]` | Unique per type | ✅ FIXED |
| Sidebar shows sub-reqs | ❌ No | ✅ Yes, hierarchical | ✅ FIXED |
| Execute button works | ❌ Blank page | ✅ Loads with sub-req | ✅ FIXED |
| All 4 test types shown | ❌ Only 1 | ✅ 4 buttons | ✅ FIXED |
| URL parameters | Old format | New format (FR-20.01) | ✅ FIXED |
| Results per sub-req | ❌ No | ✅ Yes | ✅ FIXED |

---

## Troubleshooting

### Problem: "Select a requirement to view test cases"
- **Cause:** No PDF uploaded or no requirements found
- **Solution:** Upload and parse PDF first via main interface

### Problem: Sidebar shows only main requirements
- **Cause:** PDF doesn't have sub-requirements or ComprehensiveTestCaseGenerator not generating them
- **Solution:** Check if PDF has sub-requirements (e.g., "1.1", "1.2" under "1")

### Problem: All test names show `[POSITIVE]`
- **Cause:** Old UATTestCase still being used
- **Solution:** Restart PHP/web server, clear browser cache

### Problem: Sidebar shows parent but not subs
- **Cause:** Detection logic issue in execution page
- **Solution:** Check if sub-requirement codes are in $allTestCases keys

### Problem: Test names show "Happy Path" for all tests
- **Cause:** Test name generation not including test type
- **Solution:** Check ComprehensiveTestCaseGenerator line with test_name assignment

---

## File Locations

### Modified Files
- `uat_test_execution.php` - Complete rewrite to support sub-requirements
- `classes/ComprehensiveTestCaseGenerator.php` - Enhanced test names

### Backup
- `uat_test_execution_old.php` - Original version (for reference)

### Documentation
- `EXECUTION_PAGE_FIX_SUMMARY.md` - Detailed technical changes
- `this.file` - Quick test guide

### Testing
- `test_execution_integration.php` - Automated integration tests

---

## Performance Notes

The new execution page:
- Loads all test cases into memory (ComprehensiveTestCaseGenerator)
- Builds requirement hierarchy dynamically
- Uses simple array lookups for requirement/test selection
- Should handle 100+ requirements with sub-requirements efficiently

---

## Next Steps

After verifying everything works:

1. **Test Other Requirements:** Try FR-21, NFR-01, etc.
2. **Test Result Tracking:** View saved results in uat_results.php
3. **Generate Reports:** Check if reports show sub-requirement level detail
4. **Load Testing:** Parse large PDFs and verify performance

---

**Status:** ✅ All fixes implemented and tested
**Validation:** Integration tests passed
**Ready for:** User browser testing

