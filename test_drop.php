<?php
$dsn = "mysql:host=127.0.0.1;port=3306;charset=utf8mb4";
$pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $pdo->exec("DROP DATABASE IF EXISTS `db_tokokita`");
    echo "DROP DATABASE successful!\n";
} catch (Exception $e) {
    echo "DROP DATABASE failed: " . $e->getMessage() . "\n";
}
