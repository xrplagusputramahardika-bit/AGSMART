<?php
/**
 * Modern Thermal Receipt Modal Component (High-End Authentic Receipt)
 * AGSmart POS System
 */
?>
<!-- Modal Struk Pembayaran -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm-custom">
        <div class="modal-content receipt-modal-content">
            <!-- Modal Header -->
            <div class="modal-header receipt-modal-header py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-primary-orange fs-5"></i>
                    <span class="fw-bold text-white small" id="receiptModalLabel">Pratinjau Struk Kasir</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Paper Size Toggle -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ukuran kertas struk">
                        <button type="button" class="btn btn-outline-secondary py-1 px-2" id="btnWidth58" onclick="setReceiptWidth('58mm')" title="Kertas Kasir Kecil (58mm)">58mm</button>
                        <button type="button" class="btn btn-primary active py-1 px-2" id="btnWidth80" onclick="setReceiptWidth('80mm')" title="Kertas Kasir Standar (80mm)">80mm</button>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            <!-- Modal Body (Paper Scroll Area) -->
            <div class="modal-body receipt-preview-body p-3">
                
                <!-- THERMAL RECEIPT PAPER (Authentic Look) -->
                <div class="thermal-receipt-paper receipt-width-80 mx-auto" id="printableReceipt">
                    
                    <!-- Paper Top Jagged Cut Edge -->
                    <div class="receipt-tear-edge top-edge"></div>

                    <!-- Store Header & Logo -->
                    <header class="receipt-header text-center">
                        <!-- Monochrome Vector Logo -->
                        <div class="receipt-logo-wrap text-center mb-1">
                            <img src="assets/img/logo-receipt.svg" alt="AGSMART" class="receipt-logo-img" id="recLogo">
                        </div>
                        <h4 class="receipt-store-name mb-0" id="recStoreName"><?= htmlspecialchars($pengaturan['nama_toko']) ?></h4>
                        <p class="receipt-store-detail receipt-slogan" id="recStoreSlogan"><?= htmlspecialchars($pengaturan['slogan']) ?></p>
                        <p class="receipt-store-detail" id="recStoreAddress"><?= htmlspecialchars($pengaturan['alamat']) ?></p>
                        <p class="receipt-store-detail" id="recStorePhone">Telp: <?= htmlspecialchars($pengaturan['telepon']) ?> &bull; Web: agsmart.id</p>
                        <div class="receipt-badge-title">★ BUKTI PEMBAYARAN SAH ★</div>
                    </header>

                    <div class="receipt-divider dashed-line"></div>

                    <!-- Metadata Grid -->
                    <section class="receipt-meta" aria-label="Informasi transaksi">
                        <div class="receipt-meta-row">
                            <span class="meta-label">No. Faktur</span>
                            <strong class="meta-val font-mono" id="recFaktur">TRX-20261116-0001</strong>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="meta-label">Tanggal / Waktu</span>
                            <strong class="meta-val" id="recDate">16/11/2026 10:45</strong>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="meta-label">Kasir</span>
                            <strong class="meta-val" id="recKasir">Kasir 1</strong>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="meta-label">Pelanggan</span>
                            <strong class="meta-val" id="recCustomer">Pelanggan Umum</strong>
                        </div>

                        <!-- Member Card VIP Box (Shown if customer has card) -->
                        <div id="recMemberCardRow" class="receipt-member-box mt-2" style="display: none;">
                            <div class="receipt-member-badge text-center">
                                ★ <span id="recMemberTier">GOLD MEMBER</span> ★
                            </div>
                            <div class="receipt-meta-row mt-1">
                                <span>No. Kartu:</span>
                                <strong class="font-mono" id="recMemberCardNo">MBR-0000</strong>
                            </div>
                            <div class="receipt-meta-row">
                                <span>Poin Transaksi:</span>
                                <strong>+<span id="recMemberPoints">0</span> Pts</strong>
                            </div>
                            <div class="receipt-meta-row" id="recMemberBalanceRow" style="display: none;">
                                <span>Sisa Saldo Kartu:</span>
                                <strong id="recMemberBalance">Rp 0</strong>
                            </div>
                        </div>
                    </section>

                    <div class="receipt-divider dashed-line"></div>

                    <!-- Items Header Table -->
                    <div class="receipt-items-col-header">
                        <span class="col-item">PRODUK / ITEM</span>
                        <span class="col-qty text-center">QTY</span>
                        <span class="col-price text-end">TOTAL</span>
                    </div>

                    <div class="receipt-divider dotted-line"></div>

                    <!-- Items List Injected by JS -->
                    <section class="receipt-items" id="recItemsList" aria-label="Daftar belanja">
                        <!-- Dynamic items -->
                    </section>

                    <div class="receipt-divider double-dashed"></div>

                    <!-- Totals & Payment Breakdown -->
                    <section class="receipt-totals" aria-label="Rincian pembayaran">
                        <div class="receipt-total-row">
                            <span>Subtotal Belanja</span>
                            <span class="font-mono" id="recSubtotal">Rp 0</span>
                        </div>
                        <div class="receipt-total-row receipt-discount-text" id="recDiskonRow" style="display: none;">
                            <span>Diskon / Potongan</span>
                            <span class="font-mono" id="recDiskon">-Rp 0</span>
                        </div>
                        <div class="receipt-total-row receipt-muted" id="recPajakRow" style="display: none;">
                            <span>PPN (11%)</span>
                            <span class="font-mono" id="recPajak">Rp 0</span>
                        </div>

                        <div class="receipt-grand-total-box my-1">
                            <div class="receipt-grand-total">
                                <span>TOTAL AKHIR</span>
                                <strong class="font-mono" id="recGrandTotal">Rp 0</strong>
                            </div>
                        </div>

                        <div class="receipt-total-row pt-1">
                            <span>Bayar (<b id="recMetode">Tunai</b>)</span>
                            <span class="font-mono" id="recBayar">Rp 0</span>
                        </div>
                        <div class="receipt-total-row">
                            <span>Kembalian</span>
                            <strong class="font-mono" id="recKembalian">Rp 0</strong>
                        </div>

                        <!-- Savings Highlight (Customer Delight!) -->
                        <div id="recHematBox" class="receipt-savings-box my-2" style="display: none;">
                            🎉 <b>ANDA HEMAT:</b> <span id="recHematAmount" class="font-mono">Rp 0</span>
                        </div>
                    </section>

                    <div class="receipt-divider dashed-line"></div>

                    <!-- Barcode & QR Code Section -->
                    <section class="receipt-barcode-section text-center my-2">
                        <!-- Dynamic SVG Barcode Container -->
                        <div class="barcode-svg-container" id="recBarcodeSvg"></div>
                        <div class="receipt-barcode-code font-mono" id="recBarcodeText">* TRX-000000 *</div>
                        
                        <!-- Dynamic QR Code Container for Digital E-Receipt -->
                        <div class="receipt-qr-wrap d-flex align-items-center justify-content-center gap-2 mt-2">
                            <div class="receipt-qr-box" id="recQrBox"></div>
                            <div class="receipt-qr-caption text-start">
                                <div class="qr-title">E-STRUK RESMI</div>
                                <div class="qr-desc">Scan QR untuk verifikasi &amp; simpan struk digital</div>
                            </div>
                        </div>
                    </section>

                    <div class="receipt-divider dashed-line"></div>

                    <!-- Receipt Footer -->
                    <footer class="receipt-footer text-center">
                        <!-- Custom Store Thank-you Message -->
                        <p class="receipt-message mb-1" id="recPesan"><?= nl2br(htmlspecialchars($pengaturan['pesan_struk'])) ?></p>
                        
                        <!-- Wi-Fi & Facilities Info (Modern Mart touch) -->
                        <div class="receipt-wifi-info my-1">
                            <i class="bi bi-wifi"></i> Free Wi-Fi: <b>AGSMART-FREE</b> | Pass: <b>belanjahemat</b>
                        </div>

                        <p class="receipt-footer-note mb-1" id="recFooterText"><?= htmlspecialchars($pengaturan['footer_struk']) ?></p>
                        
                        <div class="receipt-closing mt-2">
                            <div>*** TERIMA KASIH TELAH BERBELANJA ***</div>
                            <div class="small">Layanan Pelanggan: 0812-8888-9999</div>
                            <div class="small text-muted font-mono">www.agsmart.id &bull; @agsmart.official</div>
                        </div>
                    </footer>

                    <!-- Paper Bottom Jagged Cut Edge -->
                    <div class="receipt-tear-edge bottom-edge"></div>

                </div> <!-- Close #printableReceipt -->

            </div> <!-- Close .modal-body -->

            <!-- Modal Action Buttons -->
            <div class="modal-footer receipt-modal-footer d-flex justify-content-between py-2 px-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Tutup
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill btn-sm px-3 border" onclick="copyReceiptText()" title="Salin teks struk untuk dikirim via WhatsApp">
                        <i class="bi bi-clipboard me-1"></i> Salin Teks
                    </button>
                    <button type="button" class="btn btn-primary-orange rounded-pill btn-sm px-4 fw-bold shadow-sm" onclick="printReceipt()">
                        <i class="bi bi-printer-fill me-1"></i> Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
