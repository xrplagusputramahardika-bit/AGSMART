<?php
/**
 * Customer Product Detail - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Detail Produk';
require_once __DIR__ . '/config/helper.php';
requireCustomer();
$db = getDB();
$produk = null; $kategori = [];
$idProduk = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($db && $idProduk > 0) {
    try {
        $stmt = $db->prepare("SELECT p.*, k.nama_kategori FROM tbl_produk p LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori WHERE p.id_produk = ?");
        $stmt->execute([$idProduk]);
        $produk = $stmt->fetch();
        $catStmt = $db->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori ASC");
        $kategori = $catStmt->fetchAll();
    } catch (Exception $e) {}
}
if (!$produk) { header('Location: customer_catalog.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produk['nama_produk']) ?> - AGSMART POS</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/customer.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .prod-gallery { border-radius: 16px; border: 1px solid var(--shopee-border); overflow: hidden; background: #f7f7f7; height: 320px; display: flex; align-items: center; justify-content: center; }
        .prod-gallery img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .prod-sticky { position: sticky; top: 100px; }
        .qty-selector { display: flex; align-items: center; gap: 8px; border: 1px solid var(--shopee-border); border-radius: 12px; width: 120px; background: #fff; }
        .qty-selector button { width: 36px; height: 36px; border: none; background: #f7f7f7; font-size: 1.2rem; font-weight: 800; cursor: pointer; border-radius: 8px; color: var(--shopee-text); }
        .qty-selector button:active { background: var(--shopee-orange-light); }
        .qty-selector input { width: 48px; text-align: center; border: none; background: transparent; font-weight: 800; font-size: 1rem; color: var(--shopee-text); }
    </style>
</head>
<body class="customer-app">
    <div class="customer-topbar">
        <a href="customer_catalog.php" class="btn-icon" style="margin-left:0;"><i class="bi bi-arrow-left"></i></a>
        <span class="customer-brand">Produk</span>
        <div class="customer-actions">
            <a href="customer_cart.php" class="btn-icon position-relative"><i class="bi bi-cart"></i><span class="cart-badge" hidden>0</span></a>
            <a href="#" class="btn-icon" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas"><i class="bi bi-list"></i></a>
        </div>
    </div>
    <div class="customer-section pt-3">
        <a href="customer_catalog.php?kat=<?= urlencode($produk['nama_kategori'] ?? '') ?>" class="text-muted text-decoration-none small"><?= htmlspecialchars($produk['nama_kategori'] ?? '-') ?></a>
    </div>
    <div class="container-fluid px-3 pb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="prod-gallery">
                    <img src="assets/img/products/<?= (int)$produk['id_produk'] ?>.jpg" onerror="this.src='assets/img/no-image.svg'" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                </div>
            </div>
            <div class="col-md-6 prod-sticky">
                <h2 class="fw-bold mb-2"><?= htmlspecialchars($produk['nama_produk']) ?></h2>
                <div class="d-flex align-items-center mb-2"><span class="badge bg-light text-dark border">★ 4.8</span><span class="ms-2 text-muted small">(128 ulasan)</span></div>
                <div class="mb-3">
                    <div class="d-flex justify-between mb-1"><span class="text-muted small">Harga</span><span class="fw-bold fs-5 text-dark"><?= formatRupiah($produk['harga_jual']) ?></span></div>
                    <div class="d-flex justify-between"><span class="text-muted small">Stok</span><span class="fw-bold <?= (int)$produk['stok'] <= 0 ? 'text-danger' : 'text-success' ?>"><?= (int)$produk['stok'] > 0 ? 'Tersedia (' . (int)$produk['stok'] . ')' : 'Habis' ?></span></div>
                </div>
                <form id="addForm" method="POST" onsubmit="return false;">
                    <div class="mb-3"><label class="form-label small text-muted fw-bold">Qty</label><div class="qty-selector"><button type="button" onclick="changeQty(-1)">−</button><input type="number" id="prodQty" value="1" min="1" max="<?= (int)$produk['stok'] ?>"><button type="button" onclick="changeQty(1)">+</button></div></div>
                    <button type="button" class="btn-primary-wide w-100 mb-2" id="btnAdd" onclick="appAddToCart(<?= (int)$produk['id_produk'] ?>, '<?= addslashes(htmlspecialchars($produk['nama_produk'])) ?>', <?= (float)$produk['harga_jual'] ?>, <?= (int)$produk['stok'] ?>, document.getElementById('prodQty').value);">Tambah ke Keranjang</button>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="location.href='customer_cart.php'">Lihat Keranjang</button>
                </form>
                <div class="mt-3 p-3 bg-light rounded-3"><h6 class="fw-bold mb-2">Deskripsi</h6><p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($produk['deskripsi'] ?? 'Tidak ada deskripsi tersedia.')) ?></p></div>
            </div>
        </div>
    </div>
    <nav class="customer-bottomnav">
        <a href="customer_dashboard.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
        <a href="customer_catalog.php" class="active"><i class="bi bi-grid-fill"></i><span>Katalog</span></a>
        <a href="customer_cart.php" class="position-relative"><i class="bi bi-cart-fill"></i><span>Keranjang</span><span class="cart-badge" hidden>0</span></a>
        <a href="customer_orders.php"><i class="bi bi-box-arrow-in-right"></i><span>Pesanan</span></a>
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
        function changeQty(d) {
            const inp = document.getElementById('prodQty');
            let v = Math.max(1, parseInt(inp.value || 1) + d);
            const max = parseInt(inp.getAttribute('max') || 99);
            if (v > max) v = max;
            inp.value = v;
        }
    </script>
</body>
</html>