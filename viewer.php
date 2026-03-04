<?php
session_start();

// Check if a PDF has been uploaded
if (!isset($_SESSION['uploaded_pdf']) || !file_exists($_SESSION['uploaded_pdf'])) {
    header('Location: index.php');
    exit('No PDF file found. Please upload a PDF first.');
}

$pdfPath = $_SESSION['uploaded_pdf'];
$originalFilename = $_SESSION['original_filename'] ?? 'document.pdf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Viewer - <?php echo htmlspecialchars($originalFilename); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filename {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .btn-sections {
            background: rgba(255, 193, 7, 0.9);
            color: #333;
            padding: 10px 20px;
            border: 1px solid rgba(255, 193, 7, 1);
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: bold;
            white-space: nowrap;
        }
        
        .btn-sections:hover {
            background: rgba(255, 193, 7, 1);
        }
        
        .controls {
            background: white;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .controls button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .controls button:hover:not(:disabled) {
            background: #764ba2;
        }
        
        .controls button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .page-info {
            font-size: 14px;
            color: #666;
        }
        
        .zoom-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .viewer-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        
        #pdf-canvas-container {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .pdf-page {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }
        
        #loading {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }
        
        .text-layer {
            background: white;
            padding: 30px;
            font-family: 'Georgia', serif;
            line-height: 1.8;
            color: #333;
        }
        
        .text-layer h3 {
            margin-bottom: 20px;
            color: #667eea;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .text-line {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .text-line:hover {
            background: #f8f9ff;
        }
        
        .line-number {
            color: #999;
            font-weight: bold;
            min-width: 50px;
            user-select: none;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .text-content {
            color: #333;
            flex: 1;
            font-size: 16px;
        }
        
        .page-divider {
            margin: 30px 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .toggle-view {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>📄 PDF Viewer</h1>
                <div class="filename"><?php echo htmlspecialchars($originalFilename); ?></div>
            </div>
            <div class="header-buttons">
                <a href="sections.php" class="btn-sections">📑 View SRS Sections</a>
                <a href="index.php" class="btn-back">← Upload New PDF</a>
            </div>
        </div>
    </div>
    
    <div class="controls">
        <button id="prev-page">← Previous Page</button>
        <span class="page-info">
            Page <span id="page-num">1</span> of <span id="page-count">-</span>
        </span>
        <button id="next-page">Next Page →</button>
        
        <div class="zoom-controls">
            <button id="zoom-out">Zoom Out</button>
            <span id="zoom-level">100%</span>
            <button id="zoom-in">Zoom In</button>
        </div>
        
        <button id="show-text" class="toggle-view">Show PDF View</button>
    </div>
    
    <div class="viewer-container">
        <div id="loading">Loading PDF...</div>
        <div id="pdf-canvas-container"></div>
        <div id="text-layer" class="text-layer">
            <div id="text-content"></div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // PDF.js configuration
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        const pdfPath = 'serve_pdf.php';
        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.5;
        
        const canvasContainer = document.getElementById('pdf-canvas-container');
        const pageNumElement = document.getElementById('page-num');
        const pageCountElement = document.getElementById('page-count');
        const prevButton = document.getElementById('prev-page');
        const nextButton = document.getElementById('next-page');
        const zoomInButton = document.getElementById('zoom-in');
        const zoomOutButton = document.getElementById('zoom-out');
        const zoomLevelElement = document.getElementById('zoom-level');
        const showTextButton = document.getElementById('show-text');
        const textLayer = document.getElementById('text-layer');
        const textContent = document.getElementById('text-content');
        const loading = document.getElementById('loading');
        
        let isTextView = true; // Start with text view
        
        // Load PDF
        pdfjsLib.getDocument(pdfPath).promise.then(function(pdf) {
            pdfDoc = pdf;
            pageCountElement.textContent = pdf.numPages;
            loading.style.display = 'none';
            
            // Automatically extract and display text from current page
            extractAndDisplayText(pageNum);
        }).catch(function(error) {
            loading.innerHTML = '<div style="color: #d9534f;"><strong>Error loading PDF:</strong><br>' + error.message + '<br><br>Please try uploading the PDF again.</div>';
            console.error('Error loading PDF:', error);
        });
        
        // Render page
        function renderPage(num) {
            pageRendering = true;
            
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                
                // Create canvas for this page
                const canvas = document.createElement('canvas');
                canvas.className = 'pdf-page';
                const ctx = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                // Clear container and add new canvas
                canvasContainer.innerHTML = '';
                canvasContainer.appendChild(canvas);
                
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                
                const renderTask = page.render(renderContext);
                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
            
            pageNumElement.textContent = num;
        }
        
        // Queue page rendering
        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }
        
        // Previous page
        prevButton.addEventListener('click', function() {
            if (pageNum <= 1) return;
            pageNum--;
            if (isTextView) {
                extractAndDisplayText(pageNum);
            } else {
                queueRenderPage(pageNum);
            }
            updateButtons();
        });
        
        // Next page
        nextButton.addEventListener('click', function() {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            if (isTextView) {
                extractAndDisplayText(pageNum);
            } else {
                queueRenderPage(pageNum);
            }
            updateButtons();
        });
        
        // Zoom in
        zoomInButton.addEventListener('click', function() {
            if (scale >= 3) return;
            scale += 0.25;
            zoomLevelElement.textContent = Math.round(scale * 100) + '%';
            queueRenderPage(pageNum);
        });
        
        // Zoom out
        zoomOutButton.addEventListener('click', function() {
            if (scale <= 0.5) return;
            scale -= 0.25;
            zoomLevelElement.textContent = Math.round(scale * 100) + '%';
            queueRenderPage(pageNum);
        });
        
        // Update button states
        function updateButtons() {
            prevButton.disabled = pageNum <= 1;
            nextButton.disabled = pageNum >= pdfDoc.numPages;
        }
        
        // Extract and display text from a page
        async function extractAndDisplayText(num) {
            try {
                textContent.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Extracting text from page ' + num + '...</p>';
                
                const page = await pdfDoc.getPage(num);
                const textContentData = await page.getTextContent();
                const items = textContentData.items;
                
                let textHtml = '<h3>📄 Page ' + num + ' - Text Content (Line by Line)</h3>';
                
                if (items.length === 0) {
                    textHtml += '<p style="color: #999; text-align: center; padding: 20px;">No text content found on this page.</p>';
                } else {
                    // Group text items by their Y position (actual lines in PDF)
                    const lines = [];
                    let currentLine = null;
                    let lastY = null;
                    
                    items.forEach((item) => {
                        const y = item.transform[5]; // Y position
                        const text = item.str;
                        
                        // Check if this is a new line (Y position changed significantly)
                        if (lastY === null || Math.abs(y - lastY) > 1) {
                            if (currentLine !== null && currentLine.trim()) {
                                lines.push(currentLine.trim());
                            }
                            currentLine = text;
                            lastY = y;
                        } else {
                            // Same line, append text
                            currentLine += text;
                        }
                    });
                    
                    // Add the last line
                    if (currentLine !== null && currentLine.trim()) {
                        lines.push(currentLine.trim());
                    }
                    
                    // Display lines
                    let lineNumber = 1;
                    lines.forEach((line) => {
                        textHtml += `<div class="text-line">
                            <span class="line-number">${lineNumber}.</span>
                            <span class="text-content">${escapeHtml(line)}</span>
                        </div>`;
                        lineNumber++;
                    });
                }
                
                textContent.innerHTML = textHtml;
                pageNumElement.textContent = num;
                
                // Scroll to top when changing pages
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                textContent.innerHTML = '<p style="color: #d9534f; text-align: center; padding: 20px;">Error extracting text: ' + error.message + '</p>';
            }
        }
        
        // Toggle between text and PDF view
        showTextButton.addEventListener('click', function() {
            if (isTextView) {
                // Switch to PDF canvas view
                isTextView = false;
                textLayer.style.display = 'none';
                canvasContainer.style.display = 'flex';
                showTextButton.textContent = 'Show Text View';
                showTextButton.classList.remove('toggle-view');
                renderPage(pageNum);
                
                // Hide zoom controls in text view
                zoomInButton.style.display = 'inline-block';
                zoomOutButton.style.display = 'inline-block';
                zoomLevelElement.style.display = 'inline-block';
            } else {
                // Switch to text view
                isTextView = true;
                canvasContainer.style.display = 'none';
                textLayer.style.display = 'block';
                showTextButton.textContent = 'Show PDF View';
                showTextButton.classList.add('toggle-view');
                extractAndDisplayText(pageNum);
                
                // Show zoom controls in PDF view
                zoomInButton.style.display = 'none';
                zoomOutButton.style.display = 'none';
                zoomLevelElement.style.display = 'none';
            }
        });
        
        // Hide zoom controls initially (since we start in text view)
        zoomInButton.style.display = 'none';
        zoomOutButton.style.display = 'none';
        zoomLevelElement.style.display = 'none';
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && pageNum > 1) {
                pageNum--;
                if (isTextView) {
                    extractAndDisplayText(pageNum);
                } else {
                    queueRenderPage(pageNum);
                }
                updateButtons();
            } else if (e.key === 'ArrowRight' && pageNum < pdfDoc.numPages) {
                pageNum++;
                if (isTextView) {
                    extractAndDisplayText(pageNum);
                } else {
                    queueRenderPage(pageNum);
                }
                updateButtons();
            }
        });
    </script>
</body>
</html>
