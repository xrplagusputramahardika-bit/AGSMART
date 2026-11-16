<?php
/**
 * Analytics & Sales Reports Dashboard
 * AGSmart POS System (Retaillet Theme)
 */
$pageTitle = 'Laporan & Analitik';
require_once __DIR__ . '/config/helper.php';

$db = getDB();

$selectedBulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selectedTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$totalOmset = 0;
$totalTransaksi = 0;
$topProducts = [];
$topCustomers = [];
$methodBreakdown = [];
$profitTotal = 0;

if ($db) {
    try {
        // Omset in current filter
        $stmtOmset = $db->prepare("
            SELECT COUNT(*) as jml, COALESCE(SUM(total_belanja), 0) as omset
            FROM tbl_transaksi
            WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
        ");
        $stmtOmset->execute([$selectedBulan, $selectedTahun]);
        $omsetData = $stmtOmset->fetch();
        $totalTransaksi = $omsetData['jml'] ?? 0;
        $totalOmset = $omsetData['omset'] ?? 0;

        // Best selling products (via v_produk_terlaris or direct join)
        $stmtTop = $db->query("
            SELECT p.nama_produk, p.kode_produk, k.nama_kategori,
                   COALESCE(SUM(d.qty), 0) as total_qty,
                   COALESCE(SUM(d.subtotal), 0) as total_omset
            FROM tbl_produk p
            LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori
            LEFT JOIN tbl_detail_transaksi d ON p.id_produk = d.id_produk
            GROUP BY p.id_produk, p.nama_produk, p.kode_produk, k.nama_kategori
            ORDER BY total_qty DESC
            LIMIT 7
        ");
        $topProducts = $stmtTop->fetchAll();

        // Payment method breakdown
        $stmtMethods = $db->query("
            SELECT COALESCE(metode_pembayaran, 'Tunai') as metode,
                   COUNT(*) as jml,
                   COALESCE(SUM(total_belanja), 0) as total
            FROM tbl_transaksi
            GROUP BY COALESCE(metode_pembayaran, 'Tunai')
            ORDER BY total DESC
        ");
        $methodBreakdown = $stmtMethods->fetchAll();

        // Top spending customers (from v_total_belanja_pelanggan)
        $stmtCust = $db->query("
            SELECT * FROM v_total_belanja_pelanggan
            ORDER BY total_keseluruhan_belanja DESC
            LIMIT 5
        ");
        $topCustomers = $stmtCust->fetchAll();

        // Estimated profit
        $stmtProfit = $db->query("
            SELECT COALESCE(SUM((d.harga_satuan - p.harga_beli) * d.qty), 0) as profit
            FROM tbl_detail_transaksi d
            JOIN tbl_produk p ON d.id_produk = p.id_produk
        ");
        $profitTotal = (float)$stmtProfit->fetchColumn();

    } catch (Exception $e) {}
}

$namaBulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

require_once __DIR__ . '/views/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-graph-up-arrow text-primary-orange"></i> Laporan &amp; Analitik Penjualan
            </h4>
            <p class="text-muted small mb-0">Statistik performa penjualan toko, produk terlaris, dan pelanggan teraktif.</p>
        </div>
        <form method="GET" class="d-flex gap-2">
            <select name="bulan" class="form-select form-select-sm bg-white border">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $selectedBulan == $m ? 'selected' : '' ?>><?= $namaBulan[$m] ?></option>
                <?php endfor; ?>
            </select>
            <select name="tahun" class="form-select form-select-sm bg-white border">
                <option value="2026" selected>2026</option>
                <option value="2025">2025</option>
            </select>
            <button type="submit" class="btn btn-sm topbar-btn-orange rounded-pill px-3">Tampilkan</button>
        </form>
    </div>

    <!-- Highlight Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="retail-card p-3 border-start border-4 border-warning">
                <span class="text-muted small">Omset Bulan Ini</span>
                <h3 class="fw-bold text-dark mb-0 font-mono mt-1"><?= formatRupiah($totalOmset) ?></h3>
                <span class="small text-muted mt-2 d-block"><?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?></span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="retail-card p-3 border-start border-4 border-success">
                <span class="text-muted small">Total Transaksi Selesai</span>
                <h3 class="fw-bold text-success mb-0 font-mono mt-1"><?= number_format($totalTransaksi, 0, ',', '.') ?></h3>
                <span class="small text-muted mt-2 d-block">Struk tercetak</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="retail-card p-3 border-start border-4 border-info">
                <span class="text-muted small">Estimasi Profit Kotor</span>
                <h3 class="fw-bold text-info mb-0 font-mono mt-1"><?= formatRupiah($profitTotal) ?></h3>
                <span class="small text-muted mt-2 d-block">Selisih harga jual - beli</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="retail-card p-3 border-start border-4" style="border-left-color: #ff7849 !important;">
                <span class="text-muted small">Rata-rata Keranjang (AOV)</span>
                <h3 class="fw-bold font-mono mt-1" style="color: #ff7849;">
                    <?= $totalTransaksi > 0 ? formatRupiah($totalOmset / $totalTransaksi) : 'Rp 0' ?>
                </h3>
                <span class="small text-muted mt-2 d-block">Nilai rata-rata per struk</span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Produk Terlaris -->
        <div class="col-lg-7">
            <div class="retail-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-fire text-danger"></i> 7 Produk Paling Laris
                    </h6>
                    <span class="badge bg-light text-muted border small">Terjual Paling Banyak</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-secondary text-muted small text-uppercase">
                            <tr>
                                <th class="ps-2">Nama Produk</th>
                                <th>Kategori</th>
                                <th class="text-center">Qty Terjual</th>
                                <th class="text-end pe-2">Kontribusi Omset</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada data transaksi produk.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $tp): ?>
                                    <tr>
                                        <td class="ps-2">
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($tp['nama_produk'] ?? '') ?></div>
                                            <span class="font-mono text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars($tp['kode_produk'] ?? '') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-muted border small">
                                                <?= htmlspecialchars($tp['nama_kategori'] ?? 'Umum') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-2 py-1 fw-bold">
                                                <?= $tp['total_qty'] ?> pcs
                                            </span>
                                        </td>
                                        <td class="text-end pe-2 font-mono fw-bold text-dark small">
                                            <?= formatRupiah($tp['total_omset']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Metode Pembayaran & Pelanggan Loyal -->
        <div class="col-lg-5">
            <!-- Breakdown Metode Pembayaran -->
            <div class="retail-card mb-3">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-wallet-fill text-warning"></i> Metode Pembayaran Digunakan
                </h6>
                <div class="list-group list-group-flush bg-transparent">
                    <?php if (empty($methodBreakdown)): ?>
                        <p class="text-muted small mb-0">Belum ada transaksi.</p>
                    <?php else: ?>
                        <?php foreach ($methodBreakdown as $mb): 
                            $pct = $totalOmset > 0 ? round(($mb['total'] / $totalOmset) * 100) : 0;
                        ?>
                            <div class="list-group-item bg-transparent border-bottom px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-dark small"><?= htmlspecialchars($mb['metode'] ?? 'Tunai') ?> (<?= $mb['jml'] ?>x)</span>
                                    <span class="font-mono small fw-bold text-primary-orange"><?= formatRupiah($mb['total']) ?></span>
                                </div>
                                <div class="progress bg-light" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%; background-color: #ff7849;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5 Pelanggan Paling Banyak Belanja -->
            <div class="retail-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-trophy-fill text-warning"></i> Top 5 Pelanggan
                    </h6>
                    <a href="pelanggan.php" class="small text-decoration-none" style="color: #ff7849;">Semua &rarr;</a>
                </div>
                <div class="list-group list-group-flush bg-transparent">
                    <?php if (empty($topCustomers)): ?>
                        <p class="text-muted small mb-0">Belum ada data pelanggan.</p>
                    <?php else: ?>
                        <?php foreach ($topCustomers as $rank => $tc): ?>
                            <div class="list-group-item bg-transparent border-bottom px-0 py-2 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">
                                        <?= $rank + 1 ?>
                                    </span>
                                    <div>
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($tc['nama_pelanggan']) ?></div>
                                        <span class="text-muted small"><?= htmlspecialchars($tc['kota']) ?> &bull; <?= $tc['jumlah_transaksi'] ?>x transaksi</span>
                                    </div>
                                </div>
                                <span class="font-mono fw-bold text-success small"><?= formatRupiah($tc['total_keseluruhan_belanja']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
