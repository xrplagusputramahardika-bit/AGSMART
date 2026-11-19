/**
 * AGSmart POS - Cashier POS Interactive Engine + Member Card Tap System
 */

// Cart & Member State
let cart = [];
let activeMember = null; // Stored member card details
let currentCategory = 'all';
let searchQuery = '';
let activeDiscountPercent = 0;
let activeDiscountFixed = 0;
let isPpnActive = false;
let ppnRate = 0.11; // 11% PPN standard

// Web Audio API Beep & Chime Generator
let audioCtx = null;
function getAudioContext() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
}

function playBeep(freq = 900, type = 'sine', duration = 0.08) {
    try {
        const ctx = getAudioContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = type;
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + duration);
    } catch (e) {}
}

// Indomaret Supermarket Scanner Beep (2400 Hz Sine Wave, 65ms crisp click)
function playIndomaretBeep() {
    try {
        const ctx = getAudioContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(2400, ctx.currentTime);
        gain.gain.setValueAtTime(0.35, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.065);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.065);
    } catch (e) {}
}

// Indomaret Error Buzz (Low double tone)
function playIndomaretError() {
    try {
        const ctx = getAudioContext();
        [0, 100].forEach(delay => {
            setTimeout(() => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(200, ctx.currentTime);
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.08);
            }, delay);
        });
    } catch (e) {}
}

// Success Sound (double ding)
function playSuccessSound() {
    playBeep(880, 'sine', 0.1);
    setTimeout(() => playBeep(1320, 'sine', 0.15), 100);
}

// Error Sound (low buzz)
function playErrorSound() {
    playIndomaretError();
}

// RFID / NFC Member Tap Chime (Tri-tone chord C-E-G)
function playMemberChime() {
    try {
        const ctx = getAudioContext();
        const notes = [523.25, 659.25, 783.99]; // C5, E5, G5
        notes.forEach((freq, idx) => {
            setTimeout(() => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            }, idx * 75);
        });
    } catch (e) {}
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    initKeyboardShortcuts();
    initBarcodeScanner();
    initEventListeners();
    renderCart();
});

// Setup Keyboard Shortcuts
function initKeyboardShortcuts() {
    window.addEventListener('keydown', (e) => {
        // F2: Focus Search
        if (e.key === 'F2') {
            e.preventDefault();
            const searchInput = document.getElementById('searchProduct');
            if (searchInput) searchInput.focus();
        }
        // F3: Tap Kartu Member
        else if (e.key === 'F3') {
            e.preventDefault();
            openTapMemberModal();
        }
        // F4: Open Payment
        else if (e.key === 'F4') {
            e.preventDefault();
            if (cart.length > 0) {
                openPaymentModal();
            } else {
                showToast('Keranjang masih kosong!', 'warning');
            }
        }
        // F8: Clear Cart
        else if (e.key === 'F8') {
            e.preventDefault();
            clearCart();
        }
    });
}

// ========================================================
// INDOMARET BARCODE SCANNER ENGINE
// ========================================================
let barcodeBuffer = '';
let lastKeyTime = Date.now();
let currentScanMultiplier = 1;
let cameraStream = null;
let cameraScanning = false;
let barcodeDetector = null;

