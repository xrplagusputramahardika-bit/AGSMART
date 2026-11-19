<?php
/**
 * Customer Order Detail - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Detail Pesanan';
require_once __DIR__ . '/config/helper.php';
header('Link: <assets/img/favicon.svg>; rel="icon"; type="image/svg+xml"');
requireCustomer();
$db = getDB();
$order = null; $detail = []; $canUpload = false; $msg = '';
$idOrder = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($db && $idOrder > 0 && ($_GET['type'] ?? '') === 'transaction') {
    $stmt = $db->prepare("SELECT t.* FROM tbl_transaksi t WHERE t.id_transaksi = ? AND t.id_pelanggan = ? LIMIT 1");
    $stmt->execute([$idOrder, $_SESSION['customer_id']]);
    $transaction = $stmt->fetch();
    if (!$transaction) { header('Location: customer_orders.php'); exit; }
    $stmt = $db->prepare("SELECT nama_produk, harga_satuan, qty, subtotal FROM tbl_detail_transaksi WHERE id_transaksi = ? ORDER BY id_detail");
    $stmt->execute([$idOrder]);
    $items = $stmt->fetchAll();
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Transaksi <?= htmlspecialchars($transaction['no_faktur'] ?: '#' . $idOrder) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/customer.css"></head><body class="customer-app"><div class="customer-topbar"><a href="customer_orders.php" class="btn-icon" style="margin-left:0"><i class="bi bi-arrow-left"></i></a><span class="customer-brand">Detail transaksi</span><span></span></div><main class="transaction-detail-wrap"><section class="transaction-detail-hero"><span class="transaction-icon"><i class="bi bi-check-lg"></i></span><span class="eyebrow">TRANSAKSI BERHASIL</span><h1><?= formatRupiah($transaction['total_belanja']) ?></h1><p><?= htmlspecialchars($transaction['no_faktur'] ?: 'Transaksi #' . $idOrder) ?></p></section><section class="transaction-detail-card"><h2>Informasi pembayaran</h2><div class="detail-line"><span>Tanggal</span><strong><?= date('d M Y · H:i', strtotime($transaction['tanggal'] . ' ' . $transaction['waktu'])) ?></strong></div><div class="detail-line"><span>Metode pembayaran</span><strong><?= htmlspecialchars($transaction['metode_pembayaran'] ?? 'Tunai') ?></strong></div><?php if ((float)$transaction['diskon'] > 0): ?><div class="detail-line"><span>Diskon</span><strong>- <?= formatRupiah($transaction['diskon']) ?></strong></div><?php endif; ?></section><section class="transaction-detail-card"><h2>Rincian belanja <span><?= count($items) ?> item</span></h2><?php foreach ($items as $item): ?><div class="detail-product"><span><strong><?= htmlspecialchars($item['nama_produk']) ?></strong><small><?= (int)$item['qty'] ?> × <?= formatRupiah($item['harga_satuan']) ?></small></span><strong><?= formatRupiah($item['subtotal']) ?></strong></div><?php endforeach; ?><div class="detail-line detail-total"><span>Total dibayar</span><strong><?= formatRupiah($transaction['total_belanja']) ?></strong></div></section><a class="btn btn-success w-100 mt-3" href="customer_orders.php">Kembali ke riwayat</a></main><nav class="customer-bottomnav"><a href="customer_dashboard.php"><i class="bi bi-house-door"></i><span>Beranda</span></a><a href="customer_catalog.php"><i class="bi bi-grid"></i><span>Katalog</span></a><a href="customer_cart.php"><i class="bi bi-cart-fill"></i><span>Keranjang</span></a><a href="customer_orders.php" class="active"><i class="bi bi-receipt"></i><span>Riwayat</span></a><a href="customer_logout.php"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a></nav></body></html><?php
    exit;
}
if ($db && $idOrder > 0) {
    try {
        $stmt = $db->prepare("
            SELECT o.*, p.nama_pelanggan, p.no_kartu
            FROM tbl_pelanggan_order o
            LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = o.id_pelanggan
            WHERE o.id_order = ? AND o.id_pelanggan = ?
        ");
        $stmt->execute([$idOrder, $_SESSION['customer_id']]);
        $order = $stmt->fetch();
        if ($order) {
            $detail = json_decode($order['detail_json'], true) ?: [];
            $canUpload = in_array($order['status'], ['menunggu_verifikasi', 'ditolak'], true) && !empty($order['metode_bayar']) && $order['metode_bayar'] !== 'COD' && strpos($order['metode_bayar'], 'Duitku') !== 0;
        }
    } catch (Exception $e) {}
}
if (!$order) { header('Location: customer_orders.php'); exit; }
$statusBadge = [
    'menunggu_pembayaran' => ['status-pending', 'Menunggu Pembayaran'],
    'menunggu_verifikasi' => ['status-pending', 'Menunggu Verifikasi'],
    'diproses'            => ['status-proses', 'Diproses'],
    'selesai'             => ['status-selesai', 'Selesai'],
    'ditolak'             => ['status-ditolak', 'Ditolak'],
];
$badge = $statusBadge[$order['status']] ?? ['status-pending', 'Menunggu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan #<?= str_pad($idOrder, 5, '0', STR_PAD_LEFT) ?> - AGSMART POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/customer.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="customer-app">
    <div class="customer-topbar">
        <a href="customer_orders.php" class="btn-icon" style="margin-left:0;"><i class="bi bi-arrow-left"></i></a>
        <span class="customer-brand">Detail Pesanan</span>
        <div class="customer-actions"></div>
    </div>
    <div class="customer-section pt-3"><h2>Detail Pesanan</h2></div>
    <div class="container-fluid px-3 pb-3">
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-between align-items-start mb-2">
                    <div>
                        <h5 class="mb-0 fw-bold">Pesanan #<?= str_pad($idOrder, 5, '0', STR_PAD_LEFT) ?></h5>
                        <div class="text-muted small">Dibuat pada <?= date('d M Y H:i', strtotime($order['dibuat_pada'])) ?></div>
                    </div>
                    <span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                </div>
                <div class="d-flex justify-between mb-1"><span class="text-muted small">Metode Pembayaran</span><span class="fw-bold"><?= htmlspecialchars($order['metode_bayar']) ?></span></div>
                <div class="d-flex justify-between mb-1"><span class="text-muted small">Total</span><span class="fw-bold fs-5 text-dark"><?= formatRupiah($order['total']) ?></span></div>
                <?php if ($order['status'] === 'menunggu_pembayaran' && !empty($order['duitku_payment_url'])): ?>
                    <a href="<?= htmlspecialchars($order['duitku_payment_url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success w-100 mt-3" rel="noopener"><i class="bi bi-credit-card me-1"></i>Lanjutkan Pembayaran</a>
                    <div class="proof-hint mt-2">Status akan diperbarui otomatis setelah Duitku mengonfirmasi pembayaran.</div>
                <?php endif; ?>
                <?php if (!empty($order['bukti_transfer'])): ?>
                    <div class="mt-2"><a href="api/payment_proof.php?type=order&id=<?= (int)$order['id_order'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-image me-1"></i>Lihat Bukti Pembayaran</a></div>
                <?php endif; ?>
                <?php if ($canUpload && !$order['bukti_transfer']): ?>
                    <form id="uploadProofForm" method="POST" action="api/customer_request.php" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="action" value="order">
                        <input type="hidden" name="id_order" value="<?= (int)$order['id_order'] ?>">
                        <label class="form-label small text-muted fw-bold mb-2 d-block">Unggah Bukti Pembayaran</label>
                        <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-control form-control-sm mb-2" required>
                        <button type="submit" class="btn btn-primary-wide w-100">Kirim Bukti</button>
                    </form>
                    <div class="proof-hint mt-2">Format JPG, PNG, WEBP atau PDF (maks. 5 MB)</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 fw-bold">Item Pesanan</div>
            <div class="p-3">
                <?php if ($detail): foreach ($detail as $item): ?>
                    <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                        <img src="assets/img/products/<?= (int)($item['id_produk'] ?? 0) ?>.jpg" onerror="this.src='assets/img/no-image.svg'" style="width:56px;height:56px;object-fit:cover;border-radius:8px;background:#f7f7f7;">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark small"><?= htmlspecialchars($item['nama_produk'] ?? 'Produk') ?></div>
                            <div class="text-muted small"><?= (int)($item['qty'] ?? 0) ?> x <?= formatRupiah($item['harga'] ?? 0) ?></div>
                        </div>
                        <div class="fw-bold text-dark"><?= formatRupiah($item['subtotal'] ?? 0) ?></div>
                    </div>
                <?php endforeach; endif; ?>
                <div class="d-flex justify-between mt-2 pt-2 border-top"><span class="fw-bold">Total</span><span class="fw-bold fs-5 text-dark"><?= formatRupiah($order['total']) ?></span></div>
            </div>
        </div>
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
    <script>
        document.getElementById('uploadProofForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api/customer_request.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status) { showToast(json.message, 'success'); setTimeout(()=>location.reload(), 800); }
                else { showToast(json.message, 'danger'); }
            } catch (err) { showToast('Gagal mengunggah bukti', 'danger'); }
        });
    </script>
</body>
</html>
