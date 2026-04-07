# 🔧 Troubleshooting Guide

## Common Issues & Solutions

### Issue 1: "PDFFile upload fails"

#### Symptom:
```
"Only PDF files are allowed" or "Invalid file type"
```

#### Causes & Solutions:

**A) File is not actually a PDF**
```
✓ Solution: Ensure file is a valid PDF
✓ Test: Try a sample PDF from templates/ folder
✓ Check: File extension is .pdf (not .PDF)
```

**B) MIME type check failing**
```
✓ Solution: Some MIME type detectors are strict
✓ Workaround: Modify index.php line ~30 to remove MIME check:
   // Commented out: elseif ($fileType !== 'application/pdf')
✓ Reason: Extension check is sufficient security
```

**C) File size too large**
```
✓ Limit: 10MB maximum
✓ Solution: Compress PDF or split into smaller documents
✓ Modify: In index.php, search for "10 * 1024 * 1024" to change limit
```

**D) Permissions issue**
```
✓ Check: uploads/ directory has write permissions
✓ Command: chmod 755 uploads/
✓ Windows: Right-click → Properties → Security → Edit
```

---

### Issue 2: "PDFtext not extracting correctly"

#### Symptom:
```
Sections show as empty or have garbled text
Requirements not parsed properly
```

#### Causes & Solutions:

**A) PDF has scanned images (no text layer)**
```
✓ Problem: OCR needed (out of scope for this app)
✓ Solution: Use PDF with embedded text
✓ Check: Try opening PDF in Acrobat Reader, select text
✓ Workaround: Use Copy-paste to get text, create text-based PDF
```

**B) PDF uses non-standard encoding**
```
✓ Check: Try sample PDF from templates/
✓ Solution: Re-save PDF with UTF-8 encoding
✓ Tool: Use Adobe or online PDF tools to re-encode
```

**C) Special characters causing issues**
```
✓ Check: SRSParser handling special characters
✓ Workaround: Use simpler PDF without special fonts
✓ See: classes/SRSParser.php lines 40-60
```

---

### Issue 3: "Test cases not generating"

#### Symptom:
```
Test cases page shows "No test cases generated"
or displays empty table
```

#### Causes & Solutions:

**A) PDF not uploaded or parsed**
```
✓ Check: Go back to index.php and upload PDF first
✓ Verify: sections.php shows extracted requirements
✓ If not:
   - Try a different PDF
   - Check browser console (F12) for errors
   - Check PHP logs
```

**B) Requirements not identified**
```
✓ Check: Requirement identification
✓ Look for: Requirements starting with FR- or NFR-
✓ Example: "FR-10.01: System shall..."
✓ If not found:
   - PDF might have custom format
   - Edit SRSParser.php to add pattern
   - See lines 20-30 for regex patterns
```

**C) Gemini API key issue**
```
✓ Check: .env file has GEMINI_API_KEY set
✓ Test: Run this PHP script:
   <?php
   require 'config.php';
   echo "API Key: " . GEMINI_API_KEY;
   echo "Key length: " . strlen(GEMINI_API_KEY);
   ?>
✓ If error: 
   - Ensure key is from Google Cloud Console
   - Check it's not expired
   - Verify it's enabled for Generative AI API
```

**D) JavaScript error**
```
✓ Action: Open browser console (F12 → Console)
✓ Look for: Red errors in console
✓ Common: "fetch() failed" - check network tab
✓ Solution: Check browser developer tools
```

---

### Issue 4: "Test cases look generic (not specific)"

#### Symptom:
```
Test case names all same: "[POSITIVE] User Authentication - Happy Path"
Test steps mention "module" but not specific field name
```

#### Causes & Solutions:

**A) Old code version**
```
✗ Wrong: Using uat_test_cases.php
✓ Right: Use uat_test_cases_enhanced.php
✓ Check: URL should show "uat_test_cases_enhanced.php"
```

**B) ComprehensiveTestCaseGenerator not updated**
```
✓ Verify: classes/ComprehensiveTestCaseGenerator.php exists
✓ Check: File has methods like extractRequirementContext()
✓ If not:
   - Restore from backup
   - Make sure enhancement was applied
```

**C) Cached files in browser**
```
✓ Action: Clear browser cache
   - Ctrl+Shift+Delete
   - Or F12 → Application → Clear cache
✓ Then: Reload page
```

---

### Issue 5: "Execution page doesn't load"