// Multiplier Control (e.g. 5x per scan or 5*BRG-006)
function setScanMultiplier(multiplier, btn) {
    currentScanMultiplier = parseInt(multiplier) || 1;
    document.querySelectorAll('.multiplier-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    playBeep(1100, 'sine', 0.05);
    showToast(`Mode Quantity Scan: ${currentScanMultiplier}x`, 'info');
}

// Toggle Quick Barcode Shelf
function toggleQuickBarcodes() {
    const shelf = document.getElementById('quickBarcodeShelf');
    const icon = document.getElementById('quickBarcodeChevron');
    if (!shelf) return;
    if (shelf.classList.contains('d-none')) {
        shelf.classList.remove('d-none');
        if (icon) icon.className = 'bi bi-chevron-up';
    } else {
        shelf.classList.add('d-none');
        if (icon) icon.className = 'bi bi-chevron-down';
    }
}

// Global Hardware Barcode Scanner Listener (Auto-detects USB/Bluetooth scanner)
function initBarcodeScanner() {
    window.addEventListener('keydown', (e) => {
        const currentTime = Date.now();

        // Skip if typing in general inputs EXCEPT barcodeScanInput, searchProduct, or inputTapCard
        if (e.target.tagName === 'INPUT' && 
            e.target.id !== 'searchProduct' && 
            e.target.id !== 'barcodeScanInput' &&
            e.target.id !== 'inputTapCard') {
            return;
        }

        // Hardware scanner keystroke burst is fast (< 75ms per char)
        if (currentTime - lastKeyTime > 120) {
            barcodeBuffer = '';
        }
        lastKeyTime = currentTime;

        if (e.key === 'Enter') {
            if (barcodeBuffer.length >= 2) {
                e.preventDefault();
                const scanned = barcodeBuffer.trim();
                barcodeBuffer = '';

                // If modal tap member is open, tap member card
                const modalTapEl = document.getElementById('modalTapMember');
                if (modalTapEl && modalTapEl.classList.contains('show')) {
                    handleTapCard(scanned);
                    return;
                }

                // If prefix is MBR- or CARD-, treat as Member Card Tap!
                if (scanned.toUpperCase().startsWith('MBR-') || scanned.toUpperCase().startsWith('CARD-')) {
                    handleTapCard(scanned);
                    return;
                }

                // Otherwise treat as Product Barcode
                handleScannedBarcode(scanned);
            }
        } else if (e.key.length === 1) {
            barcodeBuffer += e.key;
        }
    });

    // Auto focus barcode input on load
    const scanInput = document.getElementById('barcodeScanInput');
    if (scanInput) {
        setTimeout(() => scanInput.focus(), 300);
    }
}

// Core Barcode Scanning Handler (Like Indomaret)
function handleScannedBarcode(rawBarcode) {
    if (!rawBarcode) return;
    let barcode = String(rawBarcode).trim();
    if (!barcode) return;

    // Check Multiplier Syntax: e.g. "5*BRG-006" or "10*8990000000006"
    let multiplier = currentScanMultiplier;
    if (barcode.includes('*')) {
        const parts = barcode.split('*');
        const parsedMultiplier = parseInt(parts[0]);
        if (!isNaN(parsedMultiplier) && parsedMultiplier > 0) {
            multiplier = parsedMultiplier;
            barcode = parts.slice(1).join('*').trim();
        }
    }

    // Check Member Card Prefix
    if (barcode.toUpperCase().startsWith('MBR-') || barcode.toUpperCase().startsWith('CARD-')) {
        handleTapCard(barcode);
        return;
    }

    const cleanCode = barcode.toUpperCase();
    
    // Find matching product in allProducts (checks kode_produk, barcode, or id)
    let match = allProducts.find(p => {
        const code = String(p.kode_produk || '').toUpperCase();
        const bcode = String(p.barcode || '').toUpperCase();
        const id = String(p.id_produk);
        return code === cleanCode || bcode === cleanCode || id === cleanCode;
    });

    // Fallback: check DOM elements
    if (!match) {
        const card = document.querySelector(`.retail-pos-card[data-barcode="${cleanCode}"], .pos-product-item[data-barcode="${cleanCode}"]`);
        if (card) {
            const id = card.getAttribute('data-id');
            match = allProducts.find(p => p.id_produk == id);
        }
    }

    if (match) {
        if (parseInt(match.stok) <= 0) {
            playIndomaretError();
            showToast(`Stok ${match.nama_produk} HABIS! Tidak dapat ditambahkan.`, 'danger');
            return;
        }

        // Add to cart with multiplier and Indomaret scanner beep
        addToCart(match, multiplier, true);
        showLastScannedBanner(match, multiplier);

        // Reset multiplier to 1 after scan
        if (currentScanMultiplier !== 1) {
            setScanMultiplier(1, document.querySelector('.multiplier-pill:first-child'));
        }

        // Auto refocus barcode input for next scan
        const scanInput = document.getElementById('barcodeScanInput');
        if (scanInput) {
            scanInput.value = '';
            scanInput.focus();
        }
    } else {
        playIndomaretError();
        showToast(`Barcode '${barcode}' TIDAK DITEMUKAN di database kasir!`, 'danger');
        const scanInput = document.getElementById('barcodeScanInput');
        if (scanInput) {
            scanInput.value = '';
            scanInput.focus();
        }
    }
}

// Show Last Scanned Item Banner (Indomaret Cashier Display)
function showLastScannedBanner(product, qty) {
    const banner = document.getElementById('lastScannedBanner');
    if (!banner) return;
    document.getElementById('scanBannerName').textContent = product.nama_produk;
    document.getElementById('scanBannerQty').textContent = qty;
    document.getElementById('scanBannerPrice').textContent = formatRupiahJs(product.harga_jual);
    document.getElementById('scanBannerSubtotal').textContent = formatRupiahJs(product.harga_jual * qty);
    banner.classList.remove('d-none');
}

// Camera Barcode Scanner (Kamera HP / Laptop)
function openCameraScanner() {
    const modalEl = document.getElementById('cameraScannerModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    startCameraScanner();
}

async function startCameraScanner() {
    const video = document.getElementById('cameraVideo');
    const statusText = document.getElementById('cameraStatusText');
    if (!video) return;

    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
        });
        video.srcObject = cameraStream;
        await video.play();

        // Check BarcodeDetector support
        if ('BarcodeDetector' in window) {
            try {
                barcodeDetector = new BarcodeDetector({
                    formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code', 'upc_a', 'upc_e']
                });
            } catch (e) {
                barcodeDetector = null;
            }
        }

        cameraScanning = true;
        scanCameraFrame();
    } catch (e) {
        console.error('Camera error:', e);
        if (statusText) statusText.innerHTML = '<span class="text-danger">Kamera tidak dapat diakses atau diblokir.</span>';
    }
}

