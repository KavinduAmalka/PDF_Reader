# UAT Testing Module - Sub-Requirements Feature Update

## 📋 New Feature: Separate Test Cases for Sub-Requirements

### 🎯 What Changed?

The UAT Testing Module now generates **separate test cases for each sub-requirement** instead of combining all sub-items into one requirement.

#### Before ❌
```
FR-20 (Main Requirement) → 4 test cases
  - Combines all sub-requirements (FR-20.01, FR-20.02, FR-20.03)
  - 4 generic tests for the entire FR-20
```

#### After ✅
```
FR-20 (Main Requirement) → Multiple test case groups
  ├── FR-20.01 (Sub-requirement 1) → 4 specific test cases
  ├── FR-20.02 (Sub-requirement 2) → 4 specific test cases
  ├── FR-20.03 (Sub-requirement 3) → 4 specific test cases
  └── ...more sub-requirements → 4 specific test cases each
```

---

## 🔍 How It Works

### Requirement Structure

Structured requirements have sub-requirements identified and extracted from the SRS:

```
FR-20: User Authentication
  ├── FR-20.01: Password Validation
  │   └── Must validate password strength (uppercase, lowercase, numbers, symbols)
  ├── FR-20.02: Session Management
  │   └── Must create secure session tokens
  └── FR-20.03: MFA Support
      └── Must support multi-factor authentication
```

### Test Case Generation Logic

For each sub-requirement, the system generates **4 distinct test cases**:

1. **Positive Test Case** ✅ - Happy path scenario
   - Example: FR-20.01_TC_001 - Test valid password acceptance
   
2. **Negative Test Case** ❌ - Error handling
   - Example: FR-20.01_TC_002 - Test weak password rejection
   
3. **Edge Case Test Case** 🔄 - Boundary conditions  
   - Example: FR-20.01_TC_003 - Test maximum password length
   
4. **Alternate Flow Test Case** 🔀 - Alternative scenarios
   - Example: FR-20.01_TC_004 - Test password reset flow

---

## 📊 Dashboard Display

### Requirement Grouping

Main requirements are displayed as collapsible groups with sub-requirements nested inside:

```
┌─────────────────────────────────────────┐
│ FR-20 (3 sub-requirements) → 12 tests   │  ← Parent container
├─────────────────────────────────────────┤
│ ┌─── FR-20.01 → 4 tests ─────────────┐  │
│ │  [✓ Positive] [✗ Negative]         │  │
│ │  [🔄 Edge] [🔀 Alternate]          │  │
│ └────────────────────────────────────┘  │
│ ┌─── FR-20.02 → 4 tests ─────────────┐  │
│ │  [✓ Positive] [✗ Negative]         │  │
│ │  [🔄 Edge] [🔀 Alternate]          │  │
│ └────────────────────────────────────┘  │
│ ┌─── FR-20.03 → 4 tests ─────────────┐  │
│ │  [✓ Positive] [✗ Negative]         │  │
│ │  [🔄 Edge] [🔀 Alternate]          │  │
│ └────────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

---

## 🎯 Test Case ID Format

### New Test Case ID Structure

```
{SUB_REQUIREMENT_CODE}_{TEST_TYPE}_{TEST_NUMBER}

Examples:
├── FR-20.01_TC_001 = FR-20.01 Positive Test
├── FR-20.01_TC_002 = FR-20.01 Negative Test
├── FR-20.01_TC_003 = FR-20.01 Edge Case Test
├── FR-20.01_TC_004 = FR-20.01 Alternate Flow Test
├── FR-20.02_TC_001 = FR-20.02 Positive Test
└── ... and so on
```

### Old vs New Format

| Old Format | New Format | Benefit |
|-----------|-----------|---------|
| FR-20_TC_001 | FR-20.01_TC_001 | Identifies exact sub-requirement |
| FR-20_TC_002 | FR-20.02_TC_001 | Traceable to specific sub-item |
| FR-20_TC_003 | FR-20.03_TC_001 | Better test organization |
| FR-20_TC_004 | FR-20.04_TC_001 | Clearer test mapping |

---

## 📈 Statistics Impact

### Before
- **Total Requirements:** 42
- **Total Test Cases:** 168 (42 × 4)

### After (with Sub-Requirements)  
- **Total Requirements:** 120 (if 42 main reqs have avg 2.85 sub-reqs each)
- **Total Test Cases:** 480 (120 × 4)
- **Better Coverage:** More granular testing of individual features

---

## 🔍 Filtering with Sub-Requirements

All filtering features work seamlessly with sub-requirements:

### Example: Filter Password-Related Tests
1. Open **UAT Test Cases (Enhanced)**
2. Use **Search:** type `password`
3. Results show:
   - FR-20.01 sub-requirement cards matching "password"
   - All related test cases (Positive, Negative, Edge, Alternate)
   - Exact FR-20.01_TC_001, FR-20.01_TC_002, etc.

### Example: Filter by Requirement Type
1. **Requirement Type:** Select `Functional (FR)`
2. Results show:
   - Only FR-XX requirements (all sub-requirements)
   - Excludes NFR requirements
   - Complete grouping maintained

### Example: Filter by Test Type
1. **Test Type:** Select `Edge Case`
2. Results show:
   - Only Edge Case tests (TC_003) for all sub-requirements
   - Across all requirements
   - Still organized by parent requirement

---

## ✅ Test Execution with Sub-Requirements

When executing tests:

```
1. Click "Execute →" on any sub-requirement test card
2. Navigate to Execution page
3. Requirement field shows: FR-20.01 (not just FR-20)
4. Test case shows specific sub-requirement
5. Evidence links to exact sub-requirement
6. Results tracked against FR-20.01
```

### Execution URL Format
```
uat_test_execution.php?req=FR-20.01&tc=FR-20.01_TC_001

