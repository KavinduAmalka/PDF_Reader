<?php
session_start();

// Check if a PDF has been uploaded
if (!isset($_SESSION['uploaded_pdf']) || !file_exists($_SESSION['uploaded_pdf'])) {
    header('HTTP/1.0 404 Not Found');
    exit('PDF file not found');
}

$pdfPath = $_SESSION['uploaded_pdf'];

// Check if file exists
if (!file_exists($pdfPath)) {
    header('HTTP/1.0 404 Not Found');
    exit('PDF file not found');
}

// Get file info
$filesize = filesize($pdfPath);
$filename = basename($pdfPath);

// Set headers for PDF
header('Content-Type: application/pdf');
header('Content-Length: ' . $filesize);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: 0');

// Output the file
readfile($pdfPath);
exit();
?>
