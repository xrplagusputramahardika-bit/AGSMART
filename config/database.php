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
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Check if database tables are corrupted (e.g. Error 1932: table doesn't exist in engine)
        if (isDatabaseCorrupted($pdo)) {
            $repaired = repairCorruptedDatabase($options);
            if ($repaired) {
                $pdo = $repaired;
            }
        }

        // Auto-heal / auto-migrate any missing tables or columns seamlessly
        autoMigrateDatabase($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        // If error is database unknown (code 1049) or table doesn't exist in engine
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            $sqlFile = __DIR__ . '/../database/db_tokokita.sql';
            if (file_exists($sqlFile)) {
                executeSqlScript($pdo, $sqlFile);
            }
            autoMigrateDatabase($pdo);
            return $pdo;
        } catch (Exception $ex) {
            return null;
        }
    }
}

// Robust SQL file executor that parses individual statements
function executeSqlScript($pdo, $sqlFile) {
    if (!file_exists($sqlFile)) return false;
    $content = file_get_contents($sqlFile);
    // Remove multi-line comments
    $content = preg_replace('!/\*.*?\*/!s', '', $content);
    // Split by lines and remove single-line comments
    $lines = explode("\n", $content);
    $cleanSql = '';
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $cleanSql .= $line . "\n";
    }
    // Split by semicolons
    $statements = explode(';', $cleanSql);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (Throwable $e) {
                // Ignore non-fatal statement level errors, continue executing
            }
        }
    }
    return true;
}

// Check if tables have MySQL 1932 engine corruption
function isDatabaseCorrupted($pdo) {
    $criticalTables = ['tbl_user', 'tbl_transaksi', 'tbl_produk', 'tbl_pelanggan', 'tbl_topup_request', 'tbl_pelanggan_order'];
    foreach ($criticalTables as $table) {
        try {
            $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '1932') !== false || 
                strpos($msg, "doesn't exist in engine") !== false ||
                strpos($msg, '42S02') !== false ||
                strpos($msg, '1146') !== false ||
                strpos($msg, 'Base table or view not found') !== false ||
                strpos($msg, '1813') !== false ||
                strpos($msg, 'Tablespace') !== false) {
                return true;
            }
        }
    }
    return false;
}

// Cleanly repair corrupted / orphaned InnoDB tables
function repairCorruptedDatabase($options) {
    try {
        $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
        
        // Drop corrupted database and recreate fresh
        $rootPdo->exec("DROP DATABASE IF EXISTS `" . DB_NAME . "`");
        $rootPdo->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $newPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        $sqlFile = __DIR__ . '/../database/db_tokokita.sql';
        if (file_exists($sqlFile)) {
            executeSqlScript($newPdo, $sqlFile);
        }
        return $newPdo;
    } catch (Throwable $e) {
        return null;
    }
}

