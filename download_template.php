<?php
require_once __DIR__ . '/template_catalog.php';

$templates = getSupportedTemplates();

$templateType = $_GET['type'] ?? '';

if (!isset($templates[$templateType])) {
    http_response_code(404);
    exit('Template not found.');
}

$template = $templates[$templateType];

if (!is_file($template['path'])) {
    http_response_code(404);
    exit('Template file is missing.');
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $template['content_type']);
header('Content-Disposition: attachment; filename="' . $template['name'] . '"');
header('Content-Length: ' . filesize($template['path']));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: public');
header('X-Content-Type-Options: nosniff');

readfile($template['path']);
exit;
?>