async function scanCameraFrame() {
    if (!cameraScanning) return;
    const video = document.getElementById('cameraVideo');
    if (!video || video.readyState < 2) {
        requestAnimationFrame(scanCameraFrame);
        return;
    }

    if (barcodeDetector) {
        try {
            const barcodes = await barcodeDetector.detect(video);
            if (barcodes.length > 0) {
                const detected = barcodes[0].rawValue;
                stopCameraScanner();
                const modalEl = document.getElementById('cameraScannerModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                handleScannedBarcode(detected);
                return;
            }
        } catch (e) {}
    }

    setTimeout(() => {
        if (cameraScanning) requestAnimationFrame(scanCameraFrame);
    }, 150);
}

function stopCameraScanner() {
    cameraScanning = false;
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

// Event Listeners for UI
function initEventListeners() {
    // Search input
    const searchInput = document.getElementById('searchProduct');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            filterProducts();
        });
    }

    // Category pills
    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentCategory = pill.getAttribute('data-category');
            filterProducts();
        });
    });

    // Payment input event
    const nominalBayarInput = document.getElementById('nominalBayar');
    if (nominalBayarInput) {
        nominalBayarInput.addEventListener('input', calculateKembalian);
    }

    // Payment method selector
    document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const method = e.target.value;
            const qrisContainer = document.getElementById('qrisContainer');
            const saldoMemberContainer = document.getElementById('saldoMemberContainer');
            const cashSection = document.getElementById('cashSection');

            if (qrisContainer) qrisContainer.style.display = (method === 'QRIS') ? 'block' : 'none';
            if (saldoMemberContainer) saldoMemberContainer.style.display = (method === 'Saldo Member') ? 'block' : 'none';
            if (cashSection) cashSection.style.display = (method === 'Tunai') ? 'block' : 'none';

            if (method === 'QRIS' || method === 'Saldo Member' || method === 'Transfer Bank' || method === 'Debit') {
                const grandTotal = getGrandTotal();
                if (nominalBayarInput) nominalBayarInput.value = grandTotal;
                calculateKembalian();
            }
        });
    });

    // Customer Select dropdown change
    const selectPelanggan = document.getElementById('selectPelanggan');
    if (selectPelanggan) {
        selectPelanggan.addEventListener('change', (e) => {
            const custId = e.target.value;
            if (!custId) {
                removeActiveMember(false);
            } else {
                // Fetch member details
                fetchMemberById(custId);
            }
        });
    }
}

// Open Tap Member Modal
function openTapMemberModal() {
    const modalEl = document.getElementById('modalTapMember');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    setTimeout(() => {
        const input = document.getElementById('inputTapCard');
        if (input) {
            input.value = '';
            input.focus();
        }
    }, 400);
}