#### Symptom:
```
uat_test_execution.php shows blank or error
"Execution page not found"
```

#### Causes & Solutions:

**A) Session expired**
```
✓ Action: Go back to sections.php (maintains session)
✓ Or: Upload PDF again
✓ Then: Try execution page again
```

**B) No test cases selected**
```
✓ Action: Go to uat_test_cases_enhanced.php first
✓ Select: A specific requirement
✓ Then: Click "Execute Tests" button
✓ Don't: Try accessing execution page directly
```

**C) PHP error on page**
```
✓ Check: Browser console (F12 → Console)
✓ Check: Server PHP error logs
✓ Location of logs:
   Linux/Mac: /var/log/apache2/error.log
   Windows/WAMP: C:\wamp64\logs\php_error.log
```

---

### Issue 6: "Results not saving"

#### Symptom:
```
After executing test, results page shows no data
Or: Error "Cannot write to results directory"
```

#### Causes & Solutions:

**A) Directory permissions**
```
✓ Check: uat_results/ directory exists and is writable
✓ Linux: chmod 755 uat_results/
✓ Windows: Right-click → Properties → Security → Edit
✓ Add: Full Control for current user
```

**B) Disk space issue**
```
✓ Check: Available disk space
✓ Linux: df -h
✓ Windows: Check D: or C: drive free space
✓ Solution: Free up disk space
```

**C) Session timeout**
```
✓ Problem: Session variables lost
✓ Check: PHP.ini session.gc_maxlifetime
✓ Default: 1440 seconds (24 minutes)
✓ Solution: Complete testing within 24 minutes
   Or: Increase timeout in config.php
```

**D) Browser security restriction**
```
✓ Check: Browser console for CORS errors
✓ Solution: Ensure same domain/port
✓ Don't: Access file:// directly, use localhost
```

---

### Issue 7: "Gemini API errors"

#### Symptom:
```
"API request failed" or "Invalid API key"
JavaScript console shows CORS error
```

#### Causes & Solutions:

**A) Invalid API key**
```
✓ Check: Key in .env is correct
✓ Get key: https://ai.google.dev/
✓ Verify: 39+ character long string
✓ Test: Create simple test script:
   curl "https://generativelanguage.googleapis.com/v1beta/models?key=YOUR_KEY"
```

**B) API quota exceeded**
```
✓ Check: Google Cloud Console usage
✓ Limit: Free tier has generous limit
✓ Solution: API is free, quota very high
✓ Note: Cost only if you exceed free tier
```

**C) Network/Firewall blocking**
```
✓ Check: Firewall allows outbound HTTPS
✓ Test: Try curl from command line:
   curl https://generativelanguage.googleapis.com/
✓ If blocked: 
   - Contact IT department
   - Use proxy if available
```

**D) CORS issue (browser)**
```
✓ Cause: Cross-origin request blocked
✓ Solution: API call should be server-side only
✓ Check: analyze_requirement.php makes the call
✓ Browser: Just receives JSON response
```

---

### Issue 8: "PDF viewer not working"

#### Symptom:
```
viewer.php shows blank or "PDF cannot be displayed"
```

#### Causes & Solutions:

**A) PDF not found**
```
✓ Check: PDF uploaded successfully
✓ Look: Browser Network tab (F12 → Network)
✓ Verify: serve_pdf.php returns 200 status
```

**B) Browser doesn't support PDF.js**
```
✓ Check: Browser is modern (Chrome, Firefox, Safari, Edge)
✓ Update: Browser to latest version
✓ Test: In different browser
```

**C) File path issue**
```
✓ Check: uploads/ directory has PDF files
✓ Linux: ls -la uploads/
✓ Windows: dir uploads
✓ Verify: Filenames have no spaces/special chars
```

---

### Issue 9: "Cannot access localhost:8080"

#### Symptom:
```
Docker container not responding
Browser shows "connection refused"
```

#### Causes & Solutions:

**A) Docker container not running**
```
✓ Check: docker ps
✓ If not listed, start it:
   docker-compose up -d
✓ Wait: 5-10 seconds for startup
```

**B) Port already in use**
```
✓ Check: netstat -an | grep 8080 (Linux/Mac)
✓ Or: netstat -ano | findstr :8080 (Windows)
✓ Solution: Kill process or change port in docker-compose.yml
```

