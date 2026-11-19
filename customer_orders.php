<?php
/**
 * Customer Orders - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Pesanan Saya';
require_once __DIR__ . '/config/helper.php';
requireCustomer();
$db = getDB();
$orders = []; $transactions = [];
if ($db) {
    require_once __DIR__ . '/config/duitku_orders.php';
    syncExpiredDuitkuOrders($db, (int)$_SESSION['customer_id']);
    if (($_GET['payment'] ?? '') === 'duitku' && !empty($_GET['merchantOrderId'])) {
        syncDuitkuReturn($db, (string)$_GET['merchantOrderId'], (int)$_SESSION['customer_id']);
    }
    try {
        $stmt = $db->prepare("SELECT t.id_transaksi, t.no_faktur, t.tanggal, t.waktu, t.total_belanja, t.metode_pembayaran, COUNT(d.id_detail) AS jumlah_item FROM tbl_transaksi t LEFT JOIN tbl_detail_transaksi d ON d.id_transaksi = t.id_transaksi WHERE t.id_pelanggan = ? GROUP BY t.id_transaksi ORDER BY t.tanggal DESC, t.waktu DESC, t.id_transaksi DESC");
        $stmt->execute([$_SESSION['customer_id']]);
        $transactions = $stmt->fetchAll();
        $stmt = $db->prepare("
            SELECT o.*, p.nama_pelanggan, p.no_kartu
            FROM tbl_pelanggan_order o
            LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = o.id_pelanggan
            WHERE o.id_pelanggan = ?
            ORDER BY o.id_order DESC
        ");
        $stmt->execute([$_SESSION['customer_id']]);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {}
}
$statusBadge = [
    'menunggu_pembayaran' => ['status-pending', 'Menunggu Pembayaran'],
    'menunggu_verifikasi' => ['status-pending', 'Menunggu Verifikasi'],
    'diproses'            => ['status-proses', 'Diproses'],
    'selesai'             => ['status-selesai', 'Selesai'],
    'ditolak'             => ['status-ditolak', 'Ditolak'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - AGSMART POS</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/customer.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="customer-app">
    <div class="customer-topbar">
        <a href="customer_dashboard.php" class="btn-icon" style="margin-left:0;"><i class="bi bi-arrow-left"></i></a>
        <span class="customer-brand">Pesanan</span>
        <div class="customer-actions"></div>
    </div>
    <div class="customer-section pt-3"><span class="eyebrow">AKUN SAYA</span><h2>Riwayat transaksi</h2><p class="text-muted mb-0">Belanja kasir dan pesanan online Anda.</p></div>
    <div class="container-fluid px-3">
        <?php if (!$orders && !$transactions): ?>
            <div class="empty-state"><i class="bi bi-receipt"></i><h3>Belum ada transaksi</h3><p>Riwayat belanja akan muncul di sini.</p><a class="btn btn-success" href="customer_catalog.php">Mulai belanja</a></div>
        <?php endif; ?>
        <?php foreach ($transactions as $t): ?>
            <a class="order-card transaction-history-card" href="customer_order_detail.php?type=transaction&id=<?= (int)$t['id_transaksi'] ?>">
                <span class="transaction-icon"><i class="bi bi-bag-check"></i></span><span class="order-info"><span class="order-no"><?= htmlspecialchars($t['no_faktur'] ?: 'TRX-' . $t['id_transaksi']) ?></span><span class="order-title"><?= (int)$t['jumlah_item'] ?> item · <?= htmlspecialchars($t['metode_pembayaran'] ?? 'Tunai') ?></span><span class="order-date"><?= date('d M Y · H:i', strtotime($t['tanggal'] . ' ' . $t['waktu'])) ?></span></span><span class="order-total"><?= formatRupiah($t['total_belanja']) ?></span><i class="bi bi-chevron-right transaction-chevron"></i>
            </a>
        <?php endforeach; ?>
        <?php foreach ($orders as $o): $detail = json_decode($o['detail_json'], true) ?: [];
            $badge = $statusBadge[$o['status']] ?? ['status-pending', 'Menunggu'];
            $firstItem = $detail[0] ?? null;
        ?>
            <div class="order-card">
                <?php if ($firstItem): ?>
                    <img src="assets/img/products/<?= (int)$firstItem['id_produk'] ?>.jpg" onerror="this.src='assets/img/no-image.svg'" class="order-img" alt="">
                <?php else: ?>
                    <div class="order-img" style="background:#f7f7f7;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:1.5rem;">📦</div>
                <?php endif; ?>
                <div class="order-info">
                    <div class="order-no">#<?= str_pad((int)$o['id_order'], 5, '0', STR_PAD_LEFT) ?></div>
                    <div class="order-title">
                        <?= count($detail) ?> item<?= count($detail) > 1 ? 's' : '' ?>
                        <?php if (count($detail) > 1): ?> +<?= count($detail) - 1 ?> lain<?php endif; ?>
                    </div>
                    <div class="order-date"><?= date('d M Y', strtotime($o['dibuat_pada'])) ?></div>
                </div>
                <span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                <div class="order-total"><?= formatRupiah($o['total']) ?></div>
                <?php if ($o['status'] === 'menunggu_pembayaran' && !empty($o['duitku_payment_url'])): ?>
                    <a href="<?= htmlspecialchars($o['duitku_payment_url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm" rel="noopener">Bayar Sekarang</a>
                <?php endif; ?>
                <a href="customer_order_detail.php?id=<?= (int)$o['id_order'] ?>" class="btn btn-outline-secondary btn-sm">Lihat Detail</a>
            </div>
        <?php endforeach; ?>
    </div>
    <nav class="customer-bottomnav">
        <a href="customer_dashboard.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
        <a href="customer_catalog.php"><i class="bi bi-grid"></i><span>Katalog</span></a>
        <a href="customer_cart.php" class="position-relative"><i class="bi bi-cart-fill"></i><span>Keranjang</span><span class="cart-badge" hidden>0</span></a>
        <a href="customer_orders.php" class="active"><i class="bi bi-box-arrow-in-right"></i><span>Pesanan</span></a>
        <a href="customer_logout.php"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
    </nav>
    <div class="offcanvas offcanvas-end" id="menuOffcanvas" tabindex="-1" style="width: 280px;">
        <div class="offcanvas-header bg-white"><h5 class="offcanvas-title fw-bold">Menu</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <a href="customer_dashboard.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-house-door me-2"></i>Beranda</a>
            <a href="customer_catalog.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-grid me-2"></i>Katalog Produk</a>
            <a href="customer_cart.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-cart me-2"></i>Keranjang Belanja</a>
            <a href="customer_orders.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Pesanan Saya</a>
            <hr class="my-3">
            <a href="customer_logout.php" class="dropdown-item py-3 text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a>
        </div>
    </div>
    <script src="assets/js/customer.js"></script>
</body>
</html>
