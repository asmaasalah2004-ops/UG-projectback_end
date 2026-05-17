<?php
// Direct MySQL check - bypass Laravel to verify DB credentials
$host = '127.0.0.1';
$port = '3306';
$dbname = 'advising_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL database: $dbname\n\n";

    // List tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  - $t ($count rows)\n";
    }

    // Check users table
    if (in_array('users', $tables)) {
        echo "\n=== Users Table ===\n";
        $stmt = $pdo->query("SELECT id, name, email, role, LEFT(password,30) as pass_preview FROM users LIMIT 20");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            $isHashed = str_starts_with($u['pass_preview'], '$2y$') || str_starts_with($u['pass_preview'], '$2a$');
            echo "ID:{$u['id']} | {$u['email']} | role:{$u['role']} | bcrypted:" . ($isHashed ? 'YES' : 'NO ('.$u['pass_preview'].')') . "\n";
        }
        echo "Total users: " . count($users) . "\n";
    }

} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
