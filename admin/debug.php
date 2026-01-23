<?php
/**
 * Debug script - удалить после диагностики!
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

echo "<h1>Debug Information</h1>";

echo "<h2>PHP Version</h2>";
echo PHP_VERSION . "<br>";

echo "<h2>Config Check</h2>";
try {
    require_once __DIR__ . '/../config/config.php';
    echo "Config loaded: OK<br>";
    echo "ADMIN_USER: " . ADMIN_USER . "<br>";
    echo "ADMIN_PASS_HASH length: " . strlen(ADMIN_PASS_HASH) . "<br>";
} catch (Exception $e) {
    echo "Config error: " . $e->getMessage() . "<br>";
}

echo "<h2>Session Check</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "<br>";

echo "<h2>Password Verify Test</h2>";
$test_pass = 'password';
$result = password_verify($test_pass, ADMIN_PASS_HASH);
echo "Password 'password' verify: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";

echo "<h2>DB Check</h2>";
try {
    require_once __DIR__ . '/../core/DB.php';
    $db = DB::getInstance();
    echo "DB connection: OK<br>";
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "<br>";
}

echo "<h2>Common Functions</h2>";
try {
    require_once __DIR__ . '/common.php';
    echo "Common.php loaded: OK<br>";
} catch (Exception $e) {
    echo "Common.php error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Login Function Test</h2>";
try {
    $test_result = login('admin', 'password');
    echo "Login test result: " . ($test_result ? 'SUCCESS' : 'FAILED') . "<br>";
} catch (Exception $e) {
    echo "Login function error: " . $e->getMessage() . "<br>";
}

echo "<h2>PHP Errors</h2>";
$errors = error_get_last();
if ($errors) {
    echo "<pre>";
    print_r($errors);
    echo "</pre>";
} else {
    echo "No errors<br>";
}
