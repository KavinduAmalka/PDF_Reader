# PDF Reader with SRS Parser

A PHP-based PDF reader with advanced parsing capabilities for Software Requirements Specification (SRS) documents.

## Features

- 📄 **PDF Upload & Viewing** - Upload and view PDF documents in your browser
- 📑 **SRS Document Parsing** - Automatically parse SRS documents into sections
- 🔍 **Section Extraction** - Extract specific sections like Functional Requirements, Non-Functional Requirements
- 📊 **Line-by-Line Reading** - View each section line by line
- 🎯 **Regex-based Parsing** - Uses advanced regex patterns to identify document structure
- 💾 **Session Management** - Maintains uploaded documents during your session
- 🤖 **AI-Powered SVO Analysis** ✨ NEW! - Real-time Subject-Verb-Object analysis using Google Gemini AI (100% FREE)
- 📈 **Requirement Classification** - Automatic classification of requirement type and complexity
- 🔗 **Dependency Detection** - Identifies potential dependencies between requirements

## Installation

### Prerequisites

- PHP 7.4 or higher
- Web server (Apache/Nginx) or WAMP/XAMPP
- Composer (optional, for enhanced PDF parsing)

### Basic Setup

1. Clone or download this repository to your web server directory:
   ```
   git clone <repository-url> PDF_Reader
   ```

2. Ensure the `uploads/` directory has write permissions:
   ```
   chmod 755 uploads/
   ```

3. Access the application through your web browser:
   ```
   http://localhost/PDF_Reader/
   ```

### Enhanced Setup (Optional - for better PDF text extraction)

For improved PDF text extraction, install the Smalot PDF Parser library:

```bash
cd PDF_Reader
composer install
```

Alternatively, install `pdftotext` command-line tool (part of poppler-utils):

**Windows:**
- Download poppler-utils from: https://github.com/oschwartz10612/poppler-windows/releases
- Add to system PATH

**Linux:**
```bash
sudo apt-get install poppler-utils
```

**macOS:**
```bash
brew install poppler
```

## Project Structure

```
PDF_Reader/
├── classes/
│   ├── PDFParser.php          # PDF text extraction class
│   ├── SRSParser.php           # SRS document parsing with regex
│   └── SVOAnalyzer.php         # AI-powered SVO analysis (NEW)
├── uploads/                     # Directory for uploaded PDFs
├── index.php                    # Main upload page
├── viewer.php                   # PDF viewer page
├── sections.php                 # SRS sections viewer with SVO analysis
├── serve_pdf.php               # PDF serving endpoint
├── analyze_requirement.php     # AJAX endpoint for SVO analysis (NEW)
├── config.php                  # API configuration (NEW)
├── composer.json               # Composer dependencies
├── README.md                   # This file
├── SVO_SETUP.md               # SVO Analysis setup guide (NEW)
└── SVO_SETUP_SINHALA.md       # SVO setup guide in Sinhala (NEW)
```

## Usage

### Upload PDF

1. Navigate to `index.php`
2. Click or drag-and-drop a PDF file (max 10MB)
3. Click "Upload and Read PDF"

### View PDF

- After upload, you'll be redirected to the PDF viewer
- Use navigation controls to browse pages
- Zoom in/out as needed

### View SRS Sections

1. Click "View SRS Sections" button
2. Navigate between sections using the sidebar:
   - **Header Info** - Project name, module, client, technology stack
   - **Introduction** - Purpose and scope
   - **Functional Requirements** - FR-01, FR-02, etc.
   - **Non-Functional Requirements** - NFR-01, NFR-02, etc.

### AI-Powered SVO Analysis ✨ NEW!

Analyze functional requirements using Google Gemini AI (100% FREE) to extract Subject-Verb-Object structure:

1. **Setup:** Add your FREE Gemini API key in `config.php` (see [SVO_SETUP.md](SVO_SETUP.md))
2. Navigate to **Functional Requirements** section
3. Click **"🔍 Analyze SVO"** button on any requirement
4. View real-time analysis showing:
   - **Subjects** - Who/what performs the action
   - **Verbs** - Actions being performed
   - **Objects** - What is being acted upon
   - **Primary Components** - Main actor, action, and object
   - **Requirement Type** - Classification (functional/data/interface/business)
   - **Complexity** - Low/Medium/High
   - **Dependencies** - Related components and dependencies
   - **Summary** - AI-generated plain language summary

