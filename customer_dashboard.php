<?php
/**
 * Customer Dashboard - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Beranda Pelanggan';
require_once __DIR__ . '/config/helper.php';
requireCustomer();
$db = getDB();
$cust = null; $kategori = []; $produkTerlaris = []; $recentTransactions = [];
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM tbl_pelanggan WHERE id_pelanggan = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $cust = $stmt->fetch();
        $_SESSION['customer_name'] = $cust['nama_pelanggan'];
        $catStmt = $db->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori ASC");
        $kategori = $catStmt->fetchAll();
        $prodStmt = $db->query(" 
            SELECT p.*, k.nama_kategori FROM tbl_produk p
            LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori
            WHERE p.stok > 0
            ORDER BY (SELECT COALESCE(SUM(d.qty), 0) FROM tbl_detail_transaksi d WHERE d.id_produk = p.id_produk) DESC, p.id_produk DESC LIMIT 8
        ");
        $produkTerlaris = $prodStmt->fetchAll();
        $historyStmt = $db->prepare("SELECT t.id_transaksi, t.no_faktur, t.tanggal, t.waktu, t.total_belanja, t.metode_pembayaran, COUNT(d.id_detail) AS jumlah_item FROM tbl_transaksi t LEFT JOIN tbl_detail_transaksi d ON d.id_transaksi = t.id_transaksi WHERE t.id_pelanggan = ? GROUP BY t.id_transaksi ORDER BY t.tanggal DESC, t.waktu DESC, t.id_transaksi DESC LIMIT 3");
        $historyStmt->execute([$_SESSION['customer_id']]);
        $recentTransactions = $historyStmt->fetchAll();
    } catch (Exception $e) {}
}
$tierClass = 'tier-' . strtolower(($cust['tipe_member'] ?? 'Silver'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - AGSMART POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/customer.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="customer-app">
    <div class="customer-topbar">
        <a href="customer_dashboard.php" class="customer-brand">
            <i class="bi bi-store"></i> AGSMART <span style="color:#111c2d">MART</span>
        </a>
        <div class="customer-search">
            <i class="bi bi-search"></i>
            <input type="text" id="searchProduct" placeholder="Cari produk..." class="form-control">
        </div>
        <div class="customer-actions">
            <a href="customer_cart.php" class="btn-icon position-relative" title="Keranjang">
                <i class="bi bi-cart"></i>
                <span class="cart-badge" hidden>0</span>
            </a>
            <a href="#" class="btn-icon" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas" title="Menu">
                <i class="bi bi-list"></i>
            </a>
        </div>
    </div>
    <div class="customer-promo">
        <div class="promo-text"><strong>Belanja hemat setiap hari</strong><span>Produk pilihan untuk kebutuhan rumah Anda</span></div>
        <div class="promo-visual"><i class="bi bi-bag-heart-fill"></i></div>
    </div>
    <div class="member-card">
        <div class="chip"></div>
        <div class="card-no"><?= htmlspecialchars($cust['no_kartu'] ?? 'MBR-') ?></div>
        <h3><?= htmlspecialchars($cust['nama_pelanggan']) ?></h3>
        <span class="tier <?= $tierClass ?>"><?= htmlspecialchars(ucfirst($cust['tipe_member'] ?? 'Silver')) ?> Member</span>
        <div class="member-stats">
            <div class="member-stat"><div class="val"><?= formatRupiah($cust['saldo'] ?? 0) ?></div><div class="lbl">Saldo E-Wallet</div></div>
            <div class="member-stat"><div class="val"><?= (int)($cust['poin'] ?? 0) ?> Pts</div><div class="lbl">Poin Member</div></div>
            <div class="member-stat"><div class="val"><?= htmlspecialchars(ucfirst($cust['tipe_member'] ?? 'Silver')) ?></div><div class="lbl">Tingkat Member</div></div>
        </div>
        <div class="d-grid gap-2 mt-3">
            <a href="customer_catalog.php" class="btn btn-primary-wide" style="background:var(--shopee-green);border:none;">Belanja Sekarang</a>
            <a href="customer_orders.php" class="btn btn-outline-wide">Lihat Pesanan Saya</a>
        </div>
    </div>
    <section class="transaction-preview">
        <div class="transaction-preview-head"><div><span class="eyebrow">AKTIVITAS ANDA</span><h2>Riwayat transaksi</h2></div><a href="customer_orders.php">Lihat semua <i class="bi bi-arrow-right"></i></a></div>
        <?php if (!$recentTransactions): ?>
            <div class="transaction-empty"><i class="bi bi-receipt"></i><span>Transaksi dari kasir yang terhubung ke akun ini akan muncul di sini.</span></div>
        <?php else: foreach ($recentTransactions as $transaction): ?>
            <a class="transaction-row" href="customer_order_detail.php?id=<?= (int)$transaction['id_transaksi'] ?>">
                <span class="transaction-icon"><i class="bi bi-bag-check"></i></span><span class="transaction-copy"><strong><?= htmlspecialchars($transaction['no_faktur'] ?: 'Transaksi #' . $transaction['id_transaksi']) ?></strong><small><?= date('d M Y · H:i', strtotime($transaction['tanggal'] . ' ' . $transaction['waktu'])) ?> · <?= (int)$transaction['jumlah_item'] ?> item</small></span><span class="transaction-amount"><?= formatRupiah($transaction['total_belanja']) ?><small><?= htmlspecialchars($transaction['metode_pembayaran'] ?? 'Tunai') ?></small></span><i class="bi bi-chevron-right transaction-chevron"></i>
            </a>
        <?php endforeach; endif; ?>
    </section>
    <div class="customer-section"><h2>Kategori</h2></div>
    <div class="category-scroll">
        <button class="category-pill active">Semua</button>
        <?php foreach ($kategori as $kat): ?>
            <button class="category-pill"><?= htmlspecialchars($kat['nama_kategori']) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="customer-section">
        <h2>Rekomendasi Untuk Anda</h2>
        <a href="customer_catalog.php" class="customer-viewall">Lihat semua &rarr;</a>
    </div>
    <div class="product-grid">
        <?php if ($produkTerlaris): foreach ($produkTerlaris as $p): ?>
            <a href="customer_product.php?id=<?= (int)$p['id_produk'] ?>" class="product-card">
                <img src="assets/img/products/<?= (int)$p['id_produk'] ?>.jpg" onerror="this.src='assets/img/no-image.svg'" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                <div class="product-info">
                    <div class="product-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                    <div class="product-price"><?= formatRupiah($p['harga_jual']) ?></div>
                    <div class="product-stock <?= (int)$p['stok'] <= 0 ? 'out' : '' ?>">
                        <?= (int)$p['stok'] > 0 ? 'Stok: ' . (int)$p['stok'] : 'Habis' ?>
                    </div>
                </div>
                <div class="product-footer">
                    <span class="badge bg-light text-dark">+Keranjang</span>
                    <button class="add-btn" onclick="event.preventDefault();appAddToCart(<?= (int)$p['id_produk'] ?>, '<?= addslashes(htmlspecialchars($p['nama_produk'])) ?>', <?= (float)$p['harga_jual'] ?>, <?= (int)$p['stok'] ?>);">Beli</button>
                </div>
            </a>
        <?php endforeach; else: ?>
            <div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-box"></i><h3>Belum ada produk tersedia</h3></div>
        <?php endif; ?>
    </div>
    <nav class="customer-bottomnav">
        <a href="customer_dashboard.php" class="active"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
        <a href="customer_catalog.php"><i class="bi bi-grid"></i><span>Katalog</span></a>
        <a href="customer_cart.php" class="position-relative"><i class="bi bi-cart-fill"></i><span>Keranjang</span><span class="cart-badge" hidden>0</span></a>
        <a href="customer_orders.php"><i class="bi bi-box-arrow-in-right"></i><span>Pesanan</span></a>
        <a href="#" class="text-muted" onclick="event.preventDefault();document.getElementById('profileDropdown').click();"><i class="bi bi-person-circle"></i><span>Akun</span></a>
    </nav>
    <div class="offcanvas offcanvas-end" id="menuOffcanvas" tabindex="-1" style="width: 280px;">
        <div class="offcanvas-header bg-white">
            <h5 class="offcanvas-title fw-bold">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <a href="customer_dashboard.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-house-door me-2"></i>Beranda</a>
            <a href="customer_catalog.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-grid me-2"></i>Katalog Produk</a>
            <a href="customer_cart.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-cart me-2"></i>Keranjang Belanja</a>
            <a href="customer_orders.php" class="dropdown-item py-3 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Pesanan Saya</a>
            <hr class="my-3">
            <a href="customer_logout.php" class="dropdown-item py-3 text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a>
        </div>
    </div>
    <div style="display:none;"><button id="profileDropdown" class="btn" data-bs-toggle="modal" data-bs-target="#profileModal"></button></div>
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-xl">
                <div class="modal-header border-0"><h5 class="modal-title fw-bold">Akun Saya</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p><strong>Nama:</strong> <?= htmlspecialchars($cust['nama_pelanggan'] ?? '-') ?></p>
                    <p><strong>Kartu Member:</strong> <?= htmlspecialchars($cust['no_kartu'] ?? '-') ?></p>
                    <p><strong>Tingkat:</strong> <?= htmlspecialchars(ucfirst($cust['tipe_member'] ?? 'Silver')) ?></p>
                    <p><strong>Saldo:</strong> <?= formatRupiah($cust['saldo'] ?? 0) ?></p>
                    <p><strong>Poin:</strong> <?= (int)($cust['poin'] ?? 0) ?> Pts</p>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
    <script src="assets/js/customer.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('searchProduct');
            if (input) {
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); const q = input.value.trim(); if (q) location.href = 'customer_catalog.php?q=' + encodeURIComponent(q); }
                });
            }
        });
    </script>
</body>
</html>
