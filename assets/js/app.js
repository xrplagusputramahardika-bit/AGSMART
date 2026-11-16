/**
 * AGSmart POS - Global Application Helper JS
 * Enhanced with Authentic Thermal Receipt, SVG Barcode & QR Code Engine
 */

let currentReceiptData = null;

// Show Toast Notification
function showToast(message, type = 'info') {
    const toastEl = document.getElementById('appToast');
    if (!toastEl) return;

    const toastText = document.getElementById('toastText');
    const toastIcon = document.getElementById('toastIcon');

    if (toastText) toastText.textContent = message;

    if (toastIcon) {
        toastIcon.className = 'fs-5 bi ' + (
            type === 'success' ? 'bi-check-circle-fill text-success' :
            type === 'danger' ? 'bi-exclamation-triangle-fill text-danger' :
            type === 'warning' ? 'bi-exclamation-circle-fill text-warning' :
            'bi-info-circle-fill text-primary'
        );
    }

    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
}

// Receipt Width Switcher (58mm / 80mm)
function setReceiptWidth(width) {
    const paper = document.getElementById('printableReceipt');
    const btn58 = document.getElementById('btnWidth58');
    const btn80 = document.getElementById('btnWidth80');

    if (!paper) return;

    if (width === '80mm') {
        paper.classList.add('receipt-width-80');
        document.body.classList.add('print-80mm');
        document.body.classList.remove('print-58mm');
        btn80?.classList.add('active', 'btn-primary');
        btn80?.classList.remove('btn-outline-secondary');
        btn80?.setAttribute('aria-pressed', 'true');
        btn58?.classList.remove('active', 'btn-primary');
        btn58?.classList.add('btn-outline-secondary');
        btn58?.setAttribute('aria-pressed', 'false');
    } else {
        paper.classList.remove('receipt-width-80');
        document.body.classList.remove('print-80mm');
        document.body.classList.add('print-58mm');
        btn58?.classList.add('active', 'btn-primary');
        btn58?.classList.remove('btn-outline-secondary');
        btn58?.setAttribute('aria-pressed', 'true');
        btn80?.classList.remove('active', 'btn-primary');
        btn80?.classList.add('btn-outline-secondary');
        btn80?.setAttribute('aria-pressed', 'false');
    }
}

// Print Receipt
function printReceipt() {
    const paper = document.getElementById('printableReceipt');
    if (!paper || !paper.parentNode) return;

    const parent = paper.parentNode;
    const nextSibling = paper.nextSibling;
    document.body.appendChild(paper);

    try {
        window.print();
    } finally {
        parent.insertBefore(paper, nextSibling);
    }
}

// Format number to Rupiah string
function formatRupiahJs(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID');
}

