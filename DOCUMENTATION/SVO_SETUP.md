# SVO Analysis Feature - Setup Instructions

## Overview
The PDF Reader now includes AI-powered SVO (Subject-Verb-Object) analysis for functional requirements! This feature uses Google's Gemini AI to analyze each requirement in real-time.

## What is SVO Analysis?
- **Subject**: Who or what is performing the action (e.g., "User", "System", "Admin")
- **Verb**: The action being performed (e.g., "creates", "validates", "sends")
- **Object**: What is being acted upon (e.g., "report", "data", "notification")

## Features
- 🔍 **Real-time analysis** - Click "Analyze SVO" button on any functional requirement
- 📊 **Detailed breakdown** - See subjects, verbs, objects, and more
- 🎯 **Primary components** - Identifies the main actor, action, and object
- 🏷️ **Requirement classification** - Categorizes requirement type and complexity
- 🔗 **Dependency detection** - Identifies potential dependencies
- ✨ **Beautiful UI** - Clean, modern interface with smooth animations
- 💰 **100% FREE** - Gemini API is completely free to use!

## Setup Instructions

### Step 1: Get Gemini API Key (2 minutes) - FREE!

1. Visit [https://makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account (any Gmail account works)
3. Click "Create API Key"
4. Select "Create API key in new project" or choose an existing project
5. Copy your API key (it will look like a long string of letters and numbers)

**🎉 Gemini is COMPLETELY FREE with excellent limits:**
- 60 requests per minute
- 1,500 requests per day
- No credit card required!

### Step 2: Configure the Application
1. Open `config.php` in your PDF_Reader directory
2. Find this line:
   ```php
   define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
   ```
3. Replace `YOUR_GEMINI_API_KEY_HERE` with your actual API key:
   ```php
   define('GEMINI_API_KEY', 'AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
   ```
4. Save the file

### Step 3: Test the Feature
1. Upload an SRS PDF document
2. Go to "Functional Requirements" section
3. Click the "🔍 Analyze SVO" button on any requirement
4. Wait a few seconds for the AI analysis
5. View the detailed SVO breakdown!

## Configuration Options

In `config.php`, you can customize:

```php
// Gemini model (recommended - free and powerful)
define('GEMINI_MODEL', 'gemini-pro');

// You can also use advanced models:
// define('GEMINI_MODEL', 'gemini-1.5-pro'); // More capable
// define('GEMINI_MODEL', 'gemini-1.5-flash'); // Faster

// Adjust API timeout (in seconds)
define('API_TIMEOUT', 30);

// Enable/Disable SVO Analysis
define('ENABLE_SVO_ANALYSIS', true);
```

## Pricing Information

**🎉 COMPLETELY FREE!**

Gemini API is 100% free with generous limits:
- **60 requests per minute** - Perfect for real-time analysis
- **1,500 requests per day** - More than enough for daily work
- **No credit card required** - Just sign in with Google
- **No usage charges** - Completely free forever

This means you can:GEMINI_API_KEY_HERE` in `config.php`
- Check that your API key looks like `AIzaSy...`
- Ensure there are no extra spaces or quotes

### "Invalid API key" error
- Verify your API key is correct
- Make sure you're signed in to Google AI Studio
- Try regenerating a new API key if needed

### "Rate limit exceeded" error
- You've made more than 60 requests in one minute
- Wait a minute and try again
- This is very rare in normal usage

### Analysis takes too long
- Check your internet connection
- Increase `API_TIMEOUT` in `config.php`
- Gemini is usually very fast (1-3 seconds
- You've made too many requests too quickly
- Wait a minute and try again
- Consider upgrading your OpenAI plan

### Analysis takes too long
- Check your internet connection
- Increase `API_TIMEOUT` in `config.php`
- Try switching to GPT-3.5 (faster than GPT-4)

## How It Works

1. When you click "Analyze SVO", the requirement text is sent to `analyze_requirement.php`
2. The `SVOAnalyzer` class formats a specialized prompt for Gemini AI
3. Google's Gemini model analyzes the requirement structure
4. The response is parsed and displayed in a beautiful UI
5. Results are shown instantly with subjects, verbs, objects, and more!

## Why Gemini?

**Advantages over other AI services:**
- ✅ **100% Free** - No credit card, no charges
- ✅ **Generous limits** - 60 req/min, 1,500 req/day
- ✅ **High quality** - Excellent analysis accuracy
- ✅ **Fast** - Typically 1-3 seconds response time
- ✅ **Easy setup** - Just Google account needed
- ✅ **Reliable** - Google's infrastructure

## Files Added/Modified

**New Files:**
- `config.php` - API configuration
- `classes/SVOAnalyzer.php` - AI analysis engine
- `analyze_requirement.php` - AJAX endpoint
- `SVO_SETUP.md` - This file

**Modified Files:**
- `sections.php` - Added SVO UI and JavaScript

**Existing Functionality:**
- ✅ All existing features work exactly as before
- ✅ PDF parsing unchanged
- ✅ Section extraction unchanged
- ✅ No database changes required
 at [Google AI Studio](https://makersuite.google.com/app/apikey)
3. Ensure you have internet connectivity
4. Check Google AI service status

---

**Enjoy your FREE
## Support
If you encounter any issues:
1. Check the browser console for JavaScript errors
2. Verify your API key is valid
3. Ensure you have internet connectivity
4. Check OpenAI service status: [https://status.openai.com](https://status.openai.com)

---

**Enjoy your new AI-powered SVO analysis! 🚀**