// Handle Tap / Scan Member Card
async function handleTapCard(cardNumber) {
    if (!cardNumber) {
        cardNumber = document.getElementById('inputTapCard')?.value?.trim();
    }
    if (!cardNumber) {
        showToast('Nomor kartu tidak boleh kosong!', 'warning');
        return;
    }

    try {
        const response = await fetch('api/tap_member.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ card_number: cardNumber })
        });
        const result = await response.json();

        if (result.status) {
            playMemberChime();
            setActiveMember(result.data);

            // Close Tap Modal
            const modalEl = document.getElementById('modalTapMember');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            showToast(`Kartu Member ${result.data.nama_pelanggan} (${result.data.tipe_member}) aktif! Diskon ${result.data.diskon_member}% diterapkan.`, 'success');
        } else {
            playErrorSound();
            showToast(result.message || 'Kartu member tidak dikenali', 'danger');
        }
    } catch (e) {
        console.error(e);
        playErrorSound();
        showToast('Gagal menghubungi server kartu member', 'danger');
    }
}

// Set Active Member in Cart & State
function setActiveMember(member) {
    activeMember = member;

    // Sync dropdown
    const select = document.getElementById('selectPelanggan');
    if (select) {
        select.value = member.id_pelanggan;
    }

    // Auto-apply member discount percentage
    activeDiscountPercent = parseFloat(member.diskon_member) || 0;
    activeDiscountFixed = 0;

    // Display Member Active Card Widget
    const widget = document.getElementById('activeMemberWidget');
    if (widget) {
        widget.style.display = 'block';
        document.getElementById('widgetMemberName').textContent = member.nama_pelanggan;
        document.getElementById('widgetMemberCardNo').textContent = member.no_kartu;
        document.getElementById('widgetMemberPoints').textContent = member.poin + ' Poin';
        document.getElementById('widgetMemberBalance').textContent = formatRupiahJs(member.saldo);

        const badgeTier = document.getElementById('widgetMemberTier');
        if (badgeTier) {
            badgeTier.textContent = member.tipe_member;
            badgeTier.className = 'badge ' + (
                member.tipe_member === 'Platinum' ? 'badge-platinum' :
                member.tipe_member === 'Gold' ? 'badge-gold' :
                member.tipe_member === 'Silver' ? 'badge-silver' : 'badge-regular'
            );
        }

        const discountText = document.getElementById('widgetMemberDiscount');
        if (discountText) {
            discountText.textContent = `Diskon Member: ${activeDiscountPercent}%`;
        }
    }

    renderCart();
}

// Remove Active Member
function removeActiveMember(showNotification = true) {
    activeMember = null;
    activeDiscountPercent = 0;

    const select = document.getElementById('selectPelanggan');
    if (select) select.value = '';

    const widget = document.getElementById('activeMemberWidget');
    if (widget) widget.style.display = 'none';

    renderCart();
    if (showNotification) {
        showToast('Member kartu dilepas. Kembali ke Pelanggan Umum.', 'info');
    }
}

// Fetch member data when selecting from dropdown
async function fetchMemberById(idPelanggan) {
    try {
        const response = await fetch(`api/tap_member.php?code=${idPelanggan}`);
        const result = await response.json();
        if (result.status) {
            setActiveMember(result.data);
            showToast(`Member: ${result.data.nama_pelanggan} (${result.data.tipe_member})`, 'success');
        }
    } catch (e) {}
}

