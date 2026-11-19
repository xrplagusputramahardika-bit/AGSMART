<?php
/**
 * Customer & Member Card Management (With RFID Tap, Saldo, Poin, & PVC Card Print)
 * AGSmart POS System
 */
$pageTitle = 'Data Pelanggan & Kartu Member';
require_once __DIR__ . '/config/helper.php';
requireAdmin();

$db = getDB();

// Handle Actions (Add, Quick Add, Edit, Delete, Topup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'quick_add') {
        $nama = cleanInput($_POST['nama_pelanggan']);
        $kota = cleanInput($_POST['kota'] ?? '');
        $telepon = cleanInput($_POST['telepon'] ?? '');
        $alamat = cleanInput($_POST['alamat'] ?? '');
        $noKartu = cleanInput($_POST['no_kartu'] ?? '');
        $tipeMember = cleanInput($_POST['tipe_member'] ?? 'Silver');
        $diskon = (float)($_POST['diskon_member'] ?? 0);
        $saldo = (float)($_POST['saldo_awal'] ?? 0);

        if (empty($noKartu)) {
            // Auto generate card number
            $noKartu = 'MBR-' . date('y') . rand(100000, 999999);
        }

        if ($diskon <= 0) {
            if ($tipeMember === 'Platinum') $diskon = 10.0;
            elseif ($tipeMember === 'Gold') $diskon = 5.0;
            elseif ($tipeMember === 'Silver') $diskon = 2.0;
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO tbl_pelanggan 
                (nama_pelanggan, kota, telepon, alamat, no_kartu, tipe_member, diskon_member, saldo, status_kartu) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
            ");
            $stmt->execute([$nama, $kota, $telepon, $alamat, $noKartu, $tipeMember, $diskon, $saldo]);
            setFlash('success', "Pelanggan &amp; Kartu Member <b>$nama</b> ($noKartu) berhasil didaftarkan!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal menambah pelanggan: " . $e->getMessage());
        }

        if ($action === 'quick_add') {
            header("Location: index.php");
        } else {
            header("Location: pelanggan.php");
        }
        exit;
    }

    if ($action === 'update') {
        $id = (int)$_POST['id_pelanggan'];
        $nama = cleanInput($_POST['nama_pelanggan']);
        $kota = cleanInput($_POST['kota'] ?? '');
        $telepon = cleanInput($_POST['telepon'] ?? '');
        $alamat = cleanInput($_POST['alamat'] ?? '');
        $noKartu = cleanInput($_POST['no_kartu'] ?? '');
        $tipeMember = cleanInput($_POST['tipe_member'] ?? 'Silver');
        $diskon = (float)($_POST['diskon_member'] ?? 0);
        $statusKartu = cleanInput($_POST['status_kartu'] ?? 'aktif');

        try {
            $stmt = $db->prepare("
                UPDATE tbl_pelanggan 
                SET nama_pelanggan = ?, kota = ?, telepon = ?, alamat = ?, 
                    no_kartu = ?, tipe_member = ?, diskon_member = ?, status_kartu = ?
                WHERE id_pelanggan = ?
            ");
            $stmt->execute([$nama, $kota, $telepon, $alamat, $noKartu, $tipeMember, $diskon, $statusKartu, $id]);
            setFlash('success', "Data kartu member <b>$nama</b> berhasil diperbarui!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal memperbarui pelanggan: " . $e->getMessage());
        }
        header("Location: pelanggan.php");
        exit;
    }

    if ($action === 'topup') {
        $id = (int)$_POST['id_pelanggan'];
        $nominal = (float)$_POST['nominal'];

        try {
            $stmt = $db->prepare("UPDATE tbl_pelanggan SET saldo = saldo + ? WHERE id_pelanggan = ?");
            $stmt->execute([$nominal, $id]);
            setFlash('success', "Top-up saldo sebesar " . formatRupiah($nominal) . " berhasil!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal melakukan top-up: " . $e->getMessage());
        }
        header("Location: pelanggan.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id_pelanggan'];
        try {
            $stmt = $db->prepare("DELETE FROM tbl_pelanggan WHERE id_pelanggan = ?");
            $stmt->execute([$id]);
            setFlash('success', "Pelanggan berhasil dihapus!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal menghapus pelanggan: " . $e->getMessage());
        }
        header("Location: pelanggan.php");
        exit;
    }
}

// Fetch Customers via view or table
$customers = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterTier = isset($_GET['tier']) ? $_GET['tier'] : '';

if ($db) {
    try {
        $query = "
            SELECT p.*,
                   COALESCE(v.jumlah_transaksi, 0) as jumlah_transaksi,
                   COALESCE(v.total_keseluruhan_belanja, 0) as total_keseluruhan_belanja
            FROM tbl_pelanggan p
            LEFT JOIN v_total_belanja_pelanggan v ON p.id_pelanggan = v.id_pelanggan
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $query .= " AND (p.nama_pelanggan LIKE ? OR p.kota LIKE ? OR p.telepon LIKE ? OR p.no_kartu LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($filterTier !== '') {
            $query .= " AND p.tipe_member = ?";
            $params[] = $filterTier;
        }

        $query .= " ORDER BY p.id_pelanggan ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/views/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-person-vcard-fill text-primary"></i> Data Pelanggan &amp; Kartu Member
            </h4>
            <p class="text-slate-400 small mb-0">Kelola nomor kartu RFID/Barcode member, level loyalitas, saldo tap, dan poin belanja.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddCustomer">
                <i class="bi bi-person-plus-fill me-1"></i> Registrasi Member Baru
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-slate-900 border-slate-700 text-slate-400"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-slate-900 border-slate-700 text-light" placeholder="Cari nama, nomor kartu member, nomor HP, atau kota..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="tier" class="form-select form-select-sm bg-slate-900 border-slate-700 text-light" onchange="this.form.submit()">
                    <option value="">-- Semua Level Member --</option>
                    <option value="Platinum" <?= $filterTier === 'Platinum' ? 'selected' : '' ?>>Platinum (Diskon 10%)</option>
                    <option value="Gold" <?= $filterTier === 'Gold' ? 'selected' : '' ?>>Gold (Diskon 5%)</option>
                    <option value="Silver" <?= $filterTier === 'Silver' ? 'selected' : '' ?>>Silver (Diskon 2%)</option>
                    <option value="Regular" <?= $filterTier === 'Regular' ? 'selected' : '' ?>>Regular</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Filter</button>
                <a href="pelanggan.php" class="btn btn-sm btn-outline-secondary rounded-pill" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Table Pelanggan & Member Cards -->
    <div class="card card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead class="table-secondary text-slate-400 small text-uppercase">
                    <tr>
                        <th class="ps-3" style="width: 140px;">No Kartu Member</th>
                        <th>Nama Pelanggan</th>
                        <th>Level Member</th>
                        <th>Kontak &amp; Kota</th>
                        <th class="text-center">Poin</th>
                        <th class="text-end">Saldo Kartu</th>
                        <th class="text-end">Total Belanja</th>
                        <th class="text-center pe-3" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-slate-500">
                                <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                Tidak ada data pelanggan yang cocok.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): 
                            $tier = $c['tipe_member'] ?? 'Regular';
                            $tierBadgeClass = ($tier === 'Platinum') ? 'badge-platinum' : (($tier === 'Gold') ? 'badge-gold' : (($tier === 'Silver') ? 'badge-silver' : 'badge-regular'));
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-1 font-mono fw-bold text-primary small">
                                        <i class="bi bi-nfc text-warning"></i>
                                        <span><?= htmlspecialchars($c['no_kartu'] ?: ('MBR-' . $c['id_pelanggan'])) ?></span>
                                    </div>
                                    <span class="badge bg-slate-900 border border-slate-700 text-slate-400 small-badge">
                                        Diskon: <?= (float)($c['diskon_member'] ?? 0) ?>%
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($c['nama_pelanggan']) ?></div>
                                    <div class="text-slate-400 small text-truncate" style="max-width: 180px;"><?= htmlspecialchars($c['alamat'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $tierBadgeClass ?> px-2 py-1 rounded-pill small">
                                        <i class="bi bi-gem me-1"></i><?= htmlspecialchars($tier) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small font-mono text-light"><?= htmlspecialchars($c['telepon'] ?: '-') ?></div>
                                    <div class="small text-slate-400"><i class="bi bi-geo-alt me-1 text-primary"></i><?= htmlspecialchars($c['kota'] ?: '-') ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 px-2 py-1 font-mono fw-bold">
                                        <?= number_format($c['poin'] ?? 0, 0, ',', '.') ?> Pts
                                    </span>
                                </td>
                                <td class="text-end font-mono fw-bold text-success">
                                    <?= formatRupiah($c['saldo'] ?? 0) ?>
                                </td>
                                <td class="text-end font-mono fw-bold text-white">
                                    <?= formatRupiah($c['total_keseluruhan_belanja']) ?>
                                    <div class="text-slate-500 small font-sans"><?= $c['jumlah_transaksi'] ?>x order</div>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Topup Button -->
                                        <button type="button" class="btn btn-outline-success" title="Top Up Saldo Member" onclick="openTopupModal(<?= $c['id_pelanggan'] ?>, '<?= addslashes(htmlspecialchars($c['nama_pelanggan'])) ?>', <?= (float)($c['saldo'] ?? 0) ?>)">
                                            <i class="bi bi-cash-stack"></i>
                                        </button>
                                        <!-- View Card Print Modal -->
                                        <button type="button" class="btn btn-outline-info" title="Lihat & Cetak Kartu Member" onclick="openMemberCardPreview(<?= htmlspecialchars(json_encode($c)) ?>)">
                                            <i class="bi bi-credit-card-2-front"></i>
                                        </button>
                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-warning" title="Edit" onclick="openEditCustomer(<?= htmlspecialchars(json_encode($c)) ?>)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Delete Form -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_pelanggan" value="<?= $c['id_pelanggan'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL REGISTRASI CUSTOMER & KARTU MEMBER BARU
======================================================== -->
<div class="modal fade" id="modalAddCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-person-plus text-primary me-2"></i> Registrasi Member Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nomor Kartu Member (RFID / Barcode) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-900 border-slate-700 text-warning"><i class="bi bi-nfc"></i></span>
                                <input type="text" name="no_kartu" id="addMemberCardNo" class="form-control bg-slate-900 border-slate-700 text-light font-mono" placeholder="Tempel kartu RFID atau ketik...">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateMemberCardNo()">Auto</button>
                            </div>
                            <span class="small text-slate-500">Dapat di-tap langsung menggunakan alat pembaca kartu USB.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Level / Tipe Member *</label>
                            <select name="tipe_member" id="addMemberTier" class="form-select bg-slate-900 border-slate-700 text-light" onchange="autoFillDiscount(this.value, 'addMemberDiscount')">
                                <option value="Silver" selected>Silver (Diskon 2%)</option>
                                <option value="Gold">Gold (Diskon 5%)</option>
                                <option value="Platinum">Platinum (Diskon 10%)</option>
                                <option value="Regular">Regular (Tanpa Diskon)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Diskon Otomatis Saat Belanja (%)</label>
                            <input type="number" step="0.1" name="diskon_member" id="addMemberDiscount" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="2.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Saldo Awal Kartu (Rp)</label>
                            <input type="number" name="saldo_awal" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nama Pelanggan / Pemegang Kartu *</label>
                            <input type="text" name="nama_pelanggan" class="form-control bg-slate-900 border-slate-700 text-light" required placeholder="Contoh: Rian Pratama">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nomor WhatsApp / HP *</label>
                            <input type="text" name="telepon" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Kota Domisili</label>
                            <input type="text" name="kota" class="form-control bg-slate-900 border-slate-700 text-light" placeholder="Contoh: Surabaya">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Alamat Rumah</label>
                            <input type="text" name="alamat" class="form-control bg-slate-900 border-slate-700 text-light" placeholder="Alamat jalan, nomor...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-slate-700">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Daftarkan Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL EDIT CUSTOMER & KARTU
======================================================== -->
<div class="modal fade" id="modalEditCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Data Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_pelanggan" id="editPlgId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nomor Kartu Member (RFID / Barcode) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-900 border-slate-700 text-warning"><i class="bi bi-nfc"></i></span>
                                <input type="text" name="no_kartu" id="editPlgNoKartu" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Level / Tipe Member</label>
                            <select name="tipe_member" id="editPlgTier" class="form-select bg-slate-900 border-slate-700 text-light" onchange="autoFillDiscount(this.value, 'editPlgDiskon')">
                                <option value="Silver">Silver (Diskon 2%)</option>
                                <option value="Gold">Gold (Diskon 5%)</option>
                                <option value="Platinum">Platinum (Diskon 10%)</option>
                                <option value="Regular">Regular (Tanpa Diskon)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Diskon Member (%)</label>
                            <input type="number" step="0.1" name="diskon_member" id="editPlgDiskon" class="form-control bg-slate-900 border-slate-700 text-light font-mono">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Status Kartu</label>
                            <select name="status_kartu" id="editPlgStatus" class="form-select bg-slate-900 border-slate-700 text-light">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="blokir">Blokir / Hilang</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nama Pelanggan *</label>
                            <input type="text" name="nama_pelanggan" id="editPlgNama" class="form-control bg-slate-900 border-slate-700 text-light" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Nomor WhatsApp / HP</label>
                            <input type="text" name="telepon" id="editPlgTelepon" class="form-control bg-slate-900 border-slate-700 text-light font-mono">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Kota Domisili</label>
                            <input type="text" name="kota" id="editPlgKota" class="form-control bg-slate-900 border-slate-700 text-light">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Alamat</label>
                            <input type="text" name="alamat" id="editPlgAlamat" class="form-control bg-slate-900 border-slate-700 text-light">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-slate-700">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Update Data Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL TOP-UP SALDO KARTU
======================================================== -->
<div class="modal fade" id="modalTopupSaldo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h6 class="modal-title fw-bold text-white"><i class="bi bi-wallet2 text-success me-2"></i> Top Up Saldo Kartu</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="topup">
                <input type="hidden" name="id_pelanggan" id="topupPlgId">
                <div class="modal-body p-3">
                    <p class="small text-slate-300 mb-1" id="topupPlgName">Nama Pelanggan</p>
                    <p class="small text-slate-400 mb-3">Saldo sekarang: <b id="topupCurrentSaldo" class="text-success font-mono">Rp 0</b></p>
                    
                    <label class="form-label small text-slate-300">Nominal Pengisian (Rp) *</label>
                    <input type="number" name="nominal" id="topupNominal" class="form-control bg-slate-900 border-slate-700 text-light font-mono fw-bold fs-5 mb-2" value="50000" required>
                    
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="document.getElementById('topupNominal').value=20000">20k</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="document.getElementById('topupNominal').value=50000">50k</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="document.getElementById('topupNominal').value=100000">100k</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="document.getElementById('topupNominal').value=200000">200k</button>
                    </div>
                </div>
                <div class="modal-footer border-slate-700 p-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-4 fw-bold">Konfirmasi Top Up</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL PREVIEW & CETAK KARTU MEMBER (PVC DIGITAL)
======================================================== -->
<div class="modal fade" id="modalCardPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-slate-900 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-800">
                <h6 class="modal-title fw-bold text-white"><i class="bi bi-credit-card-2-front text-info me-2"></i> Kartu Member Digital / Fisik</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <!-- Visual Member Card -->
                <div class="pvc-member-card pvc-card-gold mx-auto mb-3" id="pvcCardContainer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold tracking-wide fs-6 d-flex align-items-center gap-1">
                            <i class="bi bi-cart4 text-white"></i> AGSMART STORE
                        </div>
                        <span class="badge badge-gold" id="pvcTierBadge">GOLD MEMBER</span>
                    </div>

                    <div class="d-flex align-items-center gap-3 my-2">
                        <div class="card-emv-chip"></div>
                        <i class="bi bi-wifi fs-4 text-white-50" style="transform: rotate(90deg);"></i>
                    </div>

                    <div class="text-start">
                        <div class="font-mono fs-5 fw-bold tracking-widest text-white" id="pvcCardNumber">MBR-88012302</div>
                        <div class="d-flex justify-content-between align-items-end mt-1">
                            <div>
                                <div class="text-uppercase small text-white-50" style="font-size: 0.65rem;">Cardholder Name</div>
                                <div class="fw-bold text-white small" id="pvcMemberName">Budi Santoso</div>
                            </div>
                            <div class="text-end">
                                <div class="text-uppercase small text-white-50" style="font-size: 0.65rem;">Valid Thru</div>
                                <div class="font-mono small text-white">12/28</div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="small text-slate-400 mb-0">Kartu ini dapat dicetak pada kertas PVC kartu ID atau di-tap langsung menggunakan kartu RFID USB.</p>
            </div>
            <div class="modal-footer border-slate-800 p-2 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-sm btn-info rounded-pill px-4 fw-bold" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak Kartu
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function generateMemberCardNo() {
        const rand = 'MBR-' + Math.floor(10000000 + Math.random() * 90000000);
        document.getElementById('addMemberCardNo').value = rand;
    }

    function autoFillDiscount(tier, targetInputId) {
        let disc = 0;
        if (tier === 'Platinum') disc = 10.0;
        else if (tier === 'Gold') disc = 5.0;
        else if (tier === 'Silver') disc = 2.0;
        document.getElementById(targetInputId).value = disc;
    }

    function openEditCustomer(c) {
        document.getElementById('editPlgId').value = c.id_pelanggan;
        document.getElementById('editPlgNama').value = c.nama_pelanggan;
        document.getElementById('editPlgKota').value = c.kota || '';
        document.getElementById('editPlgTelepon').value = c.telepon || '';
        document.getElementById('editPlgAlamat').value = c.alamat || '';
        document.getElementById('editPlgNoKartu').value = c.no_kartu || ('MBR-' + c.id_pelanggan);
        document.getElementById('editPlgTier').value = c.tipe_member || 'Silver';
        document.getElementById('editPlgDiskon').value = c.diskon_member || 0;
        document.getElementById('editPlgStatus').value = c.status_kartu || 'aktif';

        new bootstrap.Modal(document.getElementById('modalEditCustomer')).show();
    }

    function openTopupModal(id, name, currentSaldo) {
        document.getElementById('topupPlgId').value = id;
        document.getElementById('topupPlgName').textContent = name;
        document.getElementById('topupCurrentSaldo').textContent = formatRupiahJs(currentSaldo);

        new bootstrap.Modal(document.getElementById('modalTopupSaldo')).show();
    }

    function openMemberCardPreview(c) {
        const cardNo = c.no_kartu || ('MBR-' + c.id_pelanggan);
        const tier = c.tipe_member || 'Silver';
        const name = c.nama_pelanggan;

        document.getElementById('pvcCardNumber').textContent = cardNo;
        document.getElementById('pvcMemberName').textContent = name;
        document.getElementById('pvcTierBadge').textContent = tier.toUpperCase() + ' MEMBER';

        const cardContainer = document.getElementById('pvcCardContainer');
        cardContainer.className = 'pvc-member-card mx-auto mb-3 ' + (
            tier === 'Platinum' ? 'pvc-card-platinum' :
            tier === 'Gold' ? 'pvc-card-gold' : 'pvc-card-silver'
        );

        new bootstrap.Modal(document.getElementById('modalCardPreview')).show();
    }
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