Components:
- req = Sub-requirement code (FR-20.01)
- tc = Test case ID (FR-20.01_TC_001)
```

---

## 📊 Results & Reporting with Sub-Requirements

### Coverage Tracking

Results now show sub-requirement level coverage:

```
Requirement Coverage:
├── FR-20 (Main)
│   ├── FR-20.01 (Password Validation): 75% (3/4 passed)
│   ├── FR-20.02 (Session Management): 100% (4/4 passed)
│   └── FR-20.03 (MFA Support): 50% (2/4 passed)
```

### Test Results Breakdown

Each test result is linked to specific sub-requirement:
- Test execution history per FR-20.01, FR-20.02, etc.
- Pass/Fail status by sub-requirement
- Evidence organized by sub-requirement

### Report Generation

Reports now include:
- Sub-requirement mapping (which sub-req each test covers)
- Pass rate per sub-requirement
- Detailed traceability matrix (FR-20.01 → Test Cases → Results)

---

## 🛠️ Technical Implementation

### Data Structure Changes

**Inside `ComprehensiveTestCaseGenerator.php`:**

```php
// Before: One entry per main requirement
$testCases = [
    'FR-20' => [
        [test1], [test2], [test3], [test4]
    ]
];

// After: One entry per sub-requirement
$testCases = [
    'FR-20.01' => [[test1], [test2], [test3], [test4]],
    'FR-20.02' => [[test1], [test2], [test3], [test4]],
    'FR-20.03' => [[test1], [test2], [test3], [test4]]
];
```

### Grouping Mechanism

**In `uat_test_cases_enhanced.php`:**

The `groupTestCasesByParent()` function reorganizes flat sub-requirement tests into hierarchical structure:

```php
Input:  'FR-20.01' → tests, 'FR-20.02' → tests, 'FR-20.03' → tests
Output: 'FR-20' → ['FR-20.01' → tests, 'FR-20.02' → tests, 'FR-20.03' → tests]
```

This provides:
- Parent grouping in UI
- Easy navigation
- Sub-requirement organization
- Backward compatible structure

---

## 🔄 Migration Notes

### For Existing Projects

If you have existing test results from **before** this update:
- Old results are still accessible
- New tests use sub-requirement codes
- Reports can show both old and new test cases
- No data loss, full backward compatibility

### For New Projects

All new tests automatically use sub-requirement codes:
- Better granularity
- Clearer traceability
- Easier debugging
- More comprehensive coverage

---

## 📚 Best Practices

### When Testing Sub-Requirements

1. **Execute in Order**
   - Test FR-20.01 (Password Validation) first
   - Then FR-20.02 (Session Management)
   - Finally FR-20.03 (MFA Support)

2. **Prioritize by Risk**
   - Test critical sub-requirements first
   - Then optional/alternate features
   - Use Priority field to guide test order

3. **Evidence per Sub-Requirement**
   - Capture screenshots for FR-20.01 execution
   - Separate evidence for FR-20.02
   - Organize uploads by sub-requirement

4. **Report by Sub-Requirement**
   - Generate sub-requirement level reports
   - Show coverage per sub-requirement
   - Identify gaps in specific sub-features

---

## 🐛 Troubleshooting

### Sub-Requirements Not Appearing

✓ Check: Document has properly formatted sub-requirements  
✓ Pattern: `FR-20.01: Description` (colon after code)  
✓ Verify: SRS includes numbered sub-items  

### Test Cases Not Generating for Sub-Reqs

✓ Ensure: PDF parsing extracts sub-requirement content  
✓ Check: `$reqData['content']` array has items  
✓ Review: SRSParser correctly extracted sections  

### Filtering Sub-Requirements

✓ Use: Main requirement code in filters  
✓ Or: Sub-requirement code in search  
✓ Try: Collapsing/expanding parent requirement  

---

## 🎓 Examples

### Example 1: Simple Requirement
No sub-requirements detected:
```
FR-15: User Registration

Result:
├── FR-15_TC_001 (Positive)
├── FR-15_TC_002 (Negative)
├── FR-15_TC_003 (Edge)
└── FR-15_TC_004 (Alternate)
```

### Example 2: Complex Requirement with Sub-Reqs
```
FR-20: User Authentication
├── FR-20.01: Password Validation
├── FR-20.02: Session Management
├── FR-20.03: MFA Support
└── FR-20.04: Account Lockout

Result: 4 sub-reqs × 4 tests = 16 total test cases
├── FR-20.01_TC_001, _TC_002, _TC_003, _TC_004
├── FR-20.02_TC_001, _TC_002, _TC_003, _TC_004
├── FR-20.03_TC_001, _TC_002, _TC_003, _TC_004
└── FR-20.04_TC_001, _TC_002, _TC_003, _TC_004
```

---

## 📞 Questions?

For issues with sub-requirement:
- **Test generation:** Check `ComprehensiveTestCaseGenerator.php`
- **UI grouping:** Check `uat_test_cases_enhanced.php` grouping functions
- **Data extraction:** Check `SRSParser.php` content extraction
- **Results:** Check how sub-requirement codes are stored in results

---

## ✅ Feature Checklist

- [x] Generate separate test cases for each sub-requirement
- [x] Display sub-requirements in hierarchical UI
- [x] Maintain parent-child requirement relationship
- [x] Update test case ID format (FR-20.01_TC_001)
- [x] Filtering works with sub-requirement codes
- [x] Search finds sub-requirement tests
- [x] Execution links to correct sub-requirement
- [x] Results track sub-requirement level
- [x] Reports include sub-requirement mapping
- [x] Backward compatible with existing data

---

**Sub-Requirements Feature is now live! 🚀**

You now get more granular, targeted testing with separate test cases for each sub-requirement!