// Filter Products
function filterProducts() {
    const cards = document.querySelectorAll('.pos-product-item');
    let visibleCount = 0;

    cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        const name = card.getAttribute('data-name').toLowerCase();
        const barcode = card.getAttribute('data-barcode').toLowerCase();

        const matchCat = (currentCategory === 'all' || cat === currentCategory);
        const matchSearch = (!searchQuery || name.includes(searchQuery) || barcode.includes(searchQuery));

        if (matchCat && matchSearch) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const emptyAlert = document.getElementById('emptyProductsAlert');
    if (emptyAlert) {
        emptyAlert.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

// Add to Cart from DOM Element
function addToCartFromElement(el) {
    const id = parseInt(el.getAttribute('data-id'));
    const barcode = el.getAttribute('data-barcode');
    const name = el.getAttribute('data-name');
    const price = parseFloat(el.getAttribute('data-price'));
    const stock = parseInt(el.getAttribute('data-stock'));
    const unit = el.getAttribute('data-unit') || 'pcs';

    if (stock <= 0) {
        playErrorSound();
        showToast(`Stok ${name} habis!`, 'danger');
        return;
    }

    addToCart({ id_produk: id, kode_produk: barcode, nama_produk: name, harga_jual: price, stok: stock, satuan: unit });
}

// Add to Cart Object (Supports Multiplier & Indomaret Scanner)
function addToCart(product, quantityToAdd = 1, isFromScanner = false) {
    quantityToAdd = parseInt(quantityToAdd) || 1;
    const existingIndex = cart.findIndex(item => item.id_produk === product.id_produk);

    if (existingIndex > -1) {
        if (cart[existingIndex].qty + quantityToAdd > product.stok) {
            playIndomaretError();
            showToast(`Jumlah melebihi stok yang tersedia (${product.stok})!`, 'warning');
            return;
        }
        cart[existingIndex].qty += quantityToAdd;
        cart[existingIndex].subtotal = cart[existingIndex].qty * cart[existingIndex].harga_jual;
    } else {
        if (quantityToAdd > product.stok) {
            playIndomaretError();
            showToast(`Jumlah (${quantityToAdd}) melebihi stok yang tersedia (${product.stok})!`, 'warning');
            return;
        }
        cart.push({
            id_produk: product.id_produk,
            kode_produk: product.kode_produk,
            barcode: product.barcode || product.kode_produk,
            nama_produk: product.nama_produk,
            harga_jual: parseFloat(product.harga_jual),
            stok: parseInt(product.stok),
            satuan: product.satuan || 'pcs',
            qty: quantityToAdd,
            subtotal: parseFloat(product.harga_jual) * quantityToAdd
        });
    }

    if (isFromScanner) {
        playIndomaretBeep();
    } else {
        playBeep(920, 'sine', 0.08);
    }
    renderCart(product.id_produk);
}

// Update Cart Quantity
function updateQty(index, change) {
    if (!cart[index]) return;
    const item = cart[index];
    const newQty = item.qty + change;

    if (newQty <= 0) {
        removeItem(index);
        return;
    }

    if (newQty > item.stok) {
        playErrorSound();
        showToast(`Maksimal stok tersedia adalah ${item.stok}!`, 'warning');
        return;
    }

    item.qty = newQty;
    item.subtotal = item.qty * item.harga_jual;
    renderCart();
}

// Set Exact Cart Quantity from Input
function setQty(index, inputVal) {
    let val = parseInt(inputVal);
    if (isNaN(val) || val <= 0) {
        val = 1;
    }
    const item = cart[index];
    if (!item) return;

    if (val > item.stok) {
        playErrorSound();
        showToast(`Stok tidak mencukupi! Hanya ada ${item.stok}`, 'warning');
        item.qty = item.stok;
    } else {
        item.qty = val;
    }

    item.subtotal = item.qty * item.harga_jual;
    renderCart();
}

// Remove Single Item from Cart
function removeItem(index) {
    if (cart[index]) {
        cart.splice(index, 1);
        renderCart();
    }
}

// Clear Entire Cart
function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Kosongkan semua barang di keranjang?')) {
        cart = [];
        renderCart();
        showToast('Keranjang telah dikosongkan', 'info');
    }
}

// Calculations
function getCartSubtotal() {
    return cart.reduce((acc, item) => acc + item.subtotal, 0);
}

function getGrandTotal() {
    const subtotal = getCartSubtotal();
    let discount = 0;
    if (activeDiscountPercent > 0) {
        discount = subtotal * (activeDiscountPercent / 100);
    } else if (activeDiscountFixed > 0) {
        discount = activeDiscountFixed;
    }
    const taxableAmount = Math.max(0, subtotal - discount);
    const tax = isPpnActive ? (taxableAmount * ppnRate) : 0;
    return Math.max(0, Math.round(taxableAmount + tax));
}