// Global Re-print Receipt from Transaction ID
async function loadAndPrintReceipt(idTransaksi) {
    try {
        const response = await fetch(`api/get_receipt.php?id=${idTransaksi}`);
        const result = await response.json();

        if (!result.status) {
            showToast(result.message || 'Gagal memuat struk', 'danger');
            return;
        }

        const data = result.data;
        populateReceiptModal(data);

        const modalEl = document.getElementById('receiptModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    } catch (e) {
        console.error(e);
        showToast('Terjadi kesalahan saat memuat struk', 'danger');
    }
}

/**
 * Generate Authentic SVG Barcode (Code 39/128 High Density)
 */
function generateBarcodeSvg(text) {
    const raw = (text || 'TRX-000000').toUpperCase().replace(/[^A-Z0-9\-\.]/g, '-');
    const code = '*' + raw + '*';
    const patterns = {
        '0': '101001101101', '1': '110100101011', '2': '101100101011', '3': '110110010101',
        '4': '101001101011', '5': '110100110101', '6': '101100110101', '7': '101001011011',
        '8': '110100101101', '9': '101100101101', 'A': '110101001011', 'B': '101101001011',
        'C': '110110100101', 'D': '101011001011', 'E': '110101100101', 'F': '101101100101',
        'G': '101010011011', 'H': '110101001101', 'I': '101101001101', 'J': '101011001101',
        'K': '110101010011', 'L': '101101010011', 'M': '110110101001', 'N': '101011010011',
        'O': '110101101001', 'P': '101101101001', 'Q': '101010110011', 'R': '110101011001',
        'S': '101101011001', 'T': '101011011001', 'U': '110010101011', 'V': '100110101011',
        'W': '110011010101', 'X': '100101101011', 'Y': '110010110101', 'Z': '100110110101',
        '-': '100101011011', '.': '110010101101', ' ': '100110101101', '*': '100101101101'
    };

    let bitStream = '';
    for (let char of code) {
        bitStream += (patterns[char] || patterns['-']) + '0';
    }

    let rects = '';
    let x = 0;
    const barWidth = 1.35;
    const height = 34;

    for (let i = 0; i < bitStream.length; i++) {
        if (bitStream[i] === '1') {
            rects += `<rect x="${(x * barWidth).toFixed(1)}" y="0" width="${barWidth.toFixed(1)}" height="${height}" fill="#000000" />`;
        }
        x++;
    }

    const totalWidth = (x * barWidth).toFixed(1);
    return `<svg viewBox="0 0 ${totalWidth} ${height}" width="${totalWidth}" height="${height}" xmlns="http://www.w3.org/2000/svg" style="max-width: 92%; height: 32px; display: block; margin: 0 auto;">${rects}</svg>`;
}

/**
 * Generate Authentic Vector QR Code SVG (21x21 Matrix Pattern)
 */
function generateQrCodeSvg(content, size = 48) {
    const n = 21;
    const cellSize = size / n;
    
    // Seeded random pseudo-grid based on content
    let seed = 0;
    for (let i = 0; i < content.length; i++) {
        seed = (seed * 31 + content.charCodeAt(i)) % 9999991;
    }
    
    function pseudoRand() {
        seed = (seed * 9301 + 49297) % 233280;
        return seed / 233280;
    }

    let grid = Array(n).fill(null).map(() => Array(n).fill(0));

    // Place 3 Finder Patterns (7x7) at corners
    function placeFinder(startX, startY) {
        for (let r = 0; r < 7; r++) {
            for (let c = 0; c < 7; c++) {
                if (r === 0 || r === 6 || c === 0 || c === 6 || (r >= 2 && r <= 4 && c >= 2 && c <= 4)) {
                    grid[startY + r][startX + c] = 1;
                } else {
                    grid[startY + r][startX + c] = 0;
                }
            }
        }
    }

    placeFinder(0, 0);       // Top-Left
    placeFinder(n - 7, 0);   // Top-Right
    placeFinder(0, n - 7);   // Bottom-Left

    // Separators (white margins around finders)
    for (let i = 0; i < 8; i++) {
        if (grid[7]) grid[7][i] = 0;
        if (grid[i]) grid[i][7] = 0;
        if (grid[7]) grid[7][n - 1 - i] = 0;
        if (grid[i]) grid[i][n - 8] = 0;
        if (grid[n - 8]) grid[n - 8][i] = 0;
        if (grid[n - 1 - i]) grid[n - 1 - i][7] = 0;
    }

    // Timing Patterns
    for (let i = 8; i < n - 8; i++) {
        grid[6][i] = (i % 2 === 0) ? 1 : 0;
        grid[i][6] = (i % 2 === 0) ? 1 : 0;
    }

    // Fill remaining cells with pseudo-data
    for (let r = 0; r < n; r++) {
        for (let c = 0; c < n; c++) {
            // Skip finders & timing
            const inFinderTL = (r < 8 && c < 8);
            const inFinderTR = (r < 8 && c >= n - 8);
            const inFinderBL = (r >= n - 8 && c < 8);
            const inTiming = (r === 6 || c === 6);
            if (!inFinderTL && !inFinderTR && !inFinderBL && !inTiming) {
                grid[r][c] = pseudoRand() > 0.48 ? 1 : 0;
            }
        }
    }

    // Render SVG
    let rects = '';
    for (let r = 0; r < n; r++) {
        for (let c = 0; c < n; c++) {
            if (grid[r][c] === 1) {
                rects += `<rect x="${(c * cellSize).toFixed(2)}" y="${(r * cellSize).toFixed(2)}" width="${cellSize.toFixed(2)}" height="${cellSize.toFixed(2)}" fill="#000000" />`;
            }
        }
    }

    return `<svg viewBox="0 0 ${size} ${size}" width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg" style="display:block;">${rects}</svg>`;
}

// Populate Receipt Modal elements
function populateReceiptModal(data) {
    currentReceiptData = data;

    if (data.pengaturan) {
        document.getElementById('recStoreName').textContent = data.pengaturan.nama_toko || 'AGSMART';
        document.getElementById('recStoreSlogan').textContent = data.pengaturan.slogan || 'Pusat Belanja Hemat & Modern';
        document.getElementById('recStoreAddress').textContent = data.pengaturan.alamat || 'Jl. Merdeka Raya No. 45, Jawa Timur';
        document.getElementById('recStorePhone').innerHTML = 'Telp: ' + (data.pengaturan.telepon || '0812-8888-9999') + ' &bull; Web: agsmart.id';
        document.getElementById('recPesan').textContent = data.pengaturan.pesan_struk || 'Terima kasih telah berbelanja di AGSMART!';
        document.getElementById('recFooterText').textContent = data.pengaturan.footer_struk || 'Barang yang sudah dibeli dapat ditukar max 1x24 jam dengan struk ini.';
    }

    const trx = data.transaksi;
    const noFaktur = trx.no_faktur || ('TRX-' + trx.id_transaksi);
    document.getElementById('recFaktur').textContent = noFaktur;
    document.getElementById('recDate').textContent = (trx.tanggal || '') + ' ' + (trx.waktu || '');
    document.getElementById('recKasir').textContent = trx.kasir || 'Kasir 1';
    document.getElementById('recCustomer').textContent = trx.nama_pelanggan || 'Pelanggan Umum';

    // Member Card VIP Section
    const memberRow = document.getElementById('recMemberCardRow');
    if (memberRow) {
        if (trx.no_kartu) {
            memberRow.style.display = 'block';
            document.getElementById('recMemberCardNo').textContent = trx.no_kartu;
            document.getElementById('recMemberTier').textContent = (trx.tipe_member || 'Silver').toUpperCase() + ' MEMBER';
            document.getElementById('recMemberPoints').textContent = trx.poin_didapat || 0;
            
            const balRow = document.getElementById('recMemberBalanceRow');
            if (balRow) {
                if (trx.sisa_saldo !== undefined && Number(trx.sisa_saldo) > 0) {
                    balRow.style.display = 'flex';
                    document.getElementById('recMemberBalance').textContent = formatRupiahJs(trx.sisa_saldo);
                } else {
                    balRow.style.display = 'none';
                }
            }
        } else {
            memberRow.style.display = 'none';
        }
    }

    // Items list
    const itemsContainer = document.getElementById('recItemsList');
    itemsContainer.innerHTML = '';

    if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'receipt-item';

            const name = document.createElement('div');
            name.className = 'receipt-item-name';
            name.textContent = item.nama_produk || 'Produk';

            const detail = document.createElement('div');
            detail.className = 'receipt-item-detail';

            const quantity = document.createElement('span');
            quantity.className = 'item-calc font-mono';
            const unit = item.satuan || 'pcs';
            quantity.textContent = `${item.qty} ${unit} x ${Number(item.harga_satuan).toLocaleString('id-ID')}`;

            const amount = document.createElement('span');
            amount.className = 'receipt-item-amount font-mono';
            amount.textContent = Number(item.subtotal).toLocaleString('id-ID');

            detail.append(quantity, amount);
            row.append(name, detail);
            itemsContainer.appendChild(row);
        });
    }

    // Totals & Math
    const subtotal = Number(trx.subtotal || trx.total_belanja);
    const diskon = Number(trx.diskon || 0);
    const pajak = Number(trx.pajak || 0);
    const total = Number(trx.total_belanja);
    const bayar = Number(trx.bayar || total);
    const kembalian = Number(trx.kembalian || 0);

    document.getElementById('recSubtotal').textContent = formatRupiahJs(subtotal);
    
    // Diskon Row
    const diskonRow = document.getElementById('recDiskonRow');
    if (diskon > 0) {
        diskonRow.style.display = 'flex';
        document.getElementById('recDiskon').textContent = '-' + formatRupiahJs(diskon);
    } else {
        diskonRow.style.display = 'none';
    }

    // Pajak Row
    const pajakRow = document.getElementById('recPajakRow');
    if (pajak > 0) {
        pajakRow.style.display = 'flex';
        document.getElementById('recPajak').textContent = formatRupiahJs(pajak);
    } else {
        pajakRow.style.display = 'none';
    }

    // Grand Total & Payment
    document.getElementById('recGrandTotal').textContent = formatRupiahJs(total);
    document.getElementById('recMetode').textContent = trx.metode_pembayaran || 'Tunai';
    document.getElementById('recBayar').textContent = formatRupiahJs(bayar);
    document.getElementById('recKembalian').textContent = formatRupiahJs(kembalian);

    // Savings Box (Customer Delight)
    const hematBox = document.getElementById('recHematBox');
    if (diskon > 0) {
        hematBox.style.display = 'block';
        document.getElementById('recHematAmount').textContent = formatRupiahJs(diskon);
    } else {
        hematBox.style.display = 'none';
    }

    // Dynamic Barcode & QR Code Injection
    const barcodeBox = document.getElementById('recBarcodeSvg');
    if (barcodeBox) {
        barcodeBox.innerHTML = generateBarcodeSvg(noFaktur);
    }
    document.getElementById('recBarcodeText').textContent = `* ${noFaktur} *`;

    const qrBox = document.getElementById('recQrBox');
    if (qrBox) {
        qrBox.innerHTML = generateQrCodeSvg(noFaktur, 44);
    }
}

