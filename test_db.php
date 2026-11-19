<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
if (!$db) {
    echo "getDB() returned null!\n";
    exit(1);
}
echo "getDB() SUCCESS!\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

foreach ($tables as $t) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "Table $t: $count rows\n";
    } catch (Exception $e) {
        echo "Table $t: ERROR " . $e->getMessage() . "\n";
    }
}