**C) Network access issue**
```
✓ Try: http://127.0.0.1:8080 instead of localhost
✓ Or: http://192.168.x.x:8080 (if accessing from another machine)
✓ Check: Firewall port 8080 is open
```

---

### Issue 10: "Sub-requirements not showing"

#### Symptom:
```
Sidebar in test cases page doesn't show sub-requirement hierarchy
```

#### Causes & Solutions:

**A) PDF doesn't have sub-requirements**
```
✓ Check: Parent requirement in PDF
✓ Look for: FR-10.01, FR-10.01.01, FR-10.01.02 (sub-requirements)
✓ Solution: Use PDF with hierarchical structure
✓ Try: Sample PDF from templates/ folder
```

**B) Parser not detecting sub-requirements**
```
✓ Check: SRSParser.php extractSubRequirements() method
✓ Pattern: Looks for XXX.XX.XX format
✓ Modify: If your PDF uses different numbering
✓ See: classes/SRSParser.php lines 85-100
```

**C) JavaScript not loading**
```
✓ Check: Browser console for JS errors
✓ Verify: HTML has proper DOM structure
✓ Clear: Browser cache (Ctrl+Shift+Delete)
```

---

## Diagnostic Checklist

### When Something Doesn't Work:

1. **Check Browser Console**
   ```
   F12 → Console tab
   Look for red errors
   Copy error message
   ```

2. **Check PHP Error Log**
   ```
   Linux: tail -f /var/log/apache2/error.log
   Windows WAMP: C:\wamp64\logs\
   ```

3. **Test Individually**
   - Can you upload PDF? (index.php)
   - Can you see sections? (sections.php)
   - Can you generate tests? (uat_test_cases_enhanced.php)
   - Can you execute? (uat_test_execution.php)
   - Can you view results? (uat_results.php)

4. **Verify File Permissions**
   ```
   uploads/ - 755
   uat_results/ - 755
   .env - 600 (readable by web server)
   ```

5. **Test with Sample Files**
   - Use templates/ PDFs instead of custom
   - If sample works, problem is with your PDF
   - If sample doesn't work, infrastructure issue

6. **Check Configuration**
   ```php
   // config.php should have all settings
   GEMINI_API_KEY  ✓
   VERIFY_SSL      ✓
   Other defaults  ✓
   ```

---

## Getting Help

### Information to Gather:

1. **Browser Details**
   - Browser name and version
   - Operating system
   - Console errors (F12)

2. **Server Details**
   - PHP version: `php -v`
   - Apache/Nginx version
   - cURL enabled: `php -m | grep curl`

3. **Application Details**
   - PDF filename
   - PDF size
   - Requirement format (FR-XX.XX)

4. **Logs**
   - PHP error log contents
   - Browser network tab (F12 → Network)
   - Console errors (F12 → Console)

### Where to Check:

- **Error Logs:**
  - `/var/log/apache2/error.log` (Linux)
  - `C:\wamp64\logs\apache_error.log` (Windows WAMP)
  - `~/.config/gcloud/` (GCP logs)

- **Application Files:**
  - `config.php` - Check all settings
  - `.env` - Check API key is set
  - `classes/` - Check for syntax errors

---

## Quick Reference

### File Locations:
| File | Purpose |
|------|---------|
| index.php | PDF upload |
| sections.php | View requirements |
| uat_test_cases_enhanced.php | Generate tests |
| uat_test_execution.php | Execute tests |
| uat_results.php | View results |
| classes/ | Business logic |
| uploads/ | PDFs storage |
| uat_results/ | Test results |

### Key Classes:
| Class | Purpose |
|-------|---------|
| PDFParser | Extract text from PDF |
| SRSParser | Parse SRS structure |
| SVOAnalyzer | AI semantic analysis |
| ComprehensiveTestCaseGenerator | Generate test cases |
| UATResultTracker | Save test results |

### Configuration Keys:
| Key | Purpose |
|-----|---------|
| GEMINI_API_KEY | Google AI API key |
| VERIFY_SSL | SSL verification flag |

---

## Still Need Help?

1. **Check this document** - Symptom matching
2. **Review code comments** - In affected classes
3. **Check browser console** - For JavaScript errors
4. **Review PHP logs** - For server errors
5. **Try sample PDF** - From templates/ folder

**Most issues are solved by:**
- ✅ Clearing browser cache
- ✅ Checking API key configuration
- ✅ Verifying file permissions
- ✅ Using a different/sample PDF

---

