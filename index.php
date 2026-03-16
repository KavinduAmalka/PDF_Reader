<?php
session_start();

require_once __DIR__ . '/template_catalog.php';

$uploadMessage = '';
$uploadStatus = '';
$uploadedFile = '';
$supportedTemplates = getSupportedTemplates();
$availableTemplates = array_filter($supportedTemplates, function ($template) {
    return is_file($template['path']);
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
    $uploadDir = 'uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['pdfFile'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file
    if ($fileError !== 0) {
        $uploadMessage = 'Error uploading file. Please try again.';
        $uploadStatus = 'error';
    } elseif ($fileExt !== 'pdf') {
        $uploadMessage = 'Only PDF files are allowed.';
        $uploadStatus = 'error';
    } elseif ($fileType !== 'application/pdf') {
        $uploadMessage = 'Invalid file type. Only PDF files are allowed.';
        $uploadStatus = 'error';
    } elseif ($fileSize > 10 * 1024 * 1024) { // 10MB limit
        $uploadMessage = 'File size must be less than 10MB.';
        $uploadStatus = 'error';
    } else {
        // Clean up old PDFs - keep only the 2 most recent
        $pdfFiles = glob($uploadDir . '*.pdf');
        if (count($pdfFiles) >= 2) {
            // Sort by modification time (oldest first)
            usort($pdfFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Delete all but the most recent one (keep 1, so after upload we'll have 2)
            $filesToDelete = array_slice($pdfFiles, 0, count($pdfFiles) - 1);
            foreach ($filesToDelete as $fileToDelete) {
                if (file_exists($fileToDelete)) {
                    unlink($fileToDelete);
                }
            }
        }
        
        // Generate unique filename to prevent overwriting
        $newFileName = uniqid('pdf_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $destination = $uploadDir . $newFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpName, $destination)) {
            // Clear old parsed data from session
            unset($_SESSION['srs_sections']);
            unset($_SESSION['pdf_text']);
            unset($_SESSION['parse_error']);
            
            // Store new file info in session
            $_SESSION['uploaded_pdf'] = $destination;
            $_SESSION['original_filename'] = $fileName;
            
            // Redirect to viewer
            header('Location: viewer.php');
            exit();
        } else {
            $uploadMessage = 'Failed to upload file. Please try again.';
            $uploadStatus = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Reader</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #764ba2;
            background: #f8f9ff;
        }
        
        .upload-area i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .upload-text {
            color: #666;
            margin-bottom: 10px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            width: 100%;
            transition: transform 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 5px;
            display: none;
        }
        
        .file-info.active {
            display: block;
        }
        
        .file-name {
            font-weight: bold;
            color: #333;
        }
        
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.active {
            display: block;
        }
        
        .view-current {
            margin-top: 20px;
            text-align: center;
        }
        
        .view-current a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .view-current a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .template-panel {
            margin-top: 24px;
            padding: 20px;
            background: #f7f8ff;
            border: 1px solid #d9def7;
            border-radius: 8px;
        }

        .template-panel h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
        }

        .template-panel p {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .template-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .template-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 16px;
            border-radius: 6px;
            border: 1px solid #667eea;
            color: #667eea;
            background: #fff;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.2s ease;
            flex: 1 1 200px;
        }

        .template-btn:hover {
            background: #667eea;
            color: #fff;
        }

        .template-hint {
            margin-top: 12px;
            color: #666;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 28px 20px;
            }

            .upload-area {
                padding: 28px 20px;
            }

            .template-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 PDF Reader</h1>
        
        <?php if (isset($_SESSION['uploaded_pdf']) && file_exists($_SESSION['uploaded_pdf'])): ?>
        <div class="view-current">
            <p>📑 Currently viewing: <strong><?php echo htmlspecialchars($_SESSION['original_filename'] ?? 'document.pdf'); ?></strong></p>
            <p>
                <a href="viewer.php">→ View PDF</a> | 
                <a href="sections.php">→ View SRS Sections</a>
            </p>
        </div>
        <?php endif; ?>
        
        <form id="uploadForm" method="POST" enctype="multipart/form-data" action="">
            <div class="upload-area" id="uploadArea">
                <div style="font-size: 48px; margin-bottom: 20px;">📁</div>
                <p class="upload-text"><strong>Click to upload</strong> or drag and drop</p>
                <p style="color: #999; font-size: 14px;">PDF files only (Max 10MB)</p>
                <input type="file" id="pdfFile" name="pdfFile" accept=".pdf">
            </div>
            
            <div class="file-info" id="fileInfo">
                <p>Selected file:</p>
                <p class="file-name" id="fileName"></p>
            </div>
            
            <button type="submit" class="btn" id="uploadBtn" disabled>Upload and Read PDF</button>
        </form>

        <?php if (!empty($availableTemplates)): ?>
        <div class="template-panel">
            <h2>Supported SRS Templates</h2>
            <p>This system can analyze only the two supported SRS template formats. You can download the matching PDF template from here and upload the completed PDF for analysis.</p>
            <div class="template-actions">
                <?php foreach ($availableTemplates as $templateKey => $template): ?>
                <a class="template-btn" href="download_template.php?type=<?php echo urlencode($templateKey); ?>"><?php echo htmlspecialchars($template['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="message" id="message"></div>
        
        <?php if (!empty($uploadMessage)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showMessage('<?php echo addslashes($uploadMessage); ?>', '<?php echo $uploadStatus; ?>');
            });
        </script>
        <?php endif; ?>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const pdfFile = document.getElementById('pdfFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const message = document.getElementById('message');
        
        // Click to upload
        uploadArea.addEventListener('click', () => {
            pdfFile.click();
        });
        
        // File selection
        pdfFile.addEventListener('change', (e) => {
            handleFile(e.target.files[0]);
        });
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#764ba2';
            uploadArea.style.background = '#f8f9ff';
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#667eea';
            uploadArea.style.background = '';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#667eea';
            uploadArea.style.background = '';
            
            const file = e.dataTransfer.files[0];
            if (file && file.type === 'application/pdf') {
                pdfFile.files = e.dataTransfer.files;
                handleFile(file);
            } else {
                showMessage('Please upload a PDF file only', 'error');
            }
        });
        
        function handleFile(file) {
            if (!file) return;
            
            if (file.type !== 'application/pdf') {
                showMessage('Please upload a PDF file only', 'error');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) { // 10MB
                showMessage('File size must be less than 10MB', 'error');
                return;
            }
            
            fileName.textContent = file.name;
            fileInfo.classList.add('active');
            uploadBtn.disabled = false;
            message.classList.remove('active');
        }
        
        function showMessage(text, type) {
            message.textContent = text;
            message.className = 'message active ' + type;
        }
        
        // Form submission - let it submit naturally to PHP
        uploadForm.addEventListener('submit', (e) => {
            if (!pdfFile.files[0]) {
                e.preventDefault();
                showMessage('Please select a PDF file', 'error');
            }
        });
    </script>
</body>
</html>
