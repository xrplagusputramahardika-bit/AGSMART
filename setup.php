<?php
/**
 * Setup & Database Migration Wizard (With Member Card Tap System)
 * AGSmart POS System
 */
require_once __DIR__ . '/config/helper.php';
requireAdmin();

$message = null;
$messageType = 'info';

// Handle Action
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) || (isset($_GET['auto']) && $_GET['auto'] === '1')) {
    $action = $_POST['action'] ?? 'migrate';
    $redirect = $_POST['redirect'] ?? ($_GET['redirect'] ?? '');
    
    if ($action === 'migrate') {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            // Connect to root to ensure database exists
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            
            // Connect to database
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // 1. Upgrade database tables & missing columns automatically
            autoMigrateDatabase($pdo);

            // 2. Execute main SQL script for missing tables / seed data
            $sqlFile = __DIR__ . '/database/db_tokokita.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }

            // Run migration once more to ensure all columns and data backfilled
            autoMigrateDatabase($pdo);
            
            if ($redirect === 'index') {
                setFlash('success', 'Database berhasil disetup! 26 produk contoh dan sistem kartu member siap digunakan.');
                header("Location: index.php");
                exit;
            }

            $message = "Sukses! Database <b>" . DB_NAME . "</b> berhasil disetup &amp; dimigrasikan lengkap dengan Sistem Kartu Member (RFID Tap/Barcode), produk contoh, kategori, dan pengaturan.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Gagal melakukan migrasi: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Check database and tables status
$dbStatus = false;
$tableStats = [];
$db = getDB();

if ($db) {
    $dbStatus = true;
    $checkTables = [
        'tbl_pelanggan'        => 'Data Pelanggan & Kartu Member',
        'tbl_produk'           => 'Data Produk / Barang',
        'tbl_kategori'         => 'Kategori Produk',
        'tbl_transaksi'        => 'Data Transaksi Penjualan',
        'tbl_detail_transaksi' => 'Detail Item Transaksi',
        'tbl_pengaturan'       => 'Pengaturan Toko & Struk',
        'tbl_user'             => 'User Kasir / Admin',
    ];
    
    foreach ($checkTables as $table => $label) {
        try {
            $countStmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            $tableStats[$table] = [
                'label' => $label,
                'exists' => true,
                'count' => (int)$count
            ];
        } catch (Exception $e) {
            $tableStats[$table] = [
                'label' => $label,
                'exists' => false,
                'count' => 0
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Migration - AGSmart POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .setup-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 820px;
            width: 100%;
        }
        .badge-status {
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="setup-card p-4 p-md-5">
    <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-secondary pb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white p-3 rounded-4 shadow">
                <i class="bi bi-database-fill-gear fs-2"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-1">Database Wizard &amp; Migration</h3>
                <p class="text-secondary mb-0 small">Sistem Kasir Modern AGSmart &bull; Target: <code><?= DB_NAME ?></code></p>
            </div>
        </div>
        <a href="index.php" class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Ke Kasir
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-3" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi <?= $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
                <div><?= $message ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Status Koneksi -->
    <div class="card bg-dark border-secondary mb-4 rounded-4">
        <div class="card-body p-3 p-md-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h6 class="text-uppercase text-secondary fw-semibold small mb-2">Status Koneksi MySQL</h6>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($dbStatus): ?>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 badge-status">
                                <i class="bi bi-check-circle me-1"></i> Terhubung ke MySQL
                            </span>
                            <span class="text-light small">Host: <b><?= DB_HOST ?></b> | DB: <b><?= DB_NAME ?></b></span>
                        <?php else: ?>
                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 badge-status">
                                <i class="bi bi-x-circle me-1"></i> Gagal Terhubung
                            </span>
                            <span class="text-danger small">Pastikan Apache & MySQL di XAMPP sudah berjalan!</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <form method="POST" onsubmit="return confirm('Jalankan migrasi? Tabel, data pendukung, dan sistem kartu member akan diperbarui secara otomatis.');">
                        <input type="hidden" name="action" value="migrate">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold">
                            <i class="bi bi-play-circle-fill me-1"></i> Jalankan Migrasi Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Tabel Database -->
    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-table text-primary"></i> Tabel Database &amp; Data
    </h5>
    
    <div class="table-responsive rounded-3 border border-secondary mb-4">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="table-secondary text-secondary small text-uppercase">
                <tr>
                    <th class="ps-3">Nama Tabel</th>
                    <th>Fungsi</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Jumlah Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($dbStatus && !empty($tableStats)): ?>
                    <?php foreach ($tableStats as $tbl => $info): ?>
                        <tr>
                            <td class="ps-3 font-monospace text-info"><?= $tbl ?></td>
                            <td><?= $info['label'] ?></td>
                            <td>
                                <?php if ($info['exists']): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                                        <i class="bi bi-check2"></i> Siap
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill">
                                        <i class="bi bi-x"></i> Belum Dibuat
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3 fw-bold">
                                <?= $info['exists'] ? number_format($info['count'], 0, ',', '.') . ' baris' : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-secondary">
                            <i class="bi bi-database-slash fs-1 d-block mb-2"></i>
                            Koneksi database belum aktif. Silakan aktifkan MySQL XAMPP Anda.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
        <a href="index.php" class="btn btn-success btn-lg rounded-pill px-4 fw-bold">
            <i class="bi bi-cart-check-fill me-2"></i> Buka Aplikasi Kasir
        </a>
        <div class="d-flex gap-2">
            <a href="backup_restore.php" class="btn btn-outline-info rounded-pill px-3">
                <i class="bi bi-arrow-repeat me-1"></i> Backup &amp; Restore
            </a>
            <a href="database/db_tokokita.sql" download class="btn btn-outline-light rounded-pill px-3">
                <i class="bi bi-download me-1"></i> Download File SQL
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
