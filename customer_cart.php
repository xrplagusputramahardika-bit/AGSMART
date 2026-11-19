<?php
/**
 * Customer Cart - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Keranjang Belanja';
require_once __DIR__ . '/config/helper.php';
requireCustomer();
$db = getDB();
$cartItems = []; $subtotal = 0;
if ($db) {
    try {
        $cart = json_decode(file_get_contents('php://input'), true) ?: [];
    } catch (Exception $e) {}
}
// Cart data comes from localStorage via JS; no server-side cart needed for display
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
        <a href="customer_catalog.php" class="btn-icon" style="margin-left:0;"><i class="bi bi-arrow-left"></i></a>
        <span class="customer-brand">Keranjang</span>
        <div class="customer-actions">
            <a href="#" class="btn-icon" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas"><i class="bi bi-list"></i></a>
        </div>
    </div>
    <div class="customer-section pt-3"><h2>Keranjang Belanja</h2></div>
    <div class="container-fluid px-3">
        <div id="cartPage">
            <div id="cartEmpty" class="empty-state" hidden>
                <i class="bi bi-cart-x"></i>
                <h3>Keranjang Anda kosong</h3>
            </div>
            <div id="cartItems"></div>
            <div id="cartSummarySection" class="cart-summary" hidden>
                <h3><span>Total</span>Rp 0</h3>
                <div class="cart-row"><span>Subtotal</span><span id="cartSubtotal">Rp 0</span></div>
                <div class="cart-row"><span>Diskon Member (2%)</span><span class="discount" id="cartDiskon">- Rp 0</span></div>
                <div class="cart-row"><span>Ongkir</span><span id="cartOngkir">Rp 9.000</span></div>
                <div class="cart-row total"><span>Total Bayar</span><span id="cartTotal">Rp 0</span></div>
                <div class="mt-3">
                    <a href="customer_checkout.php" class="btn btn-primary-wide w-100">Lanjutkan ke Pembayaran</a>
                </div>
            </div>
        </div>
    </div>
    <nav class="customer-bottomnav">
        <a href="customer_dashboard.php"><i class="bi bi-house-door"></i><span>Beranda</span></a>
        <a href="customer_catalog.php"><i class="bi bi-grid"></i><span>Katalog</span></a>
        <a href="customer_cart.php" class="active position-relative"><i class="bi bi-cart-fill"></i><span>Keranjang</span><span class="cart-badge" hidden>0</span></a>
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
</body>
</html>