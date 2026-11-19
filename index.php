<?php
/**
 * AGSmart POS Dashboard
 */
require_once __DIR__ . '/config/helper.php';
requireAdmin();
$pageTitle = 'Dashboard';
require_once __DIR__ . '/views/header.php';

$db = getDB();
$todaySales = 0;
$todayOrders = 0;
$customerCount = 0;
$productCount = 0;
$lowStockCount = 0;
$lowStockProduct = null;
$recentTransactions = [];
$monthKeys = [];
$monthlySales = [];

$currentMonth = new DateTimeImmutable('first day of this month');
for ($offset = 5; $offset >= 0; $offset--) {
    $month = $currentMonth->modify("-{$offset} months");
    $monthKeys[] = $month->format('Y-m');
    $monthlySales[] = 0;
}

if ($db) {
    try {
        $today = $db->query("
            SELECT COALESCE(SUM(total_belanja), 0) AS total,
                   COUNT(*) AS jumlah
            FROM tbl_transaksi
            WHERE tanggal = CURDATE()
        ")->fetch();
        $todaySales = (float)($today['total'] ?? 0);
        $todayOrders = (int)($today['jumlah'] ?? 0);

        $customerCount = (int)$db->query("SELECT COUNT(*) FROM tbl_pelanggan")->fetchColumn();

        $stock = $db->query("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN stok <= stok_minimal THEN 1 ELSE 0 END), 0) AS rendah
            FROM tbl_produk
        ")->fetch();
        $productCount = (int)($stock['total'] ?? 0);
        $lowStockCount = (int)($stock['rendah'] ?? 0);

        if ($lowStockCount > 0) {
            $lowStockProduct = $db->query("
                SELECT nama_produk
                FROM tbl_produk
                WHERE stok <= stok_minimal
                ORDER BY stok ASC
                LIMIT 1
            ")->fetchColumn();
        }

        $monthStart = $currentMonth->modify('-5 months')->format('Y-m-01');
        $nextMonth = $currentMonth->modify('+1 month')->format('Y-m-01');
        $salesByMonth = $db->prepare("
            SELECT DATE_FORMAT(tanggal, '%Y-%m') AS periode,
                   COALESCE(SUM(total_belanja), 0) AS total
            FROM tbl_transaksi
            WHERE tanggal >= ? AND tanggal < ?
            GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
        ");
        $salesByMonth->execute([$monthStart, $nextMonth]);
        $salesByMonth = $salesByMonth->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($monthKeys as $index => $monthKey) {
            $monthlySales[$index] = (float)($salesByMonth[$monthKey] ?? 0);
        }

        $recentTransactions = $db->query("
            SELECT t.id_transaksi, t.no_faktur, t.tanggal, t.waktu,
                   t.total_belanja, t.metode_pembayaran, p.nama_pelanggan
            FROM tbl_transaksi t
            LEFT JOIN tbl_pelanggan p ON p.id_pelanggan = t.id_pelanggan
            ORDER BY t.tanggal DESC, t.waktu DESC, t.id_transaksi DESC
            LIMIT 6
        ")->fetchAll();
    } catch (Throwable $e) {}
}

$dashboardChartData = [
    'months' => $monthKeys,
    'sales' => $monthlySales,
];
?>

<main class="dashboard-page container-fluid px-3 px-xl-4 py-4" id="dashboard">
    <section class="dashboard-hero mb-4">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow" data-i18n="store_overview">RINGKASAN TOKO</span>
            <h1><span data-i18n="welcome">Selamat datang kembali,</span> <strong>Admin</strong></h1>
            <p data-i18n="hero_description">Pantau penjualan, pelanggan, dan persediaan toko Anda dalam satu tempat.</p>
        </div>
        <div class="dashboard-hero-actions">
            <div class="dashboard-language-switch" role="group" aria-label="Pilih bahasa" data-i18n-aria-label="choose_language">
                <button type="button" class="active" data-dashboard-language="id" aria-pressed="true" onclick="setDashboardLanguage('id')">ID</button>
                <button type="button" data-dashboard-language="en" aria-pressed="false" onclick="setDashboardLanguage('en')">EN</button>
            </div>
            <div class="dashboard-date">
                <i class="bi bi-calendar3"></i>
                <time id="dashboardDate" datetime="<?= date('Y-m-d') ?>"><?= date('d/m/Y') ?></time>
            </div>
            <a href="pos.php" class="dashboard-hero-pos">
                <i class="bi bi-cart-plus"></i>
                <span data-i18n="new_sale">Transaksi baru</span>
            </a>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Ringkasan hari ini" data-i18n-aria-label="today_summary">
        <div class="col-sm-6 col-xl-3">
            <article class="dashboard-kpi dashboard-kpi-sales h-100">
                <div class="dashboard-kpi-top">
                    <span class="dashboard-kpi-icon"><i class="bi bi-wallet2"></i></span>
                    <span class="dashboard-kpi-caption" data-i18n="today">HARI INI</span>
                </div>
                <p data-i18n="sales_today">Penjualan hari ini</p>
                <strong class="dashboard-kpi-value" data-dashboard-currency="<?= $todaySales ?>"><?= formatRupiah($todaySales) ?></strong>
                <span class="dashboard-kpi-foot"><i class="bi bi-graph-up-arrow"></i> <span data-i18n="sales_total_hint">Total dari transaksi tercatat</span></span>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="dashboard-kpi dashboard-kpi-orders h-100">
                <div class="dashboard-kpi-top">
                    <span class="dashboard-kpi-icon"><i class="bi bi-receipt"></i></span>
                    <span class="dashboard-kpi-caption" data-i18n="today">HARI INI</span>
                </div>
                <p data-i18n="orders_today">Transaksi hari ini</p>
                <strong class="dashboard-kpi-value" data-dashboard-number="<?= $todayOrders ?>"><?= number_format($todayOrders, 0, ',', '.') ?></strong>
                <span class="dashboard-kpi-foot"><i class="bi bi-check2-circle"></i> <span data-i18n="orders_hint">Transaksi penjualan berhasil</span></span>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="dashboard-kpi dashboard-kpi-customers h-100">
                <div class="dashboard-kpi-top">
                    <span class="dashboard-kpi-icon"><i class="bi bi-people"></i></span>
                    <span class="dashboard-kpi-caption" data-i18n="all_time">TOTAL</span>
                </div>
                <p data-i18n="customers">Pelanggan terdaftar</p>
                <strong class="dashboard-kpi-value" data-dashboard-number="<?= $customerCount ?>"><?= number_format($customerCount, 0, ',', '.') ?></strong>
                <span class="dashboard-kpi-foot"><i class="bi bi-person-check"></i> <span data-i18n="customers_hint">Data pelanggan toko</span></span>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="dashboard-kpi dashboard-kpi-stock h-100">
                <div class="dashboard-kpi-top">
                    <span class="dashboard-kpi-icon"><i class="bi bi-box-seam"></i></span>
                    <span class="dashboard-kpi-caption" data-i18n="inventory">INVENTARIS</span>
                </div>
                <p data-i18n="low_stock">Stok menipis</p>
                <strong class="dashboard-kpi-value" data-dashboard-number="<?= $lowStockCount ?>"><?= number_format($lowStockCount, 0, ',', '.') ?></strong>
                <span class="dashboard-kpi-foot"><i class="bi bi-boxes"></i> <span data-dashboard-number="<?= $productCount ?>"><?= number_format($productCount, 0, ',', '.') ?></span> <span data-i18n="products_total">produk terdaftar</span></span>
            </article>
        </div>
    </section>

    <?php if ($lowStockCount > 0): ?>
        <div class="dashboard-stock-alert mb-4">
            <span class="dashboard-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="dashboard-alert-copy">
                <strong data-i18n="stock_alert_title">Perlu perhatian pada stok</strong>
                <span>
                    <b data-dashboard-number="<?= $lowStockCount ?>"><?= number_format($lowStockCount, 0, ',', '.') ?></b>
                    <span data-i18n="stock_alert_text">produk berada di bawah batas stok minimum.</span>
                    <?php if ($lowStockProduct): ?>
                        <span class="dashboard-alert-product"><?= htmlspecialchars($lowStockProduct, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <a href="produk.php?filter_stok=menipis" class="dashboard-alert-link">
                <span data-i18n="check_stock">Periksa stok</span> <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    <?php endif; ?>

    <section class="row g-3 mb-4">
        <div class="col-xl-8">
            <article class="dashboard-panel h-100">
                <div class="dashboard-panel-heading">
                    <div>
                        <span class="dashboard-panel-kicker" data-i18n="sales_analytics">ANALISIS PENJUALAN</span>
                        <h2 data-i18n="sales_trend">Tren penjualan</h2>
                        <p data-i18n="sales_chart_hint">Total penjualan per bulan selama enam bulan terakhir.</p>
                    </div>
                    <span class="dashboard-panel-chip"><i class="bi bi-calendar2-week"></i> <span data-i18n="last_six_months">6 bulan terakhir</span></span>
                </div>
                <div class="dashboard-chart-wrap">
                    <canvas id="dashboardSalesChart" role="img" aria-label="Grafik penjualan enam bulan terakhir" data-i18n-aria-label="sales_chart_label"></canvas>
                </div>
            </article>
        </div>

        <div class="col-xl-4">
            <article class="dashboard-panel dashboard-inventory-panel h-100">
                <div class="dashboard-panel-heading">
                    <div>
                        <span class="dashboard-panel-kicker" data-i18n="inventory_overview">RINGKASAN INVENTARIS</span>
                        <h2 data-i18n="stock_health">Kondisi stok</h2>
                    </div>
                    <span class="dashboard-inventory-icon"><i class="bi bi-boxes"></i></span>
                </div>
                <div class="dashboard-stock-summary">
                    <div class="dashboard-stock-count">
                        <strong data-dashboard-number="<?= $productCount ?>"><?= number_format($productCount, 0, ',', '.') ?></strong>
                        <span data-i18n="products_total">produk terdaftar</span>
                    </div>
                    <div class="dashboard-stock-count dashboard-stock-count-alert">
                        <strong data-dashboard-number="<?= $lowStockCount ?>"><?= number_format($lowStockCount, 0, ',', '.') ?></strong>
                        <span data-i18n="products_low">stok perlu ditambah</span>
                    </div>
                </div>
                <div class="dashboard-stock-progress" role="progressbar" aria-valuenow="<?= $productCount > 0 ? (int)round(100 * ($productCount - $lowStockCount) / $productCount) : 100 ?>" aria-valuemin="0" aria-valuemax="100">
                    <?php $healthyStockPercent = $productCount > 0 ? max(0, min(100, (int)round(100 * ($productCount - $lowStockCount) / $productCount))) : 100; ?>
                    <span style="width: <?= $healthyStockPercent ?>%"></span>
                </div>
                <div class="dashboard-stock-progress-label">
                    <span data-i18n="stock_ready">Stok aman</span>
                    <strong><?= $healthyStockPercent ?>%</strong>
                </div>
                <a href="produk.php?filter_stok=menipis" class="dashboard-panel-action">
                    <span data-i18n="manage_inventory">Kelola inventaris</span><i class="bi bi-arrow-up-right"></i>
                </a>
            </article>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-xl-8">
            <article class="dashboard-panel">
                <div class="dashboard-panel-heading dashboard-transactions-heading">
                    <div>
                        <span class="dashboard-panel-kicker" data-i18n="latest_activity">AKTIVITAS TERBARU</span>
                        <h2 data-i18n="recent_transactions">Transaksi terbaru</h2>
                    </div>
                    <a href="transaksi.php" class="dashboard-panel-action dashboard-view-all">
                        <span data-i18n="view_all">Lihat semua</span><i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive dashboard-table-wrap">
                    <table class="table dashboard-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th data-i18n="invoice">No. Faktur</th>
                                <th data-i18n="customer">Pelanggan</th>
                                <th data-i18n="date">Tanggal</th>
                                <th data-i18n="payment">Pembayaran</th>
                                <th class="text-end" data-i18n="amount">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$recentTransactions): ?>
                                <tr><td colspan="5" class="dashboard-empty" data-i18n="no_transactions">Belum ada transaksi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td class="dashboard-invoice"><?= htmlspecialchars($transaction['no_faktur'] ?: 'TRX-' . $transaction['id_transaksi'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?php if (!empty($transaction['nama_pelanggan'])): ?><?= htmlspecialchars($transaction['nama_pelanggan'], ENT_QUOTES, 'UTF-8') ?><?php else: ?><span data-i18n="general_customer">Pelanggan umum</span><?php endif; ?></td>
                                        <td><time class="dashboard-transaction-date" datetime="<?= htmlspecialchars($transaction['tanggal'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= !empty($transaction['tanggal']) ? date('d/m/Y', strtotime($transaction['tanggal'])) : '-' ?></time></td>
                                        <td><span class="dashboard-payment"><?= htmlspecialchars($transaction['metode_pembayaran'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-end dashboard-amount" data-dashboard-currency="<?= (float)$transaction['total_belanja'] ?>"><?= formatRupiah($transaction['total_belanja']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
        <div class="col-xl-4">
            <article class="dashboard-panel dashboard-quick-panel h-100">
                <div class="dashboard-panel-heading">
                    <div>
                        <span class="dashboard-panel-kicker" data-i18n="shortcuts">AKSES CEPAT</span>
                        <h2 data-i18n="quick_actions">Aksi cepat</h2>
                    </div>
                </div>
                <a href="pos.php" class="dashboard-quick-action">
                    <span class="dashboard-quick-icon quick-icon-orange"><i class="bi bi-calculator"></i></span>
                    <span><strong data-i18n="open_pos">Buka kasir</strong><small data-i18n="open_pos_hint">Mulai transaksi penjualan</small></span>
                    <i class="bi bi-arrow-up-right"></i>
                </a>
                <a href="produk.php" class="dashboard-quick-action">
                    <span class="dashboard-quick-icon quick-icon-blue"><i class="bi bi-box-seam"></i></span>
                    <span><strong data-i18n="manage_products">Kelola produk</strong><small data-i18n="manage_products_hint">Lihat produk dan persediaan</small></span>
                    <i class="bi bi-arrow-up-right"></i>
                </a>
                <a href="laporan.php" class="dashboard-quick-action">
                    <span class="dashboard-quick-icon quick-icon-green"><i class="bi bi-bar-chart-line"></i></span>
                    <span><strong data-i18n="view_reports">Lihat laporan</strong><small data-i18n="view_reports_hint">Tinjau performa penjualan</small></span>
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </article>
        </div>
    </section>

</main>

<script>
window.dashboardData = <?= json_encode($dashboardChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
