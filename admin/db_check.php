<?php
/**
 * Database Connection Check Script
 */

require_once __DIR__ . '/../config/config.php';

echo "<h1>Database Connection Check</h1>";

echo "<h2>Configuration</h2>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: " . (strlen(DB_PASS) > 0 ? '***' : 'empty') . "<br>";

echo "<h2>MySQL Socket Check</h2>";
$socketPaths = [
    '/var/run/mysqld/mysqld.sock',
    '/var/lib/mysql/mysql.sock',
    '/tmp/mysql.sock',
    '/run/mysqld/mysqld.sock'
];

$foundSocket = null;
foreach ($socketPaths as $path) {
    if (file_exists($path)) {
        echo "✓ Found socket: {$path}<br>";
        $foundSocket = $path;
    } else {
        echo "✗ Not found: {$path}<br>";
    }
}

if (!$foundSocket) {
    echo "<strong>Warning: No MySQL socket found. Will try TCP connection.</strong><br>";
}

echo "<h2>Connection Test</h2>";

try {
    require_once __DIR__ . '/../core/DB.php';
    
    $host = DB_HOST;
    $socket = $foundSocket;
    
    if ($socket && ($host === 'localhost' || $host === '127.0.0.1')) {
        $dsn = sprintf(
            "mysql:unix_socket=%s;dbname=%s;charset=%s",
            $socket,
            DB_NAME,
            DB_CHARSET
        );
        echo "Using socket: {$socket}<br>";
    } else {
        $host = ($host === 'localhost') ? '127.0.0.1' : $host;
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $host,
            DB_NAME,
            DB_CHARSET
        );
        echo "Using TCP: {$host}<br>";
    }
    
    echo "DSN: {$dsn}<br><br>";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "<span style='color: green;'>✓ Connection successful!</span><br>";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $version['version'] . "<br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "<br>";
    if (count($tables) > 0) {
        echo "Tables: " . implode(', ', $tables) . "<br>";
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>✗ Connection failed!</span><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Code: " . $e->getCode() . "<br>";
    
    echo "<h3>Possible Solutions:</h3>";
    echo "<ul>";
    echo "<li>Check if MySQL is running: <code>sudo systemctl status mysql</code></li>";
    echo "<li>Start MySQL if needed: <code>sudo systemctl start mysql</code></li>";
    echo "<li>Check MySQL socket: <code>mysql_config --socket</code></li>";
    echo "<li>Try using 127.0.0.1 instead of localhost in config.php</li>";
    echo "<li>Check if database exists: <code>mysql -u root -p -e 'SHOW DATABASES;'</code></li>";
    echo "<li>Create database: <code>mysql -u root -p &lt; sql/schema.sql</code></li>";
    echo "</ul>";
}
