<?php
/**
 * Transaction History & Receipt Printing
 * AGSmart POS System (Retaillet Theme)
 */
$pageTitle = 'Riwayat Transaksi';
require_once __DIR__ . '/config/helper.php';

$db = getDB();

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$metode = isset($_GET['metode']) ? $_GET['metode'] : '';

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Transaksi_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'No Faktur', 'Tanggal', 'Waktu', 'Nama Pelanggan', 'Kota', 'Metode Pembayaran', 'Total Belanja (Rp)']);

    if ($db) {
        $stmt = $db->query("
            SELECT t.id_transaksi, t.no_faktur, t.tanggal, t.waktu, p.nama_pelanggan, p.kota, t.metode_pembayaran, t.total_belanja
            FROM tbl_transaksi t
            LEFT JOIN tbl_pelanggan p ON t.id_pelanggan = p.id_pelanggan
            ORDER BY t.id_transaksi DESC
        ");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id_transaksi'],
                ($row['no_faktur'] ?? '') ?: ('TRX-' . $row['id_transaksi']),
                $row['tanggal'] ?? '',
                $row['waktu'] ?? '-',
                $row['nama_pelanggan'] ?? 'Pelanggan Umum',
                $row['kota'] ?? '-',
                $row['metode_pembayaran'] ?? 'Tunai',
                $row['total_belanja'] ?? 0
            ]);
        }
    }
    fclose($output);
    exit;
}

$transactions = [];
$totalOmset = 0;
$totalTransaksi = 0;

if ($db) {
    try {
        $query = "
            SELECT t.*, p.nama_pelanggan, p.kota
            FROM tbl_transaksi t
            LEFT JOIN tbl_pelanggan p ON t.id_pelanggan = p.id_pelanggan
            WHERE 1=1
        ";
        $params = [];

        if ($startDate !== '') {
            $query .= " AND t.tanggal >= ?";
            $params[] = $startDate;
        }

        if ($endDate !== '') {
            $query .= " AND t.tanggal <= ?";
            $params[] = $endDate;
        }

        if ($metode !== '') {
            $query .= " AND COALESCE(t.metode_pembayaran, 'Tunai') = ?";
            $params[] = $metode;
        }

        if ($search !== '') {
            $query .= " AND (t.no_faktur LIKE ? OR p.nama_pelanggan LIKE ? OR p.kota LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY t.id_transaksi DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        foreach ($transactions as $t) {
            $totalOmset += (float)($t['total_belanja'] ?? 0);
            $totalTransaksi++;
        }
    } catch (Exception $e) {}
}

