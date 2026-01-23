<?php
/**
 * Test login script - удалить после проверки!
 */

require_once __DIR__ . '/../config/config.php';

echo "Testing login...\n\n";
echo "ADMIN_USER: " . ADMIN_USER . "\n";
echo "ADMIN_PASS_HASH length: " . strlen(ADMIN_PASS_HASH) . "\n";
echo "ADMIN_PASS_HASH: " . ADMIN_PASS_HASH . "\n\n";

$test_password = 'password';
$result = password_verify($test_password, ADMIN_PASS_HASH);

echo "Testing password 'password': " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

if (!$result) {
    echo "\nGenerating new hash for 'password':\n";
    $new_hash = password_hash('password', PASSWORD_DEFAULT);
    echo "New hash: " . $new_hash . "\n";
    echo "\nUpdate config.php with this hash!\n";
}
