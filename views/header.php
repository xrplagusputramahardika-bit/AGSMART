<?php
/**
 * Layout Header - Modern Retaillet POS Design
 * AGSmart POS System
 */
require_once __DIR__ . '/../config/helper.php';
requireCashier();

$pengaturan = getPengaturan();
$activePage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
$isAdmin = currentStaffRole() === 'admin';
$dbCheck = checkDatabaseStatus();
$pendingOrders = 0;
$pendingTopups = 0;
try {
    $pendingDb = getDB();
    if ($pendingDb) {
        $pendingOrders = (int)$pendingDb->query("SELECT COUNT(*) FROM tbl_pelanggan_order WHERE status='menunggu_verifikasi'")->fetchColumn();
        $pendingTopups = (int)$pendingDb->query("SELECT COUNT(*) FROM tbl_topup_request WHERE status='menunggu'")->fetchColumn();
    }
} catch (Throwable $e) {}
$pendingTotal = $pendingOrders + $pendingTopups;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>AGSMART POS</title>
    
    <!-- Favicon: AGSMART Brand Icon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js for Dashboard Visuals -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/print.css" media="print">
</head>
<body>

<div class="app-wrapper">

    <!-- ========================================================
         LEFT SIDEBAR NAVIGATION (Matching exact Retaillet Design)
    ======================================================== -->
    <aside class="app-sidebar" id="appSidebar">
        <!-- Brand / Logo -->
        <a href="index.php" class="sidebar-brand">
            <img src="assets/img/logo-agsmart.svg" alt="AGSMART - Point of Sale" height="42" class="brand-logo-img">
        </a>

        <!-- Menu Groups -->
        <div class="sidebar-menu">
            <!-- Main Section -->
            <div class="sidebar-section-title">Main</div>
            <?php if ($isAdmin): ?>
                <a href="index.php" class="sidebar-link <?= ($activePage === 'index') ? 'active' : '' ?>">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>
            <a href="pos.php" class="sidebar-link <?= ($activePage === 'pos') ? 'active' : '' ?>">
                <i class="bi bi-calculator-fill"></i>
                <span>Kasir (POS)</span>
            </a>

            <!-- Inventory Section -->
            <?php if ($isAdmin): ?>
            <div class="sidebar-section-title mt-2">Inventory</div>
            <a href="produk.php" class="sidebar-link <?= ($activePage === 'produk' && !isset($_GET['filter_stok'])) ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i>
                <span>Products</span>
            </a>
            <a href="produk.php?filter_stok=menipis" class="sidebar-link <?= (isset($_GET['filter_stok']) && $_GET['filter_stok'] === 'menipis') ? 'active' : '' ?>">
                <i class="bi bi-hourglass-split"></i>
                <span>Low Stocks</span>
            </a>
            <a href="kategori.php" class="sidebar-link <?= ($activePage === 'kategori') ? 'active' : '' ?>">
                <i class="bi bi-tags"></i>
                <span>Category</span>
            </a>

            <!-- Member & Pelanggan Section -->
            <div class="sidebar-section-title mt-2">Pelanggan &amp; Member</div>
            <a href="pelanggan.php" class="sidebar-link <?= ($activePage === 'pelanggan') ? 'active' : '' ?>">
                <i class="bi bi-person-vcard"></i>
                <span>Data Member</span>
            </a>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <a href="online_orders.php" class="sidebar-link <?= ($activePage === 'online_orders') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i><span>Pesanan &amp; Top Up</span><span id="pendingBadge" class="badge rounded-pill text-bg-danger ms-auto" <?= $pendingTotal ? '' : 'hidden' ?>><?= $pendingTotal ?></span>
                </a>
                <a href="customer_catalog.php" class="sidebar-link <?= ($activePage === 'customer_catalog') ? 'active' : '' ?>">
                    <i class="bi bi-store"></i>
                    <span>Toko Online (Pelanggan)</span>
                </a>
            <?php endif; ?>
            <a href="logout.php" class="sidebar-link text-danger"><i class="bi bi-box-arrow-right"></i><span>Log out</span></a>
            <a href="javascript:void(0)" onclick="openTapMemberModal()" class="sidebar-link">
                <i class="bi bi-nfc text-warning"></i>
                <span>Tap Kartu Member</span>
            </a>

            <!-- Sales & Reports -->
            <div class="sidebar-section-title mt-2">Sales</div>
            <a href="transaksi.php" class="sidebar-link <?= ($activePage === 'transaksi') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Transaksi</span>
            </a>
            <?php if ($isAdmin): ?>
                <a href="laporan.php" class="sidebar-link <?= ($activePage === 'laporan') ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Laporan Penjualan</span>
                </a>
            <?php endif; ?>

            <!-- System -->
            <?php if ($isAdmin): ?>
            <div class="sidebar-section-title mt-2">System</div>
            <a href="backup_restore.php" class="sidebar-link <?= ($activePage === 'backup_restore') ? 'active' : '' ?>">
                <i class="bi bi-database-down"></i>
                <span>Backup &amp; Restore</span>
            </a>
            <a href="setup.php" class="sidebar-link <?= ($activePage === 'setup') ? 'active' : '' ?>">
                <i class="bi bi-database-gear"></i>
                <span>Status &amp; Migrasi DB</span>
            </a>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ========================================================
         MAIN CONTENT AREA
    ======================================================== -->
    <div class="app-main">

        <!-- TOPBAR HEADER -->
        <header class="app-topbar">
            <!-- Left Controls: Toggle & Search -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-light border-0 d-lg-none" onclick="document.getElementById('appSidebar').classList.toggle('show')">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="position-relative d-none d-sm-block">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" class="topbar-search ps-5" placeholder="Search..." onclick="window.location.href='pos.php'">
                    <span class="badge bg-light text-muted border position-absolute top-50 end-0 translate-middle-y me-2 small">⌘ K</span>
                </div>
            </div>

            <!-- Right Controls: Actions, Buttons, Badges -->
            <div class="d-flex align-items-center gap-2">
                <!-- Add New Button (Orange) -->
                <?php if ($isAdmin): ?>
                <a href="produk.php" class="topbar-btn-orange">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add New</span>
                </a>
                <?php endif; ?>

                <!-- POS Button (Dark Navy) -->
                <a href="pos.php" class="topbar-btn-pos shadow-sm">
                    <i class="bi bi-calculator"></i>
                    <span>POS</span>
                </a>

                <!-- Country Flag -->
                <div class="icon-circle-btn d-none d-sm-flex" title="Bahasa Indonesia">
                    🇮🇩
                </div>

                <!-- Fullscreen Toggle -->
                <button type="button" class="icon-circle-btn d-none d-md-flex" title="Layar Penuh" onclick="toggleFullScreen()">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>

                <!-- Notification Bell with Dot Badge -->
                <a href="produk.php?filter_stok=menipis" class="icon-circle-btn" title="Notifikasi Stok Menipis">
                    <i class="bi bi-bell"></i>
                    <span class="notif-badge-dot">01</span>
                </a>

                <!-- Messages -->
                <button type="button" class="icon-circle-btn d-none d-sm-flex" title="Pesan">
                    <i class="bi bi-envelope"></i>
                </button>

                <!-- Profile User Avatar -->
                <div class="dropdown ms-1">
                    <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Admin" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                            <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle" style="width: 10px; height: 10px;"></span>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><h6 class="dropdown-header"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Kasir') ?></h6></li>
                        <li><a class="dropdown-item" href="pos.php"><i class="bi bi-calculator me-2"></i> Buka Kasir POS</a></li>
                        <li><a class="dropdown-item" href="backup_restore.php"><i class="bi bi-database me-2"></i> Backup Data</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div id="pendingNotice" class="container-fluid px-4 mt-3" <?= $pendingTotal ? '' : 'hidden' ?>>
            <a href="online_orders.php" class="alert alert-warning d-flex align-items-center gap-2 mb-0 text-decoration-none"><i class="bi bi-bell-fill"></i><span>Ada <b id="pendingNoticeCount"><?= $pendingTotal ?></b> permintaan pelanggan menunggu verifikasi pembayaran.</span><b class="ms-auto">Periksa sekarang <i class="bi bi-arrow-right"></i></b></a>
        </div>

        <!-- Flash Message Container -->
        <?php if ($flash): ?>
            <div class="container-fluid px-4 mt-3">
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill text-success' : ($flash['type'] === 'danger' ? 'bi-exclamation-octagon-fill text-danger' : 'bi-info-circle-fill text-primary') ?> fs-5"></i>
                        <div class="fw-medium"><?= $flash['message'] ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        <script>
            window.setInterval(async () => {
                try {
                    const response = await fetch('api/pending_count.php', {cache: 'no-store'});
                    const result = await response.json();
                    if (!result.status) return;
                    const total = Number(result.data.total || 0);
                    const badge = document.getElementById('pendingBadge');
                    const notice = document.getElementById('pendingNotice');
                    badge.textContent = total;
                    badge.hidden = total === 0;
                    notice.hidden = total === 0;
                    document.getElementById('pendingNoticeCount').textContent = total;
                } catch (error) {}
            }, 15000);
        </script>