**Benefits:**
- ✅ Structured requirement analysis
- ✅ Better understanding of requirements
- ✅ Easy identification of actors and actions
- ✅ Dependency tracking
- ✅ Real-time results (1-3 seconds)
- ✅ Beautiful, intuitive UI
- ✅ **100% FREE** - No charges, no credit card needed!

**Setup Required:** Get a free Gemini API key at [Google AI Studio](https://makersuite.google.com/app/apikey)

For detailed setup instructions:
- English: [SVO_SETUP.md](SVO_SETUP.md)
- Sinhala: [SVO_SETUP_SINHALA.md](SVO_SETUP_SINHALA.md)

## SRS Document Template

The parser recognizes this SRS structure:

```
Software Requirements Specification (SRS)
Project Name: [Project Name]
Module: [Module Name]
Client: [Client Name]
Technology Stack: [Tech Stack]

1. Introduction
1.1 Purpose
[Content...]

2. Functional Requirements
2.1 [Module Name] (FR-01)
FR-01.01: [Requirement]
FR-01.02: [Requirement]

3. Non-Functional Requirements
NFR-01 (Security): [Description]
NFR-02 (Performance): [Description]
```

## Regex Patterns Used

The SRSParser class uses the following regex patterns:

- **Header extraction:**
  - `Project Name:\s*(.+?)(?:\n|Module:|$)`
  - `Module:\s*(.+?)(?:\n|Client:|$)`
  - `Client:\s*(.+?)(?:\n|Technology Stack:|$)`

- **Section markers:**
  - `1\.\s*Introduction`
  - `2\.\s*Functional Requirements`
  - `3\.\s*Non-Functional Requirements`

- **Subsections:**
  - `2\.\d+\s+(.*?)\((FR-\d+)\)` - Functional requirements
  - `(NFR-\d+)\s*\(([^)]+)\):\s*(.+?)` - Non-functional requirements

- **Filename sanitization:**
  - `[^a-zA-Z0-9._-]` - Removes invalid characters

## Security Features

- File type validation (PDF only)
- File size limit (10MB)
- Filename sanitization using regex
- Session-based file management
- XSS protection with `htmlspecialchars()`

## Technical Details

### PDFParser Class

Handles PDF text extraction using multiple methods:
1. **pdftotext** - Command-line tool (most accurate)
2. **Smalot\PdfParser** - PHP library (good accuracy)
3. **Basic extraction** - Fallback method

### SRSParser Class

Uses regex patterns to:
- Extract document header information
- Identify section boundaries
- Parse functional requirements (FR-01, FR-02...)
- Parse non-functional requirements (NFR-01, NFR-02...)
- Extract subsection content line by line

### SVOAnalyzer Class ✨ NEW!
Google Gemini API:
- **SVO Extraction** - Identifies subjects, verbs, and objects
- **Requirement Classification** - Categorizes by type and complexity
- **Dependency Detection** - Finds related components
- **Natural Language Processing** - Generates human-readable summaries
- **Batch Analysis** - Analyze multiple requirements efficiently
- **Error Handling** - Graceful fallbacks and informative error messages
- **100% FREE** - No API costs, generous limits (60 req/min, 1,500 req/day)

**Technologies:**
- Google Gemini Pro AI
- OpenAI GPT-3.5-turbo / GPT-4
- REST API integration with cURL
- JSON response parsing
- Asynchronous AJAX requests
- Real-time UI updates

## Browser Support

- Chrome/Edge (recommended)
- Firefox
- Safari
- Opera

## Troubleshooting

### PDF text extraction not working

1. Install composer dependencies: `composer install`
2. Or install pdftotext: See Enhanced Setup section
3. Check PDF is not password-protected or image-based

### Sections not parsing correctly

1. Ensure your SRS document follows the template structure
2. Check section numbering (1. Introduction, 2. Functional Requirements, etc.)
3. Verify FR-XX and NFR-XX codes are properly formatted

### Upload errors

1. Check `uploads/` directory permissions (755 or 777)
2. Verify PHP `upload_max_filesize` and `post_max_size` in php.ini
3. Ensure file size is under 10MB

## Contributing

Feel free to submit issues or pull requests for improvements.

## License

This project is open-source and available for educational purposes.

## Author

Developed by a PHP developer with 10 years of experience 🚀

---

**Note:** This tool is specifically designed for SRS documents following the provided template structure. For other document types, the parser may need customization.
