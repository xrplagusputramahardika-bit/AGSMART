<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
if (!$db) {
    echo "Database is unavailable.\n";
    exit(1);
}
$db->exec("TRUNCATE TABLE `tbl_detail_transaksi`");
executeSqlScript($db, __DIR__ . '/database/db_tokokita.sql');
$db->exec("UPDATE tbl_user SET role='admin' WHERE username='kasir1'");
$db->exec("UPDATE tbl_user SET role='kasir' WHERE username='kasir2'");
foreach ($db->query("SELECT username, role FROM tbl_user ORDER BY id_user") as $user) {
    echo $user['username'] . ': ' . $user['role'] . "\n";
}
echo "Migration SQL completed.\n";
