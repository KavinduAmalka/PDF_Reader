# QUICK START GUIDE - SVO Analysis

## 🚀 Get Started in 3 Minutes! (100% FREE)

### Step 1: Get Your FREE API Key (2 minutes)

1. Go to: **https://makersuite.google.com/app/apikey**
2. Sign in with your Google account (any Gmail works)
3. Click **"Create API Key"**
4. Select **"Create API key in new project"**
5. Copy the key (looks like: `AIzaSy...`)

**🎉 Completely FREE - No credit card needed!**

### Step 2: Add API Key (30 seconds)

1. Open: **`c:\wamp64\www\PDF_Reader\config.php`**
2. Find this line:
   ```php
   define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
   ```
3. Replace with your key:
   ```php
   define('GEMINI_API_KEY', 'AIzaSy-xxxxx');
   ```
4. Save the file (Ctrl+S)

### Step 3: Test It! (30 seconds)

1. Start WAMP
2. Open: **http://localhost/PDF_Reader**
3. Upload an SRS PDF
4. Go to "Functional Requirements"
5. Click **"🔍 Analyze SVO"** button
6. See the magic happen! ✨

## Example Output

When you click "Analyze SVO", you'll see:

```
📊 SVO Analysis: FR-07.1

"The system validates user credentials and grants access"

📍 Subjects (Who/What)
• System
• User

⚡ Verbs (Actions)
• Validates
• Grants

🎯 Objects (What)
• User credentials
• Access

🎯 Primary Components
Primary Actor: System
Primary Action: Validates
Primary Object: User credentials

📝 Analysis Details
Type: Functional
Complexity: Medium

Summary: The system performs credential validation 
and provides access control based on authentication results.

🔗 Dependencies & Related Components
• Authentication module
• User database
• Access control system
```

## That's It!

**Now you have:**
- ✅ Real-time AI analysis
- ✅ Structured SVO breakdown
- ✅ Requirement classification
- ✅ Dependency detection
- ✅ Beautiful UI

## Cost

**🎉 COMPLETELY FREE!**

- Analyze **UNLIMITED requirements** for FREE!
- 60 requests per minute
- 1,500 requests per day
- No credit card required
- No charges ever!
- Just need a Google account

## Need Help?

**English Guide:** `SVO_SETUP.md`
**Sinhala Guide:** `SVO_SETUP_SINHALA.md`

---

**Happy analyzing! 🎉 (And it's FREE forever!)**