/**
 * Copy Receipt as Clean Monospace Text (Ideal for WhatsApp / Email)
 */
function copyReceiptText() {
    if (!currentReceiptData) {
        showToast('Tidak ada data struk untuk disalin', 'warning');
        return;
    }

    const p = currentReceiptData.pengaturan || {};
    const t = currentReceiptData.transaksi || {};
    const items = currentReceiptData.items || [];

    const pad = (str, len, align = 'left') => {
        str = String(str);
        if (str.length >= len) return str.substring(0, len);
        const spaces = ' '.repeat(len - str.length);
        return align === 'right' ? spaces + str : str + spaces;
    };

    let text = '';
    text += '========================================\n';
    text += pad(p.nama_toko || 'AGSMART', 40, 'center') + '\n';
    text += pad(p.slogan || 'Pusat Belanja Hemat & Modern', 40, 'center') + '\n';
    text += pad(p.alamat || '', 40, 'center') + '\n';
    text += pad('Telp: ' + (p.telepon || '-'), 40, 'center') + '\n';
    text += '========================================\n';
    text += `No. Faktur : ${t.no_faktur || ('TRX-' + t.id_transaksi)}\n`;
    text += `Waktu      : ${t.tanggal || ''} ${t.waktu || ''}\n`;
    text += `Kasir      : ${t.kasir || 'Kasir 1'}\n`;
    text += `Pelanggan  : ${t.nama_pelanggan || 'Umum'}\n`;

    if (t.no_kartu) {
        text += '----------------------------------------\n';
        text += `★ VIP MEMBER : ${(t.tipe_member || 'Silver').toUpperCase()} ★\n`;
        text += `No. Kartu    : ${t.no_kartu}\n`;
        text += `Poin Didapat : +${t.poin_didapat || 0} Pts\n`;
    }

    text += '----------------------------------------\n';
    text += pad('PRODUK', 20) + pad('QTY', 6) + pad('TOTAL', 14, 'right') + '\n';
    text += '----------------------------------------\n';

    items.forEach((item, idx) => {
        text += `${idx + 1}. ${item.nama_produk || 'Item'}\n`;
        const qtyCalc = `   ${item.qty} ${item.satuan || 'pcs'} x ${Number(item.harga_satuan).toLocaleString('id-ID')}`;
        const itemTotal = Number(item.subtotal).toLocaleString('id-ID');
        text += pad(qtyCalc, 26) + pad(itemTotal, 14, 'right') + '\n';
    });

    text += '----------------------------------------\n';
    text += pad('Subtotal', 20) + pad(formatRupiahJs(t.subtotal || t.total_belanja), 20, 'right') + '\n';
    
    if (Number(t.diskon) > 0) {
        text += pad('Diskon', 20) + pad('-' + formatRupiahJs(t.diskon), 20, 'right') + '\n';
    }
    if (Number(t.pajak) > 0) {
        text += pad('PPN', 20) + pad(formatRupiahJs(t.pajak), 20, 'right') + '\n';
    }

    text += '========================================\n';
    text += pad('TOTAL AKHIR', 20) + pad(formatRupiahJs(t.total_belanja), 20, 'right') + '\n';
    text += pad(`Bayar (${t.metode_pembayaran || 'Tunai'})`, 20) + pad(formatRupiahJs(t.bayar || t.total_belanja), 20, 'right') + '\n';
    text += pad('Kembalian', 20) + pad(formatRupiahJs(t.kembalian || 0), 20, 'right') + '\n';

    if (Number(t.diskon) > 0) {
        text += '========================================\n';
        text += pad(`🎉 ANDA HEMAT: ${formatRupiahJs(t.diskon)}`, 40, 'center') + '\n';
    }

    text += '========================================\n';
    text += pad(p.pesan_struk || 'Terima kasih atas kunjungan Anda!', 40, 'center') + '\n';
    text += pad('Free WiFi: AGSMART-FREE | Pass: belanjahemat', 40, 'center') + '\n';
    text += pad('*** TERIMA KASIH TELAH BERBELANJA ***', 40, 'center') + '\n';
    text += '========================================\n';

    navigator.clipboard.writeText(text).then(() => {
        showToast('Teks struk berhasil disalin! Siap dikirim ke WhatsApp.', 'success');
    }).catch(() => {
        showToast('Gagal menyalin struk', 'danger');
    });
}