// Render Cart UI (Highlights Scanned Product)
function renderCart(highlightedId = null) {
    const container = document.getElementById('cartItemsList');
    const emptyState = document.getElementById('cartEmptyState');
    const badgeCount = document.getElementById('cartBadgeCount');
    const subtotalEl = document.getElementById('cartSubtotalDisplay');
    const grandTotalEl = document.getElementById('cartGrandTotalDisplay');
    const payBtn = document.getElementById('btnPayCart');

    if (!container) return;

    const totalItems = cart.reduce((acc, item) => acc + item.qty, 0);
    if (badgeCount) badgeCount.textContent = totalItems;

    if (cart.length === 0) {
        container.innerHTML = '';
        if (emptyState) emptyState.style.display = 'block';
        if (subtotalEl) subtotalEl.textContent = 'Rp 0';
        if (grandTotalEl) grandTotalEl.textContent = 'Rp 0';
        if (payBtn) payBtn.disabled = true;
        return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (payBtn) payBtn.disabled = false;

    let html = '';
    cart.forEach((item, index) => {
        const isScanned = (highlightedId && highlightedId === item.id_produk);
        html += `
            <div class="pos-cart-item ${isScanned ? 'cart-item-scanned' : ''}" id="cartItemRow_${item.id_produk}">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="pe-2">
                        <div class="fw-bold text-white small text-truncate" style="max-width: 180px;">
                            ${item.nama_produk}
                            ${isScanned ? '<span class="badge bg-success ms-1" style="font-size:0.65rem;">+Scan</span>' : ''}
                        </div>
                        <div class="text-slate-400 small">${formatRupiahJs(item.harga_jual)} / ${item.satuan}</div>
                    </div>
                    <button class="btn btn-link text-danger p-0 border-0" onclick="removeItem(${index})" title="Hapus">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-slate-700 text-light qty-btn" onclick="updateQty(${index}, -1)">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="qty-input" value="${item.qty}" min="1" max="${item.stok}" onchange="setQty(${index}, this.value)">
                        <button class="btn btn-sm btn-slate-700 text-light qty-btn" onclick="updateQty(${index}, 1)">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="fw-bold text-primary font-mono">${formatRupiahJs(item.subtotal)}</div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    const subtotal = getCartSubtotal();
    const grandTotal = getGrandTotal();

    if (subtotalEl) subtotalEl.textContent = formatRupiahJs(subtotal);
    if (grandTotalEl) grandTotalEl.textContent = formatRupiahJs(grandTotal);
}

// Payment Modal Handlers
function openPaymentModal() {
    if (cart.length === 0) {
        showToast('Keranjang masih kosong!', 'warning');
        return;
    }

    const grandTotal = getGrandTotal();
    const modalTotalEl = document.getElementById('modalGrandTotal');
    const nominalBayarInput = document.getElementById('nominalBayar');

    if (modalTotalEl) modalTotalEl.textContent = formatRupiahJs(grandTotal);
    if (nominalBayarInput) {
        nominalBayarInput.value = grandTotal;
        setTimeout(() => nominalBayarInput.select(), 300);
    }

    // Configure Saldo Member payment option if member is active
    const optionSaldoMember = document.getElementById('colPaySaldoMember');
    const textSaldoTersedia = document.getElementById('textSaldoMemberTersedia');
    if (optionSaldoMember && textSaldoTersedia) {
        if (activeMember) {
            optionSaldoMember.style.display = 'block';
            textSaldoTersedia.textContent = formatRupiahJs(activeMember.saldo);
            const radioSaldo = document.getElementById('paySaldoMember');
            if (activeMember.saldo < grandTotal) {
                if (radioSaldo) radioSaldo.disabled = true;
                textSaldoTersedia.textContent += ' (Kurang)';
            } else {
                if (radioSaldo) radioSaldo.disabled = false;
            }
        } else {
            optionSaldoMember.style.display = 'none';
        }
    }
    
    calculateKembalian();

    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
}

// Set Quick Cash Amount
function setQuickCash(amount) {
    const grandTotal = getGrandTotal();
    const nominalInput = document.getElementById('nominalBayar');
    if (!nominalInput) return;

    if (amount === 'pas') {
        nominalInput.value = grandTotal;
    } else {
        nominalInput.value = amount;
    }
    calculateKembalian();
    playBeep(1100, 'sine', 0.05);
}

// Calculate Kembalian
function calculateKembalian() {
    const grandTotal = getGrandTotal();
    const nominalInput = document.getElementById('nominalBayar');
    const kembalianEl = document.getElementById('kembalianDisplay');
    const btnSubmit = document.getElementById('btnSubmitTransaction');

    const bayar = parseFloat(nominalInput?.value || 0);
    const kembalian = bayar - grandTotal;

    if (kembalianEl) {
        if (kembalian >= 0) {
            kembalianEl.className = 'fw-bold text-success font-mono fs-5';
            kembalianEl.textContent = formatRupiahJs(kembalian);
            if (btnSubmit) btnSubmit.disabled = false;
        } else {
            kembalianEl.className = 'fw-bold text-danger font-mono fs-5';
            kembalianEl.textContent = 'Kurang ' + formatRupiahJs(Math.abs(kembalian));
            const method = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'Tunai';
            if (method === 'Tunai' && btnSubmit) {
                btnSubmit.disabled = true;
            } else if (btnSubmit) {
                btnSubmit.disabled = false;
            }
        }
    }
}

// Process and Submit Transaction
async function submitTransaction() {
    if (cart.length === 0) return;

    const grandTotal = getGrandTotal();
    const subtotal = getCartSubtotal();
    const nominalBayar = parseFloat(document.getElementById('nominalBayar')?.value || 0);
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'Tunai';
    const customerSelect = document.getElementById('selectPelanggan');
    const idPelanggan = activeMember ? activeMember.id_pelanggan : (customerSelect?.value ? parseInt(customerSelect.value) : null);
    const customerName = activeMember ? activeMember.nama_pelanggan : (customerSelect?.options[customerSelect.selectedIndex]?.text || 'Pelanggan Umum');
    const catatan = document.getElementById('transactionNote')?.value || '';

    if (paymentMethod === 'Tunai' && nominalBayar < grandTotal) {
        playErrorSound();
        showToast('Nominal pembayaran kurang dari total belanja!', 'danger');
        return;
    }

    if (paymentMethod === 'Saldo Member' && activeMember && activeMember.saldo < grandTotal) {
        playErrorSound();
        showToast('Saldo kartu member tidak mencukupi!', 'danger');
        return;
    }

    const btnSubmit = document.getElementById('btnSubmitTransaction');
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
    }

    const payload = {
        subtotal: subtotal,
        diskon: activeDiscountFixed || (subtotal * activeDiscountPercent / 100),
        pajak: isPpnActive ? (subtotal * ppnRate) : 0,
        total_belanja: grandTotal,
        bayar: (paymentMethod === 'Saldo Member') ? grandTotal : nominalBayar,
        kembalian: (paymentMethod === 'Saldo Member') ? 0 : Math.max(0, nominalBayar - grandTotal),
        metode_pembayaran: paymentMethod,
        id_pelanggan: idPelanggan,
        nama_pelanggan: customerName,
        catatan: catatan,
        items: cart
    };

    try {
        const response = await fetch('api/save_transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.status) {
            playSuccessSound();
            showToast('Transaksi berhasil disimpan!', 'success');

            // Close Payment Modal
            const paymentModalEl = document.getElementById('paymentModal');
            const paymentModal = bootstrap.Modal.getInstance(paymentModalEl);
            if (paymentModal) paymentModal.hide();

            // Populate & Open Thermal Receipt Modal
            populateReceiptModal(result.data);
            const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();

            // Update local stock in DOM
            cart.forEach(item => {
                const card = document.querySelector(`.pos-product-card[data-id="${item.id_produk}"]`);
                if (card) {
                    const currentStock = parseInt(card.getAttribute('data-stock'));
                    const newStock = Math.max(0, currentStock - item.qty);
                    card.setAttribute('data-stock', newStock);

                    const badge = card.querySelector('.stock-badge');
                    if (badge) {
                        badge.textContent = `Stok: ${newStock}`;
                        if (newStock <= 0) {
                            card.classList.add('out-of-stock');
                            badge.className = 'badge bg-danger stock-badge';
                        } else if (newStock <= 5) {
                            badge.className = 'badge bg-warning text-dark stock-badge';
                        }
                    }
                }
            });

            // Reset Member & Cart
            removeActiveMember(false);
            cart = [];
            renderCart();
        } else {
            playErrorSound();
            showToast(result.message || 'Gagal menyimpan transaksi', 'danger');
        }
    } catch (e) {
        console.error(e);
        playErrorSound();
        showToast('Terjadi kesalahan jaringan atau server', 'danger');
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-printer-fill me-1"></i> Simpan &amp; Cetak Struk';
        }
    }
}