require_once __DIR__ . '/views/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary-orange"></i> Riwayat Transaksi &amp; Struk
            </h4>
            <p class="text-muted small mb-0">Daftar transaksi penjualan kasir dan cetak ulang struk pembayaran.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="transaksi.php?export=csv" class="btn btn-outline-success rounded-pill px-3 fw-bold small">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </a>
            <a href="pos.php" class="btn topbar-btn-orange rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-cart-plus-fill me-1"></i> Buka Kasir (POS)
            </a>
        </div>
    </div>

    <!-- Summary Highlights -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="sub-stat-card">
                <span class="text-muted small">Total Transaksi</span>
                <h3 class="fw-bold text-dark mb-0 font-mono mt-1"><?= number_format($totalTransaksi, 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sub-stat-card">
                <span class="text-muted small">Total Pendapatan (Omset)</span>
                <h3 class="fw-bold text-success mb-0 font-mono mt-1"><?= formatRupiah($totalOmset) ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sub-stat-card">
                <span class="text-muted small">Rata-rata per Transaksi</span>
                <h3 class="fw-bold font-mono mt-1" style="color: #ff7849;">
                    <?= $totalTransaksi > 0 ? formatRupiah($totalOmset / $totalTransaksi) : 'Rp 0' ?>
                </h3>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sub-stat-card">
                <span class="text-muted small">Status Periode</span>
                <h6 class="fw-bold text-dark mb-0 mt-2"><?= $startDate ? "$startDate s/d $endDate" : 'Semua Periode' ?></h6>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="retail-card mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Cari Faktur / Pelanggan</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik No Faktur / Pelanggan..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Tanggal Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Metode Bayar</label>
                <select name="metode" class="form-select form-select-sm">
                    <option value="">-- Semua Metode --</option>
                    <option value="Tunai" <?= $metode === 'Tunai' ? 'selected' : '' ?>>Tunai</option>
                    <option value="QRIS" <?= $metode === 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                    <option value="Transfer Bank" <?= $metode === 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank</option>
                    <option value="Debit" <?= $metode === 'Debit' ? 'selected' : '' ?>>Debit</option>
                    <option value="Saldo Member" <?= $metode === 'Saldo Member' ? 'selected' : '' ?>>Saldo Member</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2 mt-3 mt-md-0">
                <button type="submit" class="btn btn-sm topbar-btn-orange flex-fill rounded-pill py-2">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                </button>
                <a href="transaksi.php" class="btn btn-sm btn-outline-secondary rounded-pill py-2 px-3" title="Reset Filter">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Transaksi -->
    <div class="retail-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-secondary text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3" style="width: 140px;">No Faktur</th>
                        <th>Waktu Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Metode Bayar</th>
                        <th>Kasir</th>
                        <th class="text-end">Total Belanja</th>
                        <th class="text-center pe-3" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                                Tidak ada data transaksi yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): 
                            $metodeItem = $t['metode_pembayaran'] ?? 'Tunai';
                            $badgeColor = 'bg-secondary';
                            if ($metodeItem === 'Tunai') $badgeColor = 'bg-success text-white';
                            elseif ($metodeItem === 'QRIS') $badgeColor = 'bg-primary text-white';
                            elseif ($metodeItem === 'Transfer Bank') $badgeColor = 'bg-warning text-dark';
                            elseif ($metodeItem === 'Debit') $badgeColor = 'bg-info text-white';
                            elseif ($metodeItem === 'Saldo Member') $badgeColor = 'bg-warning text-dark';

                            $noFaktur = ($t['no_faktur'] ?? '') ?: ('TRX-' . str_pad($t['id_transaksi'], 4, '0', STR_PAD_LEFT));
                            $waktuTrx = $t['waktu'] ?? '-';
                            $namaCust = $t['nama_pelanggan'] ?? 'Pelanggan Umum';
                            $kotaCust = $t['kota'] ?? '-';
                            $kasirName = $t['kasir'] ?? 'Kasir 1';
                        ?>
                            <tr>
                                <td class="ps-3 font-mono fw-semibold small text-primary-orange">
                                    <?= htmlspecialchars($noFaktur) ?>
                                </td>
                                <td>
                                    <div class="text-dark small fw-semibold"><?= formatTanggalIndo($t['tanggal'] ?? '') ?></div>
                                    <div class="text-muted small font-mono"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($waktuTrx) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($namaCust) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($kotaCust) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeColor ?> px-2 py-1 rounded-pill small">
                                        <?= htmlspecialchars($metodeItem) ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($kasirName) ?>
                                </td>
                                <td class="text-end font-mono fw-bold text-dark fs-6">
                                    <?= formatRupiah($t['total_belanja'] ?? 0) ?>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- CETAK STRUK BUTTON -->
                                        <button type="button" class="btn btn-outline-primary" title="Cetak Struk" onclick="loadAndPrintReceipt(<?= $t['id_transaksi'] ?>)">
                                            <i class="bi bi-printer-fill me-1"></i> Struk
                                        </button>
                                        <!-- Detail Items trigger -->
                                        <button type="button" class="btn btn-outline-info" title="Lihat Rincian Item" onclick="loadAndPrintReceipt(<?= $t['id_transaksi'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
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

<!-- Receipt Modal Inclusion -->
<?php require_once __DIR__ . '/views/receipt_modal.php'; ?>
<?php require_once __DIR__ . '/views/footer.php'; ?>
