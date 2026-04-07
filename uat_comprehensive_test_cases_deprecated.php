<?php
session_start();

if (!isset($_SESSION['srs_sections'])) {
    header('Location: index.php');
    exit;
}

// Redirect to new enhanced module
header('Location: uat_test_cases_enhanced.php');
exit;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
</head>
<body>
    <p>This module has been consolidated. Redirecting to enhanced UAT Testing Module...</p>
    <p><a href="uat_test_cases_enhanced.php">Click here if not redirected</a></p>
</body>
</html>
