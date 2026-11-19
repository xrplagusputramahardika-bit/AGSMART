<?php
/**
 * Customer Checkout - AGSmart Storefront (Shopee Style Payment)
 */
$pageTitle = 'Checkout Pembayaran';
require_once __DIR__ . '/config/helper.php';
requireCustomer();
$db = getDB();
$pengaturan = getPengaturan();
$cust = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM tbl_pelanggan WHERE id_pelanggan = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $cust = $stmt->fetch();
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
        <a href="customer_cart.php" class="btn-icon" style="margin-left:0;"><i class="bi bi-arrow-left"></i></a>
        <span class="customer-brand">Pembayaran</span>
        <div class="customer-actions"></div>
    </div>
    <div class="customer-section pt-3"><h2>Detail Pembayaran</h2></div>
    <div class="container-fluid px-3">
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="cart-summary prod-sticky">
                    <h3><span>Ringkasan</span></h3>
                    <div id="checkoutItems"></div>
                    <div class="cart-row"><span>Subtotal</span><span id="checkoutSubtotal">Rp 0</span></div>
                    <div class="cart-row"><span>Diskon Member (2%)</span><span class="discount" id="checkoutDiskon">- Rp 0</span></div>
                    <div class="cart-row"><span>Ongkir</span><span id="checkoutOngkir">Rp 9.000</span></div>
                    <div class="cart-row total"><span>Total Bayar</span><span id="checkoutTotal">Rp 0</span></div>
                    <input type="hidden" id="checkoutTotalInput" value="0">
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-wallet2 text-primary-orange me-2"></i>Pilih Metode Pembayaran</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="payment-methods">
                            <div class="payment-option" data-method="transfer">
                                <input type="radio" name="payment" value="transfer" id="pmTransfer" checked>
                                <div class="pm-icon"><i class="bi bi-bank"></i></div>
                                <div class="pm-info">
                                    <p class="pm-title mb-0">Transfer Bank</p>
                                    <p class="pm-desc mb-0">Transfer ke rekening toko lalu unggah bukti transfer.</p>
                                </div>
                                <span class="pm-arrow">›</span>
                            </div>
                            <div class="payment-option" data-method="qris">
                                <input type="radio" name="payment" value="qris" id="pmQris">
                                <div class="pm-icon"><i class="bi bi-qr-code"></i></div>
                                <div class="pm-info">
                                    <p class="pm-title mb-0">QRIS</p>
                                    <p class="pm-desc mb-0">Scan QRIS lalu unggah bukti pembayaran.</p>
                                </div>
                                <span class="pm-arrow">›</span>
                            </div>
                            <div class="payment-option" data-method="ewallet">
                                <input type="radio" name="payment" value="ewallet" id="pmEwallet">
                                <div class="pm-icon"><i class="bi bi-wallet"></i></div>
                                <div class="pm-info">
                                    <p class="pm-title mb-0">E-Wallet / Saldo Member</p>
                                    <p class="pm-desc mb-0">Bayar dengan saldo e-wallet kartu member Anda.</p>
                                </div>
                                <span class="pm-arrow">›</span>
                            </div>
                        </div>
                        <div id="paymentExtra" class="payment-extra" hidden>
                            <div class="d-flex align-items-start mb-2">
                                <i class="bi bi-info-circle text-primary-orange mt-1 me-2"></i>
                                <div id="methodInfo" class="small"></div>
                            </div>
                            <div id="proofUpload" class="proof-upload" hidden>
                                <label class="d-block mb-2">Bukti Pembayaran</label>
                                <input type="file" id="buktiTransfer" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-control form-control-sm">
                                <div class="proof-hint">Format JPG, PNG, WEBP atau PDF (maks. 5 MB)</div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary-wide w-100" id="btnCheckout">Pesan Sekarang &amp; Bayar</button>
                            <input type="hidden" id="checkoutMethod" value="transfer">
                            <input type="hidden" id="checkoutItemsJson">
                            <form id="checkoutForm" method="POST" action="api/customer_request.php" enctype="multipart/form-data" hidden>
                                <input type="hidden" name="action" value="order">
                                <input type="hidden" name="method" id="checkoutMethodInput">
                                <input type="hidden" name="items" id="checkoutItemsInput">
                                <input type="file" name="bukti_transfer" id="checkoutBuktiInput">
                            </form>
                        </div>
                    </div>
                </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            renderCheckoutSummary();
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.addEventListener('click', () => {
                    const radio = opt.querySelector('input[type=radio]');
                    if (radio) radio.checked = true;
                    selectPayment(opt.dataset.method);
                    document.getElementById('checkoutMethod').value = opt.dataset.method;
                    document.getElementById('checkoutMethodInput').value = opt.dataset.method;
                });
            });
            const form = document.getElementById('checkoutForm');
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnCheckout');
                if (!selectedPayment) { showToast('Pilih metode pembayaran', 'warning'); return; }
                const cart = JSON.parse(localStorage.getItem('agsmart_cart') || '[]');
                if (cart.length === 0) { showToast('Keranjang kosong', 'warning'); return; }
                btn.disabled = true; btn.textContent = 'Memproses...';
                const formData = new FormData();
                formData.append('action', 'order');
                formData.append('method', selectedPayment);
                formData.append('items', JSON.stringify(cart));
                const file = document.getElementById('buktiTransfer').files[0];
                if (file) formData.append('bukti_transfer', file);
                try {
                    const res = await fetch('api/customer_request.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    if (json.status) {
                        localStorage.removeItem('agsmart_cart');
                        showToast(json.message, 'success');
                        setTimeout(() => { location.href = 'customer_orders.php'; }, 800);
                    } else {
                        showToast(json.message, 'danger');
                        btn.disabled = false; btn.textContent = 'Pesan Sekarang & Bayar';
                    }
                } catch (err) {
                    showToast('Terjadi kesalahan jaringan', 'danger');
                    btn.disabled = false; btn.textContent = 'Pesan Sekarang & Bayar';
                }
            });
        });
        function renderCheckoutSummary() {
            const cart = JSON.parse(localStorage.getItem('agsmart_cart') || '[]');
            const container = document.getElementById('checkoutItems');
            if (!container) return;
            container.innerHTML = '';
            let subtotal = 0;
            if (cart.length === 0) {
                container.innerHTML = '<div class="text-muted small text-center py-3">Keranjang kosong</div>';
                document.getElementById('checkoutSubtotal').textContent = 'Rp 0';
                document.getElementById('checkoutDiskon').textContent = '- Rp 0';
                document.getElementById('checkoutOngkir').textContent = 'Rp 9.000';
                document.getElementById('checkoutTotal').textContent = 'Rp 0';
                document.getElementById('checkoutTotalInput').value = 0;
                return;
            }
            cart.forEach(item => {
                subtotal += item.harga * item.qty;
                const el = document.createElement('div');
                el.className = 'd-flex align-items-center gap-2 mb-2';
                el.innerHTML = '<img src="assets/img/products/' + item.id + '.jpg" onerror="this.src=\'assets/img/no-image.svg\'" style="width:48px;height:48px;object-fit:cover;border-radius:8px;"><div class="flex-grow-1"><div class="small fw-bold">' + item.nama + '</div><div class="text-muted small">' + item.qty + ' x ' + item.harga.toLocaleString('id-ID') + '</div></div><div class="fw-bold small text-dark">' + (item.harga * item.qty).toLocaleString('id-ID') + '</div>';
                container.appendChild(el);
            });
            const diskon = Math.floor(subtotal * 2 / 100);
            const ongkir = subtotal >= 200000 ? 0 : 9000;
            const total = subtotal - diskon + ongkir;
            document.getElementById('checkoutSubtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('checkoutDiskon').textContent = '- Rp ' + diskon.toLocaleString('id-ID');
            document.getElementById('checkoutOngkir').textContent = ongkir === 0 ? 'Gratis' : 'Rp ' + ongkir.toLocaleString('id-ID');
            document.getElementById('checkoutTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('checkoutTotalInput').value = total;
        }
    </script>
</body>
</html>