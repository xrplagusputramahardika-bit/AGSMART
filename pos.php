<?php
/**
 * Modern Retaillet POS Cashier Screen
 * AGSmart POS System
 */
$pageTitle = 'Kasir POS';
require_once __DIR__ . '/views/header.php';

$db = getDB();
$categories = [];
$products = [];
$customers = [];

if ($db) {
    try {
        $catStmt = $db->query("SELECT * FROM tbl_kategori ORDER BY id_kategori ASC");
        $categories = $catStmt->fetchAll();

        $prodStmt = $db->query("
            SELECT p.*, k.nama_kategori 
            FROM tbl_produk p 
            LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori 
            ORDER BY p.nama_produk ASC
        ");
        $products = $prodStmt->fetchAll();

        $custStmt = $db->query("SELECT * FROM tbl_pelanggan ORDER BY nama_pelanggan ASC");
        $customers = $custStmt->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="container-fluid px-4 py-3 flex-grow-1 d-flex flex-column">
    <div class="row g-3 flex-grow-1">
        
        <!-- KOLOM KIRI: KATALOG PRODUK & PENCARIAN -->
        <div class="col-lg-7 col-xl-8 d-flex flex-column">
            
            <!-- LAST SCANNED BANNER (Indomaret Cashier Display) -->
            <div id="lastScannedBanner" class="last-scanned-banner mb-3 d-none">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success px-2 py-1"><i class="bi bi-upc me-1"></i>BERHASIL SCAN</span>
                        <span class="fw-bold text-dark font-mono" id="scanBannerName">Indomie Goreng</span>
                        <span class="text-secondary small">(&times;<span id="scanBannerQty" class="fw-bold text-dark">1</span> @ <span id="scanBannerPrice">Rp 0</span>)</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="fw-bold text-primary-orange font-mono fs-6" id="scanBannerSubtotal">Rp 0</div>
                        <button type="button" class="btn-close btn-sm" onclick="document.getElementById('lastScannedBanner').classList.add('d-none')"></button>
                    </div>
                </div>
            </div>

            <!-- Bar Pencarian & Scanner Indomaret & Tap Member -->
            <div class="retail-card mb-3 p-3">
                <div class="row g-2 align-items-center">
                    <!-- Pencarian Nama Produk -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control bg-light border-0 text-dark" id="searchProduct" placeholder="Cari nama barang (F2)..." autocomplete="off">
                            <span class="input-group-text bg-light border-0 text-muted small d-none d-sm-inline">
                                <kbd class="bg-white text-muted border">F2</kbd>
                            </span>
                        </div>
                    </div>

                    <!-- Input Barcode Scanner (Indomaret Hardware / Auto-Listen) -->
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 text-primary-orange" title="Arahkan scanner barcode fisik ke sini">
                                <i class="bi bi-upc-scan fs-5"></i>
                            </span>
                            <input type="text" class="form-control bg-light border-0 text-dark font-mono fw-semibold" id="barcodeScanInput" placeholder="Scan Barcode (Auto-Listen)..." onchange="handleScannedBarcode(this.value); this.value='';" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary border-0 bg-light text-primary-orange" onclick="openCameraScanner()" title="Buka Scanner Kamera HP/Laptop">
                                <i class="bi bi-camera-fill me-1"></i> Kamera
                            </button>
                        </div>
                    </div>

                    <!-- Button Tap Kartu Member -->
                    <div class="col-md-3">
                        <button type="button" class="topbar-btn-orange w-100 justify-content-center py-2 shadow-sm" onclick="openTapMemberModal()">
                            <i class="bi bi-nfc fs-5"></i>
                            <span>Tap Member (F3)</span>
                        </button>
                    </div>
                </div>

                <!-- Scanner Toolbar (Status, Multiplier & Quick Simulator) -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 pb-1 border-top mt-2">
                    <!-- Scanner Status & Multiplier -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="scanner-status-pill" title="Scanner barcode hardware USB/Bluetooth aktif di seluruh layar">
                            <span class="scanner-active-dot"></span>
                            <span>Scanner Aktif</span>
                        </span>

                        <span class="text-muted small ms-1">Qty Scan:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="multiplier-pill active" onclick="setScanMultiplier(1, this)">1x</button>
                            <button type="button" class="multiplier-pill" onclick="setScanMultiplier(2, this)">2x</button>
                            <button type="button" class="multiplier-pill" onclick="setScanMultiplier(5, this)">5x</button>
                            <button type="button" class="multiplier-pill" onclick="setScanMultiplier(10, this)">10x</button>
                        </div>
                        <span class="text-muted small d-none d-lg-inline fst-italic ms-1" style="font-size: 0.73rem;">
                            (Tips: ketik <kbd class="bg-light border text-dark">5*</kbd> lalu scan)
                        </span>
                    </div>

                    <!-- Quick Barcode Shelf Toggle -->
                    <div>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none text-primary-orange p-0 fw-semibold small" onclick="toggleQuickBarcodes()">
                            <i class="bi bi-lightning-charge-fill"></i> Contoh Barcode Cepat <i class="bi bi-chevron-down" id="quickBarcodeChevron"></i>
                        </button>
                    </div>
                </div>

                <!-- QUICK BARCODE SHELF (Simulasi Barcode Indomaret 1-Klik) -->
                <div id="quickBarcodeShelf" class="pt-2 d-none">
                    <div class="p-2 bg-light rounded-3 border">
                        <div class="small fw-bold text-secondary mb-1">
                            <i class="bi bi-upc me-1"></i> Klik untuk tes scan barcode langsung (Suara Beep Kasir Indomaret):
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="quick-barcode-pill" onclick="handleScannedBarcode('BRG-006')">
                                <i class="bi bi-upc"></i> Indomie Goreng (BRG-006)
                            </button>
                            <button type="button" class="quick-barcode-pill" onclick="handleScannedBarcode('BRG-011')">
                                <i class="bi bi-upc"></i> Aqua 600ml (BRG-011)
                            </button>
                            <button type="button" class="quick-barcode-pill" onclick="handleScannedBarcode('BRG-008')">
                                <i class="bi bi-upc"></i> Chitato Sapi (BRG-008)
                            </button>
                            <button type="button" class="quick-barcode-pill" onclick="handleScannedBarcode('BRG-002')">
                                <i class="bi bi-upc"></i> Minyak Sania 2L (BRG-002)
                            </button>
                            <button type="button" class="quick-barcode-pill" onclick="handleScannedBarcode('BRG-001')">
                                <i class="bi bi-upc"></i> Beras Ramos 5kg (BRG-001)
                            </button>
                            <button type="button" class="quick-barcode-pill" onclick="handleScannedBarcode('BRG-019')">
                                <i class="bi bi-upc"></i> Sabun Lifebuoy (BRG-019)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kategori Pills -->
                <div class="d-flex align-items-center gap-2 overflow-x-auto pt-2 pb-1">
                    <button class="retail-category-pill active" data-category="all">
                        <i class="bi bi-grid-fill"></i> Semua (<?= count($products) ?>)
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="retail-category-pill" data-category="<?= htmlspecialchars($cat['nama_kategori']) ?>">
                            <i class="bi <?= htmlspecialchars($cat['ikon'] ?: 'bi-tag') ?>"></i> <?= htmlspecialchars($cat['nama_kategori']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Grid Produk -->
            <div class="flex-grow-1 overflow-y-auto" style="max-height: calc(100vh - 245px);">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5 retail-card p-4">
                        <i class="bi bi-box2 text-muted fs-1 mb-3"></i>
                        <h5 class="fw-bold text-dark mb-2">Belum Ada Data Produk</h5>
                        <p class="text-muted small mb-3">Database produk masih kosong atau tabel belum di-setup.</p>
                        <div>
                            <form action="setup.php" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="migrate">
                                <input type="hidden" name="redirect" value="index">
                                <button type="submit" class="btn topbar-btn-orange rounded-pill px-4 fw-bold">
                                    <i class="bi bi-magic me-1"></i> Setup &amp; Import Produk Contoh
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-xl-4 g-2 g-md-3" id="productsGrid">
                        <?php foreach ($products as $prod): 
                            $isOutOfStock = ($prod['stok'] <= 0);
                            $isLowStock = ($prod['stok'] > 0 && $prod['stok'] <= $prod['stok_minimal']);
                        ?>
                            <div class="col pos-product-item" 
                                 data-category="<?= htmlspecialchars($prod['nama_kategori'] ?? '') ?>" 
                                 data-name="<?= htmlspecialchars($prod['nama_produk']) ?>" 
                                 data-barcode="<?= htmlspecialchars($prod['kode_produk']) ?>">
                                
                                <div class="retail-pos-card h-100 d-flex flex-column <?= $isOutOfStock ? 'opacity-50' : '' ?>"
                                     data-id="<?= $prod['id_produk'] ?>"
                                     data-barcode="<?= htmlspecialchars($prod['kode_produk']) ?>"
                                     data-name="<?= htmlspecialchars($prod['nama_produk']) ?>"
                                     data-price="<?= $prod['harga_jual'] ?>"
                                     data-stock="<?= $prod['stok'] ?>"
                                     data-unit="<?= htmlspecialchars($prod['satuan'] ?? 'pcs') ?>"
                                     onclick="addToCartFromElement(this)">
                                    
                                    <!-- Thumbnail -->
                                    <div class="retail-pos-thumb">
                                        <?php if (!empty($prod['gambar']) && file_exists(__DIR__ . '/' . $prod['gambar'])): ?>
                                            <img src="<?= htmlspecialchars($prod['gambar']) ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>">
                                        <?php else: ?>
                                            <i class="bi bi-bag-check text-muted fs-1"></i>
                                        <?php endif; ?>
                                        
                                        <!-- Stock Badge -->
                                        <?php if ($isOutOfStock): ?>
                                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">Habis</span>
                                        <?php elseif ($isLowStock): ?>
                                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">Sisa: <?= $prod['stok'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-white text-dark shadow-sm position-absolute top-0 end-0 m-2" style="font-size: 0.7rem;">Stok: <?= $prod['stok'] ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="flex-grow-1 d-flex flex-column">
                                        <div class="small font-mono text-muted mb-1" style="font-size: 0.72rem;"><?= htmlspecialchars($prod['kode_produk']) ?></div>
                                        <h6 class="fw-bold text-dark mb-2 text-truncate" title="<?= htmlspecialchars($prod['nama_produk']) ?>" style="font-size: 0.88rem;">
                                            <?= htmlspecialchars($prod['nama_produk']) ?>
                                        </h6>
                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            <span class="fw-bold font-mono fs-6" style="color: #ff7849;"><?= formatRupiah($prod['harga_jual']) ?></span>
                                            <span class="small text-muted" style="font-size: 0.75rem;">/<?= htmlspecialchars($prod['satuan']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Alert saat pencarian kosong -->
                    <div id="emptyProductsAlert" class="text-center py-5 retail-card" style="display: none;">
                        <i class="bi bi-search text-muted fs-1 mb-2"></i>
                        <p class="text-muted mb-0">Tidak ada produk yang cocok dengan pencarian.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KOLOM KANAN: KERANJANG BELANJA & PEMBAYARAN -->
        <div class="col-lg-5 col-xl-4 d-flex flex-column">
            <div class="retail-cart-panel p-3">
                
                <!-- Cart Header & Customer Select -->
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-cart3 text-primary-orange"></i> Keranjang Belanja
                            <span class="badge bg-warning bg-opacity-10 text-dark rounded-pill small" id="cartBadgeCount">0</span>
                        </h6>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-2 py-0" onclick="openTapMemberModal()" title="Tap Kartu Member (F3)">
                                <i class="bi bi-nfc me-1"></i> Tap (F3)
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" onclick="clearCart()" title="Kosongkan (F8)">
                                <i class="bi bi-trash3 me-1"></i> Batal (F8)
                            </button>
                        </div>
                    </div>

                    <!-- WIDGET KARTU MEMBER AKTIF (Muncul saat kartu di-tap) -->
                    <div id="activeMemberWidget" class="member-active-card mb-2" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge-fill text-warning fs-5"></i>
                                <div>
                                    <span class="fw-bold text-dark small" id="widgetMemberName">Nama Member</span>
                                    <span class="badge badge-gold ms-1 small" id="widgetMemberTier">Gold</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-link text-danger p-0 border-0 small" onclick="removeActiveMember()" title="Lepas Kartu Member">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small pt-1 border-top border-warning border-opacity-25">
                            <div>
                                <span class="font-mono text-muted" id="widgetMemberCardNo">MBR-0000</span> &bull; 
                                <span class="text-dark fw-semibold" id="widgetMemberPoints">0 Poin</span>
                            </div>
                            <div>
                                Saldo: <b class="text-success font-mono" id="widgetMemberBalance">Rp 0</b>
                            </div>
                        </div>
                        <div class="small mt-1 fw-bold text-primary" id="widgetMemberDiscount">
                            <i class="bi bi-tag-fill me-1"></i> Diskon Member: 5%
                        </div>
                    </div>

                    <!-- Customer Picker Dropdown -->
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm bg-light border-0 text-dark" id="selectPelanggan">
                            <option value="">-- Pelanggan Umum (Walk-in) --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id_pelanggan'] ?>">
                                    <?= htmlspecialchars($c['nama_pelanggan']) ?> 
                                    <?= !empty($c['tipe_member']) ? '[' . $c['tipe_member'] . ']' : '' ?> 
                                    (<?= htmlspecialchars($c['kota']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#quickCustomerModal" title="Tambah Pelanggan Baru">
                            <i class="bi bi-person-plus-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Items Container -->
                <div class="retail-cart-items pe-1" id="cartItemsList">
                    <!-- Dynamic items rendered by pos.js -->
                </div>

                <!-- Empty State -->
                <div class="text-center py-5 my-auto" id="cartEmptyState">
                    <i class="bi bi-cart-x text-muted fs-1 mb-2"></i>
                    <p class="text-muted small mb-0">Keranjang masih kosong.<br>Klik produk atau scan barcode untuk menambah.</p>
                </div>

                <!-- Cart Summary & Actions -->
                <div class="border-top pt-3 mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-1 text-muted small">
                        <span>Subtotal</span>
                        <span class="fw-bold text-dark font-mono" id="cartSubtotalDisplay">Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none dropdown-toggle small" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-percent me-1"></i> Diskon Manual
                            </button>
                            <div class="dropdown-menu border shadow-lg p-3" style="width: 240px;">
                                <label class="form-label small text-muted">Diskon Persen (%)</label>
                                <input type="number" class="form-control form-control-sm mb-2" id="inputDiskonPersen" placeholder="Contoh: 10" min="0" max="100" onchange="activeDiscountPercent = parseFloat(this.value) || 0; renderCart();">
                                <label class="form-label small text-muted">Diskon Nominal (Rp)</label>
                                <input type="number" class="form-control form-control-sm" id="inputDiskonNominal" placeholder="Contoh: 5000" min="0" onchange="activeDiscountFixed = parseFloat(this.value) || 0; renderCart();">
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="switchPpn" onchange="isPpnActive = this.checked; renderCart();">
                            <label class="form-check-label small text-muted" for="switchPpn">PPN 11%</label>
                        </div>
                    </div>

                    <!-- Grand Total Display -->
                    <div class="bg-light rounded-3 p-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small d-block">TOTAL BAYAR</span>
                                <span class="fs-3 fw-bolder font-mono" id="cartGrandTotalDisplay" style="color: #ff7849;">Rp 0</span>
                            </div>
                            <i class="bi bi-wallet2 text-muted fs-2"></i>
                        </div>
                    </div>

                    <!-- Checkout Button (Orange) -->
                    <button type="button" class="btn topbar-btn-orange btn-lg w-100 justify-content-center py-2 fw-bold shadow-sm" id="btnPayCart" onclick="openPaymentModal()" disabled>
                        <span>BAYAR (F4)</span>
                        <i class="bi bi-arrow-right-circle-fill"></i>
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ========================================================
     MODAL TAP KARTU MEMBER (RFID / NFC / BARCODE)
======================================================== -->
<div class="modal fade" id="modalTapMember" tabindex="-1" aria-labelledby="modalTapMemberLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-2xl">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalTapMemberLabel">
                    <i class="bi bi-nfc text-primary-orange fs-4"></i> Tempelkan Kartu Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <!-- RFID Contactless Wave Animation -->
                <div class="rfid-scan-container mb-3">
                    <div class="rfid-circle"></div>
                    <div class="rfid-circle"></div>
                    <div class="rfid-circle"></div>
                    <div class="rfid-icon-center">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-1">Silakan Tap Kartu Member</h5>
                <p class="text-muted small mb-3">Tempelkan kartu RFID/NFC pada sensor reader USB atau scan barcode kartu member.</p>

                <!-- Input Tap Card -->
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light border text-muted"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" class="form-control form-control-lg font-mono text-center fw-bold" id="inputTapCard" placeholder="Scan / Tempel Kartu di sini..." autocomplete="off">
                    <button class="btn topbar-btn-orange px-3 fw-bold" type="button" onclick="handleTapCard()">Cek Kartu</button>
                </div>

                <!-- Quick Test Demo Buttons -->
                <div class="border-top pt-3">
                    <span class="text-muted small d-block mb-2">Simulasi Coba Tap Kartu Contoh:</span>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" onclick="handleTapCard('MBR-88012301')">
                            💳 Andi (Platinum - 10%)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="handleTapCard('MBR-88012302')">
                            💳 Budi (Gold - 5%)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="handleTapCard('MBR-88012303')">
                            💳 Citra (Silver - 2%)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL PEMBAYARAN (With Saldo Member option)
======================================================== -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-2xl">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="paymentModalLabel">
                    <i class="bi bi-credit-card-2-front-fill text-primary-orange"></i> Selesaikan Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Total Display -->
                <div class="text-center mb-4 bg-light rounded-3 p-3 border">
                    <span class="text-muted small">Total Tagihan</span>
                    <h2 class="fw-bolder font-mono mb-0" id="modalGrandTotal" style="color: #ff7849;">Rp 0</h2>
                </div>

                <!-- Metode Pembayaran -->
                <label class="form-label fw-semibold text-muted small mb-2">Pilih Metode Pembayaran</label>
                <div class="row g-2 mb-3">
                    <div class="col-3">
                        <input type="radio" class="btn-check" name="paymentMethod" id="payTunai" value="Tunai" checked>
                        <label class="btn btn-outline-secondary w-100 py-2 rounded-3 small fw-bold" for="payTunai">
                            <i class="bi bi-cash-stack d-block fs-5 mb-1 text-success"></i> Tunai
                        </label>
                    </div>
                    <div class="col-3">
                        <input type="radio" class="btn-check" name="paymentMethod" id="payQRIS" value="QRIS">
                        <label class="btn btn-outline-secondary w-100 py-2 rounded-3 small fw-bold" for="payQRIS">
                            <i class="bi bi-qr-code-scan d-block fs-5 mb-1 text-primary"></i> QRIS
                        </label>
                    </div>
                    <div class="col-3">
                        <input type="radio" class="btn-check" name="paymentMethod" id="payTransfer" value="Transfer Bank">
                        <label class="btn btn-outline-secondary w-100 py-2 rounded-3 small fw-bold" for="payTransfer">
                            <i class="bi bi-bank d-block fs-5 mb-1 text-warning"></i> Transfer
                        </label>
                    </div>
                    <div class="col-3">
                        <input type="radio" class="btn-check" name="paymentMethod" id="payDebit" value="Debit">
                        <label class="btn btn-outline-secondary w-100 py-2 rounded-3 small fw-bold" for="payDebit">
                            <i class="bi bi-credit-card d-block fs-5 mb-1 text-info"></i> Debit
                        </label>
                    </div>
                    <!-- Option Saldo Member -->
                    <div class="col-12" id="colPaySaldoMember" style="display: none;">
                        <input type="radio" class="btn-check" name="paymentMethod" id="paySaldoMember" value="Saldo Member">
                        <label class="btn btn-outline-warning w-100 py-2 rounded-3 fw-bold d-flex align-items-center justify-content-between px-3" for="paySaldoMember">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-nfc fs-4 text-warning"></i>
                                <div class="text-start">
                                    <div class="small fw-bold text-dark">Bayar Pakai Saldo Kartu Member</div>
                                    <span class="small text-muted">Saldo Tersedia: <b id="textSaldoMemberTersedia" class="text-success font-mono">Rp 0</b></span>
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark">Tap Saldo</span>
                        </label>
                    </div>
                </div>

                <!-- QRIS Visual Simulation -->
                <div id="qrisContainer" class="text-center p-3 bg-white text-dark rounded-3 mb-3 border" style="display: none;">
                    <span class="badge bg-danger mb-2">QRIS NASIONAL</span>
                    <div class="p-2 border border-2 border-dark d-inline-block rounded bg-white">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=AGSMART-PAYMENT" alt="QRIS Code" class="img-fluid" style="width: 150px; height: 150px;">
                    </div>
                    <p class="small text-muted mb-0 mt-1">Scan melalui BCA, GoPay, OVO, Dana, ShopeePay</p>
                </div>

                <!-- Cash Section -->
                <div id="cashSection">
                    <label class="form-label fw-semibold text-muted small mb-1">Nominal Diterima (Rp)</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-light fw-bold font-mono">Rp</span>
                        <input type="number" class="form-control form-control-lg font-mono fw-bold" id="nominalBayar" placeholder="0">
                    </div>

                    <!-- Quick Cash Buttons -->
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setQuickCash('pas')">Uang Pas</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setQuickCash(20000)">20.000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setQuickCash(50000)">50.000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setQuickCash(100000)">100.000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="setQuickCash(200000)">200.000</button>
                    </div>

                    <!-- Kembalian Display -->
                    <div class="bg-light rounded-3 p-3 mb-3 d-flex justify-content-between align-items-center border">
                        <span class="text-muted small">Kembalian:</span>
                        <span id="kembalianDisplay" class="fw-bold font-mono text-success fs-5">Rp 0</span>
                    </div>
                </div>

                <!-- Catatan Transaksi -->
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" id="transactionNote" placeholder="Catatan tambahan (opsional)...">
                </div>
            </div>

            <div class="modal-footer border-top p-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn topbar-btn-orange rounded-pill px-4 fw-bold shadow-sm" id="btnSubmitTransaction" onclick="submitTransaction()">
                    <i class="bi bi-printer-fill me-1"></i> Simpan &amp; Cetak Struk
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QUICK TAMBAH PELANGGAN -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 shadow-xl border-0">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus text-primary-orange me-1"></i> Pelanggan Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="pelanggan.php" method="POST">
                <input type="hidden" name="action" value="quick_add">
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small text-muted">Nama Pelanggan *</label>
                        <input type="text" name="nama_pelanggan" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Kota / Daerah</label>
                        <input type="text" name="kota" class="form-control form-control-sm" placeholder="Contoh: Jakarta">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">No. WhatsApp / Telepon</label>
                        <input type="text" name="telepon" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Nomor Kartu Member (Tap/Ketik)</label>
                        <input type="text" name="no_kartu" class="form-control form-control-sm font-mono" placeholder="MBR-xxxx atau scan RFID">
                    </div>
                </div>
                <div class="modal-footer border-top p-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm topbar-btn-orange rounded-pill px-3">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Scanner Barcode Kamera (Indomaret Live Mode) -->
<div class="modal fade" id="cameraScannerModal" tabindex="-1" aria-labelledby="cameraScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="cameraScannerModalLabel">
                    <i class="bi bi-camera-video text-warning"></i> Scanner Barcode Kamera (Indomaret)
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" onclick="stopCameraScanner()"></button>
            </div>
            <div class="modal-body p-0 bg-black text-center position-relative" style="min-height: 280px; max-height: 380px; overflow: hidden;">
                <video id="cameraVideo" playsinline autoplay muted style="width: 100%; height: 320px; object-fit: cover;"></video>
                
                <!-- Viewfinder & Red Laser -->
                <div class="scanner-viewfinder">
                    <div class="scanner-laser"></div>
                </div>

                <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3 text-white small px-3 py-1 bg-dark bg-opacity-75 rounded-pill shadow" id="cameraStatusText">
                    <i class="bi bi-upc-scan me-1"></i> Arahkan barcode ke dalam kotak
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 d-flex justify-content-between">
                <span class="small text-muted">Mendukung EAN-13, Code 128, QR Code</span>
                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal" onclick="stopCameraScanner()">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Print Modal Inclusion -->
<?php require_once __DIR__ . '/views/receipt_modal.php'; ?>

<!-- Inject Products to JS for instant barcode match -->
<script>
    const allProducts = <?= json_encode($products) ?>;
</script>
<script src="assets/js/pos.js"></script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
