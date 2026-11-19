<?php
/**
 * Customer Catalog - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Katalog Produk';
require_once __DIR__ . '/config/helper.php';
requireCustomer();
$db = getDB();
$kategori = []; $produk = []; $q = ''; $katFilter = '';
if ($db) {
    try {
        $catStmt = $db->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori ASC");
        $kategori = $catStmt->fetchAll();
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $katFilter = isset($_GET['kat']) ? $_GET['kat'] : '';
        $sql = "SELECT p.*, k.nama_kategori FROM tbl_produk p LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori WHERE p.stok > 0";
        $params = [];
        if ($q !== '') { $sql .= " AND p.nama_produk LIKE ?"; $params[] = "%$q%"; }
        if ($katFilter !== '') { $sql .= " AND k.nama_kategori = ?"; $params[] = $katFilter; }
        $sql .= " ORDER BY p.id_produk DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $produk = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - AGSMART POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/customer.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="customer-app">
    <div class="customer-topbar">
        <a href="customer_dashboard.php" class="customer-brand">
            <i class="bi bi-store"></i> AGSMART <span style="color:#111c2d">MART</span>
        </a>
        <form method="GET" class="customer-search d-flex">
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Cari produk..." class="form-control" value="<?= htmlspecialchars($q) ?>">
            <button type="submit" class="btn border-0 bg-transparent ps-3"><i class="bi bi-search"></i></button>
        </form>
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
        <div class="promo-text"><strong>Gratis Ongkir</strong><span>Belanja di atas Rp 200.000</span></div>
        <div class="promo-visual">📦</div>
    </div>
    <div class="customer-section">
        <h2>Semua Produk <?= ($q !== '' ? 'untuk "' . htmlspecialchars($q) . '"' : '') ?></h2>
        <a href="customer_dashboard.php" class="customer-viewall">Kembali ke Beranda &rarr;</a>
    </div>
    <div class="category-scroll">
        <button class="category-pill <?= $katFilter === '' ? 'active' : '' ?>" onclick="location.href='customer_catalog.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>'">Semua</button>
        <?php foreach ($kategori as $kat): ?>
            <button class="category-pill <?= $katFilter === $kat['nama_kategori'] ? 'active' : '' ?>" onclick="location.href='customer_catalog.php?kat=<?= urlencode($kat['nama_kategori']) ?>'"><?= htmlspecialchars($kat['nama_kategori']) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="product-grid">
        <?php if ($produk): foreach ($produk as $p): ?>
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
            <div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-search"></i><h3>Produk tidak ditemukan</h3></div>
        <?php endif; ?>
    </div>
    <nav class="customer-bottomnav">
        <a href="customer_dashboard.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
        <a href="customer_catalog.php" class="active"><i class="bi bi-grid-fill"></i><span>Katalog</span></a>
        <a href="customer_cart.php" class="position-relative"><i class="bi bi-cart-fill"></i><span>Keranjang</span><span class="cart-badge" hidden>0</span></a>
        <a href="customer_orders.php"><i class="bi bi-box-arrow-in-right"></i><span>Pesanan</span></a>
        <a href="customer_logout.php"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
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
    <script src="assets/js/customer.js"></script>
</body>
</html>