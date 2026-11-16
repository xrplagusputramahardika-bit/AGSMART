<?php
/**
 * Store & Receipt Settings
 * AGSmart POS System
 */
$pageTitle = 'Pengaturan Toko & Struk';
require_once __DIR__ . '/config/helper.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_pengaturan') {
    $namaToko = cleanInput($_POST['nama_toko']);
    $slogan = cleanInput($_POST['slogan']);
    $alamat = cleanInput($_POST['alamat']);
    $telepon = cleanInput($_POST['telepon']);
    $email = cleanInput($_POST['email']);
    $pesanStruk = cleanInput($_POST['pesan_struk']);
    $footerStruk = cleanInput($_POST['footer_struk']);
    $lebarStruk = cleanInput($_POST['lebar_struk'] ?? '58mm');
    $pajakPersen = (float)($_POST['pajak_persen'] ?? 0);

    if ($db) {
        try {
            $stmt = $db->prepare("
                UPDATE tbl_pengaturan SET
                    nama_toko = ?, slogan = ?, alamat = ?, telepon = ?, email = ?,
                    pesan_struk = ?, footer_struk = ?, lebar_struk = ?, pajak_persen = ?
                WHERE id = 1
            ");
            $stmt->execute([
                $namaToko, $slogan, $alamat, $telepon, $email,
                $pesanStruk, $footerStruk, $lebarStruk, $pajakPersen
            ]);
            setFlash('success', 'Pengaturan toko dan struk berhasil disimpan!');
        } catch (Exception $e) {
            setFlash('danger', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    }
    header("Location: pengaturan.php");
    exit;
}

$pengaturan = getPengaturan();

require_once __DIR__ . '/views/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-gear-fill text-primary"></i> Pengaturan Toko &amp; Struk
            </h4>
            <p class="text-slate-400 small mb-0">Sesuaikan identitas toko, nomor kontak, pesan struk kasir, dan preferensi cetak thermal.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Form Pengaturan -->
        <div class="col-lg-7">
            <div class="card card-custom p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="save_pengaturan">

                    <h6 class="fw-bold text-primary text-uppercase small tracking-wide mb-3">
                        <i class="bi bi-shop me-1"></i> Identitas Toko
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label small text-slate-300">Nama Toko / Usaha *</label>
                            <input type="text" name="nama_toko" id="inputNamaToko" class="form-control bg-slate-900 border-slate-700 text-light fw-bold" value="<?= htmlspecialchars($pengaturan['nama_toko']) ?>" required oninput="updateReceiptLivePreview()">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-slate-300">Slogan / Sub-judul</label>
                            <input type="text" name="slogan" id="inputSlogan" class="form-control bg-slate-900 border-slate-700 text-light" value="<?= htmlspecialchars($pengaturan['slogan']) ?>" oninput="updateReceiptLivePreview()">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-slate-300">Alamat Toko Lengkap</label>
                            <input type="text" name="alamat" id="inputAlamat" class="form-control bg-slate-900 border-slate-700 text-light" value="<?= htmlspecialchars($pengaturan['alamat']) ?>" oninput="updateReceiptLivePreview()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="telepon" id="inputTelepon" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="<?= htmlspecialchars($pengaturan['telepon']) ?>" oninput="updateReceiptLivePreview()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Email Toko</label>
                            <input type="email" name="email" class="form-control bg-slate-900 border-slate-700 text-light" value="<?= htmlspecialchars($pengaturan['email']) ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary text-uppercase small tracking-wide mb-3">
                        <i class="bi bi-printer me-1"></i> Kustomisasi Struk Kasir
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Ukuran Kertas Default</label>
                            <select name="lebar_struk" class="form-select bg-slate-900 border-slate-700 text-light">
                                <option value="58mm" <?= $pengaturan['lebar_struk'] === '58mm' ? 'selected' : '' ?>>58mm (Thermal Standar Mini)</option>
                                <option value="80mm" <?= $pengaturan['lebar_struk'] === '80mm' ? 'selected' : '' ?>>80mm (Thermal Kasir Lebar)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Pajak Bawaan (% PPN)</label>
                            <input type="number" step="0.01" name="pajak_persen" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="<?= $pengaturan['pajak_persen'] ?>" placeholder="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-slate-300">Pesan Ucapan di Struk (Header / Footer)</label>
                            <textarea name="pesan_struk" id="inputPesanStruk" class="form-control bg-slate-900 border-slate-700 text-light" rows="2" oninput="updateReceiptLivePreview()"><?= htmlspecialchars($pengaturan['pesan_struk']) ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-slate-300">Catatan Kaki Struk (Ketentuan Barang)</label>
                            <textarea name="footer_struk" id="inputFooterStruk" class="form-control bg-slate-900 border-slate-700 text-light" rows="2" oninput="updateReceiptLivePreview()"><?= htmlspecialchars($pengaturan['footer_struk']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                            <i class="bi bi-floppy-fill me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Preview Struk -->
        <div class="col-lg-5">
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-eye text-info"></i> Pratinjau Struk Kasir
                    </h6>
                    <span class="badge bg-slate-900 border border-slate-700 text-slate-400 small">Live Preview</span>
                </div>

                <div class="bg-slate-950 p-3 rounded-4 border border-slate-800 d-flex justify-content-center">
                    <!-- Thermal Paper Simulation -->
                    <div class="thermal-receipt-paper p-3 text-dark small" style="width: 320px;">
                        <div class="text-center mb-2">
                            <h5 class="fw-bold mb-0 text-dark" id="previewStoreName"><?= htmlspecialchars($pengaturan['nama_toko']) ?></h5>
                            <p class="small text-muted mb-0" id="previewSlogan"><?= htmlspecialchars($pengaturan['slogan']) ?></p>
                            <p class="small text-muted mb-0" id="previewAddress"><?= htmlspecialchars($pengaturan['alamat']) ?></p>
                            <p class="small text-muted mb-0" id="previewPhone">Telp: <?= htmlspecialchars($pengaturan['telepon']) ?></p>
                        </div>

                        <div class="receipt-divider dashed-line"></div>

                        <div class="d-flex justify-content-between text-muted small my-1">
                            <span>No: TRX-<?= date('Ymd') ?>-0001</span>
                            <span><?= date('d/m/Y H:i') ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Kasir: Kasir 1</span>
                            <span>Plg: Pelanggan Umum</span>
                        </div>

                        <div class="receipt-divider dashed-line"></div>

                        <div class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Minyak Goreng 2L</span>
                                <span class="text-dark font-mono">38.000</span>
                            </div>
                            <div class="text-muted small">1 x 38.000</div>

                            <div class="d-flex justify-content-between mt-1">
                                <span class="fw-semibold">Beras Ramos 5kg</span>
                                <span class="text-dark font-mono">70.000</span>
                            </div>
                            <div class="text-muted small">1 x 70.000</div>
                        </div>

                        <div class="receipt-divider dashed-line"></div>

                        <div class="my-1">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal</span>
                                <span class="font-mono">Rp 108.000</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold fs-6 my-1">
                                <span>TOTAL</span>
                                <span class="font-mono">Rp 108.000</span>
                            </div>
                            <div class="receipt-divider dashed-line"></div>
                            <div class="d-flex justify-content-between">
                                <span>Bayar (Tunai)</span>
                                <span class="font-mono">Rp 110.000</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Kembalian</span>
                                <span class="font-mono fw-bold">Rp 2.000</span>
                            </div>
                        </div>

                        <div class="receipt-divider dashed-line"></div>

                        <div class="text-center my-2">
                            <p class="small text-muted mb-1" id="previewPesan"><?= nl2br(htmlspecialchars($pengaturan['pesan_struk'])) ?></p>
                            <p class="small text-muted mb-0 fw-semibold" id="previewFooter"><?= htmlspecialchars($pengaturan['footer_struk']) ?></p>
                        </div>

                        <div class="text-center mt-3 pt-1 border-top border-secondary font-monospace barcode-font fs-3">
                            ||| | |||| | ||| |||| |
                        </div>
                        <div class="text-center small text-muted font-monospace">TRX-<?= date('Ymd') ?>-0001</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function updateReceiptLivePreview() {
        document.getElementById('previewStoreName').textContent = document.getElementById('inputNamaToko').value || 'AGSMART STORE';
        document.getElementById('previewSlogan').textContent = document.getElementById('inputSlogan').value || '';
        document.getElementById('previewAddress').textContent = document.getElementById('inputAlamat').value || '';
        document.getElementById('previewPhone').textContent = 'Telp: ' + (document.getElementById('inputTelepon').value || '-');
        document.getElementById('previewPesan').textContent = document.getElementById('inputPesanStruk').value || '';
        document.getElementById('previewFooter').textContent = document.getElementById('inputFooterStruk').value || '';
    }
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
