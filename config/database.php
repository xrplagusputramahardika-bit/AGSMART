<?php
/**
 * Database Connection & Auto-Migrator
 * AGSmart POS System (Retaillet Theme)
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'db_tokokita');

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Auto-heal / auto-migrate any missing tables or columns seamlessly
        autoMigrateDatabase($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        // If error is database unknown (code 1049), try to auto-create and run migration
        if ($e->getCode() == 1049) {
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                $sqlFile = __DIR__ . '/../database/db_tokokita.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    $pdo->exec($sql);
                }
                return $pdo;
            } catch (Exception $ex) {
                return null;
            }
        }
        return null;
    }
}

// Function to auto-migrate missing tables & columns
function autoMigrateDatabase($pdo) {
    static $migrated = false;
    if ($migrated) return;
    $migrated = true;

    try {
        // 1. Check if tbl_produk exists; if not, import db_tokokita.sql
        $stmt = $pdo->query("SHOW TABLES LIKE 'tbl_produk'");
        if (!$stmt->fetch()) {
            $sqlFile = __DIR__ . '/../database/db_tokokita.sql';
            if (file_exists($sqlFile)) {
                try {
                    $pdo->exec(file_get_contents($sqlFile));
                } catch (Exception $e) {}
            }
        }

        // 2. Ensure all columns in tbl_transaksi exist
        $stmtTrx = $pdo->query("SHOW COLUMNS FROM tbl_transaksi");
        $trxCols = $stmtTrx->fetchAll(PDO::FETCH_COLUMN);

        $neededTrxCols = [
            'no_faktur'         => "VARCHAR(50) DEFAULT NULL",
            'waktu'             => "TIME DEFAULT '10:00:00'",
            'subtotal'          => "DECIMAL(12,2) DEFAULT 0.00",
            'diskon'            => "DECIMAL(12,2) DEFAULT 0.00",
            'pajak'             => "DECIMAL(12,2) DEFAULT 0.00",
            'bayar'             => "DECIMAL(12,2) DEFAULT 0.00",
            'kembalian'         => "DECIMAL(12,2) DEFAULT 0.00",
            'metode_pembayaran' => "VARCHAR(30) DEFAULT 'Tunai'",
            'kasir'             => "VARCHAR(50) DEFAULT 'Kasir 1'",
            'catatan'           => "VARCHAR(255) DEFAULT NULL",
            'poin_didapat'      => "INT(11) DEFAULT 0"
        ];

        foreach ($neededTrxCols as $col => $definition) {
            if (!in_array($col, $trxCols)) {
                try {
                    $pdo->exec("ALTER TABLE `tbl_transaksi` ADD COLUMN `$col` $definition");
                } catch (Exception $e) {}
            }
        }

        // 3. Ensure all columns in tbl_pelanggan exist
        $stmtPlg = $pdo->query("SHOW COLUMNS FROM tbl_pelanggan");
        $plgCols = $stmtPlg->fetchAll(PDO::FETCH_COLUMN);

        $neededPlgCols = [
            'telepon'       => "VARCHAR(25) DEFAULT NULL",
            'alamat'        => "TEXT DEFAULT NULL",
            'no_kartu'      => "VARCHAR(50) DEFAULT NULL UNIQUE",
            'tipe_member'   => "ENUM('Regular','Silver','Gold','Platinum') DEFAULT 'Silver'",
            'diskon_member' => "DECIMAL(5,2) DEFAULT 0.00",
            'poin'          => "INT(11) DEFAULT 0",
            'saldo'         => "DECIMAL(12,2) DEFAULT 0.00",
            'status_kartu'  => "ENUM('aktif','nonaktif','blokir') DEFAULT 'aktif'"
        ];

        foreach ($neededPlgCols as $col => $definition) {
            if (!in_array($col, $plgCols)) {
                try {
                    $pdo->exec("ALTER TABLE `tbl_pelanggan` ADD COLUMN `$col` $definition");
                } catch (Exception $e) {}
            }
        }

        // 4. Fill dummy invoice number for transactions missing no_faktur
        try {
            $pdo->exec("
                UPDATE tbl_transaksi 
                SET no_faktur = CONCAT('TRX-', DATE_FORMAT(COALESCE(tanggal, CURDATE()), '%Y%m%d'), '-', LPAD(id_transaksi, 4, '0')),
                    metode_pembayaran = IF(id_transaksi % 3 = 0, 'QRIS', IF(id_transaksi % 4 = 0, 'Transfer Bank', 'Tunai')),
                    subtotal = total_belanja,
                    bayar = total_belanja,
                    kembalian = 0,
                    waktu = '10:00:00',
                    kasir = 'Kasir 1'
                WHERE no_faktur IS NULL OR no_faktur = '' OR metode_pembayaran IS NULL OR metode_pembayaran = ''
            ");
        } catch (Exception $e) {}

        // 5. Ensure store name in settings is AGSMART
        try {
            $pdo->exec("UPDATE tbl_pengaturan SET nama_toko = 'AGSMART' WHERE id = 1 AND (nama_toko LIKE '%Freshmart%' OR nama_toko = '' OR nama_toko IS NULL)");
        } catch (Exception $e) {}

    } catch (Exception $e) {}
}

// Function to check database connection status
function checkDatabaseStatus() {
    try {
        $db = getDB();
        if (!$db) {
            return ['status' => false, 'message' => 'Tidak dapat terhubung ke MySQL. Pastikan XAMPP MySQL sudah aktif.'];
        }
        return ['status' => true, 'message' => 'Database siap digunakan.'];
    } catch (Exception $e) {
        return ['status' => false, 'message' => $e->getMessage()];
    }
}
