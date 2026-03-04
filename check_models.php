<?php
// config.php eka link karanna
require_once 'config.php';

echo "<h2>Checking Available Gemini Models...</h2>";

// List models API endpoint
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Local WAMP/XAMPP vala SSL issues enava nam eka bypass karanna
if (defined('VERIFY_SSL') && VERIFY_SSL === false) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
}

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: " . $error;
} else {
    $data = json_decode($response, true);
    
    if (isset($data['models'])) {
        echo "<ul>";
        foreach ($data['models'] as $model) {
            // GenerateContent support karana models vitharak pennanna
            if (in_array('generateContent', $model['supportedGenerationMethods'])) {
                $modelName = str_replace('models/', '', $model['name']);
                echo "<li><b>" . $modelName . "</b></li>";
            }
        }
        echo "</ul>";
    } else {
        echo "Error fetching models: <br><pre>" . print_r($data, true) . "</pre>";
    }
}
?>