// Function to auto-migrate missing tables & columns
function autoMigrateDatabase($pdo) {
    static $migrated = false;
    if ($migrated) return;
    $migrated = true;

    try {
        // 1. Ensure tbl_user exists and seed default accounts
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `tbl_user` (
              `id_user` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `nama_lengkap` varchar(100) NOT NULL,
              `role` enum('admin','kasir') DEFAULT 'kasir',
              `no_kartu` varchar(50) DEFAULT NULL UNIQUE,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_user`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `tbl_user`")->fetchColumn();
            if ($userCount === 0) {
                $pdo->exec("INSERT INTO `tbl_user` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `no_kartu`) VALUES
                (1, 'admin', '$2y$10$2vZe//ohOCdUwQPfP2bV.ODxiu7xaSKjm2i1RSLMYnGkzM3k/cv52', 'Administrator', 'admin', 'MBR-ADMIN01'),
                (2, 'kasir1', '$2y$10$si4xB3QHKS2/RrEx31Bm/eCkaoaQZAUDuAceqdMo0JS2wPmIx0DxG', 'Siti Rahma (Kasir 1)', 'admin', 'MBR-KASIR01'),
                (3, 'kasir2', '$2y$10$si4xB3QHKS2/RrEx31Bm/eCkaoaQZAUDuAceqdMo0JS2wPmIx0DxG', 'Dewi Lestari (Kasir 2)', 'kasir', 'MBR-KASIR02')");
            }
        } catch (Throwable $e) {}

        // 2. Ensure all other tables exist, if not import db_tokokita.sql
        $essentialTables = ['tbl_produk', 'tbl_kategori', 'tbl_pelanggan', 'tbl_transaksi', 'tbl_pengaturan'];
        $needsFullImport = false;
        foreach ($essentialTables as $table) {
            try {
                $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            } catch (Throwable $e) {
                $needsFullImport = true;
                break;
            }
        }

        if ($needsFullImport) {
            $sqlFile = __DIR__ . '/../database/db_tokokita.sql';
            if (file_exists($sqlFile)) {
                executeSqlScript($pdo, $sqlFile);
            }
        }

        // 3. Cashier tap-card credentials; existing users keep password login.
        try {
            $userCols = $pdo->query("SHOW COLUMNS FROM tbl_user")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('no_kartu', $userCols, true)) {
                $pdo->exec("ALTER TABLE tbl_user ADD COLUMN no_kartu VARCHAR(50) DEFAULT NULL UNIQUE");
            }
            $pdo->exec("UPDATE tbl_user SET role='admin' WHERE username='kasir1'");
            $pdo->exec("UPDATE tbl_user SET role='kasir' WHERE username='kasir2'");
        } catch (Throwable $e) {}

        // 4. Ensure barcode column exists in tbl_produk for Indomaret scanning
        try {
            $prodCols = $pdo->query("SHOW COLUMNS FROM tbl_produk")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('barcode', $prodCols, true)) {
                $pdo->exec("ALTER TABLE tbl_produk ADD COLUMN barcode VARCHAR(50) DEFAULT NULL AFTER kode_produk");
                $pdo->exec("UPDATE tbl_produk SET barcode = CONCAT('899', LPAD(id_produk, 10, '0')) WHERE barcode IS NULL OR barcode = ''");
            }
        } catch (Throwable $e) {}

        // 5. Transfer top-up requests & online orders tables
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_topup_request (
                id_topup INT AUTO_INCREMENT PRIMARY KEY,
                id_pelanggan INT NOT NULL,
                nominal DECIMAL(12,2) NOT NULL,
                metode_bayar VARCHAR(30) NOT NULL DEFAULT 'Transfer Bank',
                bukti_transfer VARCHAR(80) DEFAULT NULL,
                status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
                dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (id_pelanggan), INDEX (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $settingsCols = $pdo->query("SHOW COLUMNS FROM tbl_pengaturan")->fetchAll(PDO::FETCH_COLUMN);
            foreach([
                'bank_transfer' => "VARCHAR(60) DEFAULT 'BCA'",
                'rekening_transfer' => "VARCHAR(40) DEFAULT '1234567890'",
                'pemilik_rekening' => "VARCHAR(100) DEFAULT 'AGSMART STORE'",
                'qris_image_url' => "VARCHAR(500) DEFAULT NULL",
                'ewallet_info' => "VARCHAR(500) DEFAULT NULL"
            ] as $col => $definition) {
                if (!in_array($col, $settingsCols, true)) {
                    $pdo->exec("ALTER TABLE tbl_pengaturan ADD COLUMN `$col` $definition");
                }
            }

            $topupCols = $pdo->query("SHOW COLUMNS FROM tbl_topup_request")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('metode_bayar', $topupCols, true)) {
                $pdo->exec("ALTER TABLE tbl_topup_request ADD COLUMN metode_bayar VARCHAR(30) NOT NULL DEFAULT 'Transfer Bank'");
            }
            if (!in_array('bukti_transfer', $topupCols, true)) {
                $pdo->exec("ALTER TABLE tbl_topup_request ADD COLUMN bukti_transfer VARCHAR(80) DEFAULT NULL");
            }

            $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_pelanggan_order (
                id_order INT AUTO_INCREMENT PRIMARY KEY,
                id_pelanggan INT NOT NULL,
                detail_json LONGTEXT NOT NULL,
                total DECIMAL(12,2) NOT NULL,
                metode_bayar VARCHAR(30) NOT NULL DEFAULT 'Transfer Bank',
                bukti_transfer VARCHAR(80) DEFAULT NULL,
                status ENUM('menunggu_transfer','menunggu_verifikasi','menunggu_pembayaran','diproses','ditolak','selesai') NOT NULL DEFAULT 'menunggu_transfer',
                duitku_order_id VARCHAR(50) DEFAULT NULL,
                duitku_reference VARCHAR(100) DEFAULT NULL,
                duitku_payment_url VARCHAR(500) DEFAULT NULL,
                duitku_payment_code VARCHAR(10) DEFAULT NULL,
                duitku_expires_at DATETIME DEFAULT NULL,
                stok_dipesan TINYINT(1) NOT NULL DEFAULT 0,
                dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (id_pelanggan), INDEX (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $orderCols = $pdo->query("SHOW COLUMNS FROM tbl_pelanggan_order")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('metode_bayar', $orderCols, true)) {
                $pdo->exec("ALTER TABLE tbl_pelanggan_order ADD COLUMN metode_bayar VARCHAR(30) NOT NULL DEFAULT 'Transfer Bank'");
            }
            if (!in_array('bukti_transfer', $orderCols, true)) {
                $pdo->exec("ALTER TABLE tbl_pelanggan_order ADD COLUMN bukti_transfer VARCHAR(80) DEFAULT NULL");
            }
            foreach ([
                'duitku_order_id' => 'VARCHAR(50) DEFAULT NULL',
                'duitku_reference' => 'VARCHAR(100) DEFAULT NULL',
                'duitku_payment_url' => 'VARCHAR(500) DEFAULT NULL',
                'duitku_payment_code' => 'VARCHAR(10) DEFAULT NULL',
                'duitku_expires_at' => 'DATETIME DEFAULT NULL',
                'stok_dipesan' => 'TINYINT(1) NOT NULL DEFAULT 0'
            ] as $col => $definition) {
                if (!in_array($col, $orderCols, true)) {
                    $pdo->exec("ALTER TABLE tbl_pelanggan_order ADD COLUMN `$col` $definition");
                }
            }
            $statusType = $pdo->query("SHOW COLUMNS FROM tbl_pelanggan_order LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
            if ($statusType && strpos($statusType['Type'], "'menunggu_pembayaran'") === false) {
                $pdo->exec("ALTER TABLE tbl_pelanggan_order MODIFY status ENUM('menunggu_transfer','menunggu_verifikasi','menunggu_pembayaran','diproses','ditolak','selesai') NOT NULL DEFAULT 'menunggu_transfer'");
            }
        } catch (Throwable $e) {
            error_log('AGSmart online order migration failed: ' . $e->getMessage());
        }

        // 6. Ensure all columns in tbl_transaksi exist
        try {
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
                    } catch (Throwable $e) {}
                }
            }
        } catch (Throwable $e) {}

        // 7. Ensure all columns in tbl_pelanggan exist
        try {
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
                    } catch (Throwable $e) {}
                }
            }
        } catch (Throwable $e) {}

        // 8. Fill dummy invoice number for transactions missing no_faktur
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
        } catch (Throwable $e) {}

        // 9. Ensure store name in settings is AGSMART
        try {
            $pdo->exec("UPDATE tbl_pengaturan SET nama_toko = 'AGSMART' WHERE id = 1 AND (nama_toko LIKE '%Freshmart%' OR nama_toko = '' OR nama_toko IS NULL)");
        } catch (Throwable $e) {}

    } catch (Throwable $e) {}
}

// Function to check database connection status
function checkDatabaseStatus() {
    try {
        $db = getDB();
        if (!$db) {
            return ['status' => false, 'message' => 'Tidak dapat terhubung ke MySQL. Pastikan XAMPP MySQL sudah aktif.'];
        }
        return ['status' => true, 'message' => 'Database siap digunakan.'];
    } catch (Throwable $e) {
        return ['status' => false, 'message' => $e->getMessage()];
    }
}
