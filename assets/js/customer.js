/**
 * AGSmart Customer Frontend - Shopee Inspired
 * Cart, checkout payment & order interactions
 */
const API_BASE = 'api';
let cart = JSON.parse(localStorage.getItem('agsmart_cart') || '[]');
let selectedPayment = null;
const PAYMENT_LABELS = {
    transfer: 'Transfer Bank',
    qris: 'QRIS',
    ewallet: 'E-Wallet / Saldo Member'
};
function formatRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}
function saveCart() {
    localStorage.setItem('agsmart_cart', JSON.stringify(cart));
    updateCartBadge();
    renderCart();
}
function updateCartBadge() {
    const totalQty = cart.reduce((s, i) => s + (i.qty || 1), 0);
    document.querySelectorAll('.cart-badge').forEach(b => {
        b.textContent = totalQty;
        b.hidden = totalQty === 0;
    });
}
function addToCart(id, nama, harga, stok, qty = 1) {
    if (stok < 1) { showToast('Stok tidak mencukupi', 'warning'); return; }
    const existing = cart.find(i => i.id == id);
    if (existing) {
        if (existing.qty + qty > stok) { showToast('Stok melebihi ketersediaan', 'warning'); return; }
        existing.qty += qty;
    } else {
        cart.push({ id, nama, harga: Number(harga), qty: Math.max(1, qty) });
    }
    saveCart();
    showToast('Ditambahkan ke keranjang', 'success');
}
function changeQty(id, delta) {
    const item = cart.find(i => i.id == id);
    if (!item) return;
    item.qty = Math.max(1, Math.min(99, item.qty + delta));
    saveCart();
}
function removeFromCart(id) {
    cart = cart.filter(i => i.id != id);
    saveCart();
}
function cartSubtotal() {
    return cart.reduce((s, i) => s + i.harga * i.qty, 0);
}
function renderCart() {
    const container = document.getElementById('cartItems');
    const empty = document.getElementById('cartEmpty');
    const summary = document.getElementById('cartSummarySection');
    if (!container) return;
    container.innerHTML = '';
    if (cart.length === 0) {
        if (empty) empty.hidden = false;
        if (summary) summary.hidden = true;
        updateCartBadge();
        return;
    }
    if (empty) empty.hidden = true;
    if (summary) summary.hidden = false;
    let subtotal = 0;
    cart.forEach(item => {
        const line = item.harga * item.qty;
        subtotal += line;
        const el = document.createElement('div');
        el.className = 'cart-item';
        el.innerHTML = `
            <img src="assets/img/products/${item.id}.jpg" onerror="this.src='assets/img/no-image.svg'" alt="${item.nama}">
            <div class="cart-info">
                <div class="cart-name">${escapeHtml(item.nama)}</div>
                <div class="cart-price">${formatRupiah(item.harga)}</div>
                <div class="cart-stock">Stok tersedia</div>
            </div>
            <div class="cart-controls">
                <button class="qty-btn" onclick="appChangeQty(${item.id}, -1)">−</button>
                <span class="qty-val">${item.qty}</span>
                <button class="qty-btn" onclick="appChangeQty(${item.id}, 1)">+</button>
                <button class="remove-btn" onclick="appRemove(${item.id})">Hapus</button>
            </div>
        `;
        container.appendChild(el);
    });
    const diskonPersen = 2;
    const diskon = Math.floor(subtotal * diskonPersen / 100);
    const ongkir = subtotal >= 200000 ? 0 : 9000;
    const total = subtotal - diskon + ongkir;
    document.getElementById('cartSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('cartDiskon').textContent = '-' + formatRupiah(diskon);
    document.getElementById('cartOngkir').textContent = ongkir === 0 ? 'Gratis' : formatRupiah(ongkir);
    document.getElementById('cartTotal').textContent = formatRupiah(total);
    document.getElementById('checkoutTotal').textContent = formatRupiah(total);
    document.getElementById('checkoutTotalInput').value = total;
    updateCartBadge();
}
window.appChangeQty = changeQty;
window.appRemove = removeFromCart;
window.appAddToCart = addToCart;
function selectPayment(method) {
    selectedPayment = method;
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.toggle('selected', opt.dataset.method === method);
    });
    const extra = document.getElementById('paymentExtra');
    const proof = document.getElementById('proofUpload');
    const needsProof = method === 'transfer' || method === 'qris';
    if (extra) extra.hidden = false;
    if (proof) proof.hidden = !needsProof;
    const info = document.getElementById('methodInfo');
    if (info) {
        if (method === 'transfer') info.innerHTML = '<div class="proof-hint">Transfer ke rekening toko lalu unggah bukti transfer. Diproses setelah kasir verifikasi.</div>';
        else if (method === 'qris') info.innerHTML = '<div class="proof-hint">Scan QRIS lalu unggah bukti pembayaran.</div>';
        else info.innerHTML = '<div class="proof-hint">Bayar dengan saldo e-wallet kartu member. Saldo dipotong setelah konfirmasi.</div>';
    }
}
function escapeHtml(t) {
    const d = document.createElement('div'); d.textContent = t; return d.innerHTML;
}
function showToast(msg, type = 'info') {
    if (window.showToast) return window.showToast(msg, type);
    const el = document.getElementById('appToast');
    if (!el) { alert(msg); return; }
    const body = document.getElementById('toastText');
    const icon = document.getElementById('toastIcon');
    if (body) body.textContent = msg;
    if (icon) icon.className = 'fs-5 bi ' + (type === 'success' ? 'bi-check-circle-fill text-success' : type === 'danger' ? 'bi-exclamation-triangle-fill text-danger' : type === 'warning' ? 'bi-exclamation-circle-fill text-warning' : 'bi-info-circle-fill text-primary');
    new bootstrap.Toast(el, { delay: 3500 }).show();
}
// ---------- Init ----------
document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();
    const cartPage = document.getElementById('cartPage');
    if (cartPage) renderCart();
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.addEventListener('click', () => selectPayment(opt.dataset.method));
    });
    const first = document.querySelector('.payment-option');
    if (first && selectedPayment === null) selectPayment(first.dataset.method);
    const form = document.getElementById('checkoutForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!selectedPayment) { showToast('Pilih metode pembayaran', 'warning'); return; }
            if (cart.length === 0) { showToast('Keranjang kosong', 'warning'); return; }
            const btn = document.getElementById('btnCheckout');
            if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
            const formData = new FormData();
            formData.append('action', 'order');
            formData.append('method', selectedPayment);
            formData.append('items', JSON.stringify(cart));
            const file = document.getElementById('buktiTransfer').files[0];
            if (file) formData.append('bukti_transfer', file);
            try {
                const res = await fetch(`${API_BASE}/customer_request.php`, { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status) {
                    localStorage.removeItem('agsmart_cart');
                    cart = [];
                    updateCartBadge();
                    showToast(json.message, 'success');
                    setTimeout(() => { location.href = 'customer_orders.php'; }, 800);
                } else {
                    showToast(json.message, 'danger');
                    if (btn) { btn.disabled = false; btn.textContent = 'Pesan Sekarang & Bayar'; }
                }
            } catch (err) {
                showToast('Terjadi kesalahan jaringan', 'danger');
                if (btn) { btn.disabled = false; btn.textContent = 'Pesan Sekarang & Bayar'; }
            }
        });
    }
});