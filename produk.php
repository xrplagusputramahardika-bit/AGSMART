<?php
/**
 * Product Management (CRUD & Stock Monitoring)
 * AGSmart POS System
 */
$pageTitle = 'Manajemen Produk & Stok';
require_once __DIR__ . '/config/helper.php';

$db = getDB();

// Handle Actions (Add, Edit, Delete, Quick Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $kode = cleanInput($_POST['kode_produk']);
        $nama = cleanInput($_POST['nama_produk']);
        $idKategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
        $satuan = cleanInput($_POST['satuan'] ?? 'pcs');
        $hargaBeli = (float)$_POST['harga_beli'];
        $hargaJual = (float)$_POST['harga_jual'];
        $stok = (int)$_POST['stok'];
        $stokMin = (int)($_POST['stok_minimal'] ?? 5);

        try {
            $stmt = $db->prepare("
                INSERT INTO tbl_produk (kode_produk, nama_produk, id_kategori, satuan, harga_beli, harga_jual, stok, stok_minimal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kode, $nama, $idKategori, $satuan, $hargaBeli, $hargaJual, $stok, $stokMin]);
            setFlash('success', "Produk <b>$nama</b> berhasil ditambahkan!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal menambah produk: " . $e->getMessage());
        }
        header("Location: produk.php");
        exit;
    }

    if ($action === 'update') {
        $id = (int)$_POST['id_produk'];
        $kode = cleanInput($_POST['kode_produk']);
        $nama = cleanInput($_POST['nama_produk']);
        $idKategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
        $satuan = cleanInput($_POST['satuan'] ?? 'pcs');
        $hargaBeli = (float)$_POST['harga_beli'];
        $hargaJual = (float)$_POST['harga_jual'];
        $stok = (int)$_POST['stok'];
        $stokMin = (int)($_POST['stok_minimal'] ?? 5);

        try {
            $stmt = $db->prepare("
                UPDATE tbl_produk 
                SET kode_produk = ?, nama_produk = ?, id_kategori = ?, satuan = ?, 
                    harga_beli = ?, harga_jual = ?, stok = ?, stok_minimal = ?
                WHERE id_produk = ?
            ");
            $stmt->execute([$kode, $nama, $idKategori, $satuan, $hargaBeli, $hargaJual, $stok, $stokMin, $id]);
            setFlash('success', "Produk <b>$nama</b> berhasil diperbarui!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal memperbarui produk: " . $e->getMessage());
        }
        header("Location: produk.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id_produk'];
        try {
            $stmt = $db->prepare("DELETE FROM tbl_produk WHERE id_produk = ?");
            $stmt->execute([$id]);
            setFlash('success', "Produk berhasil dihapus!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal menghapus produk: " . $e->getMessage());
        }
        header("Location: produk.php");
        exit;
    }

    if ($action === 'quick_stock') {
        $id = (int)$_POST['id_produk'];
        $tambah = (int)$_POST['tambah_stok'];
        try {
            $stmt = $db->prepare("UPDATE tbl_produk SET stok = GREATEST(0, stok + ?) WHERE id_produk = ?");
            $stmt->execute([$tambah, $id]);
            setFlash('success', "Stok produk berhasil diperbarui!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal mengupdate stok: " . $e->getMessage());
        }
        header("Location: produk.php");
        exit;
    }
}

// Fetch products & categories
$categories = [];
$products = [];
$filterCategory = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$filterStock = isset($_GET['filter_stok']) ? $_GET['filter_stok'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($db) {
    try {
        $categories = $db->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori ASC")->fetchAll();

        $query = "
            SELECT p.*, k.nama_kategori 
            FROM tbl_produk p 
            LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori 
            WHERE 1=1
        ";
        $params = [];

        if ($filterCategory > 0) {
            $query .= " AND p.id_kategori = ?";
            $params[] = $filterCategory;
        }

        if ($filterStock === 'habis') {
            $query .= " AND p.stok <= 0";
        } elseif ($filterStock === 'menipis') {
            $query .= " AND p.stok > 0 AND p.stok <= p.stok_minimal";
        }

        if ($search !== '') {
            $query .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY p.id_produk DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/views/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary"></i> Data Produk &amp; Stok
            </h4>
            <p class="text-slate-400 small mb-0">Kelola katalog produk, harga jual/beli, dan pantau ketersediaan stok toko.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddProduct">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Produk Baru
            </button>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-slate-900 border-slate-700 text-slate-400"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-slate-900 border-slate-700 text-light" placeholder="Cari nama atau kode barcode..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="kategori" class="form-select form-select-sm bg-slate-900 border-slate-700 text-light" onchange="this.form.submit()">
                    <option value="0">-- Semua Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id_kategori'] ?>" <?= $filterCategory == $cat['id_kategori'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="filter_stok" class="form-select form-select-sm bg-slate-900 border-slate-700 text-light" onchange="this.form.submit()">
                    <option value="all" <?= $filterStock === 'all' ? 'selected' : '' ?>>-- Semua Status Stok --</option>
                    <option value="menipis" <?= $filterStock === 'menipis' ? 'selected' : '' ?>>⚠️ Stok Menipis (&le; Minimal)</option>
                    <option value="habis" <?= $filterStock === 'habis' ? 'selected' : '' ?>>🛑 Stok Habis (0)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill rounded-pill">Filter</button>
                <a href="produk.php" class="btn btn-sm btn-outline-secondary rounded-pill" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="card card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead class="table-secondary text-slate-400 small text-uppercase">
                    <tr>
                        <th class="ps-3" style="width: 120px;">Barcode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Profit</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-slate-500">
                                <i class="bi bi-box2 fs-1 d-block mb-2"></i>
                                Tidak ada data produk yang sesuai filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $profit = $p['harga_jual'] - $p['harga_beli'];
                            $isOut = ($p['stok'] <= 0);
                            $isLow = ($p['stok'] > 0 && $p['stok'] <= $p['stok_minimal']);
                        ?>
                            <tr>
                                <td class="ps-3 font-mono text-primary small fw-semibold"><?= htmlspecialchars($p['kode_produk']) ?></td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                    <div class="small text-slate-400">Satuan: <?= htmlspecialchars($p['satuan']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-slate-900 border border-slate-700 text-slate-300">
                                        <?= htmlspecialchars($p['nama_kategori'] ?: 'Tanpa Kategori') ?>
                                    </span>
                                </td>
                                <td class="font-mono text-slate-400 small"><?= formatRupiah($p['harga_beli']) ?></td>
                                <td class="font-mono fw-bold text-white"><?= formatRupiah($p['harga_jual']) ?></td>
                                <td class="font-mono text-success small">+<?= formatRupiah($profit) ?></td>
                                <td class="text-center">
                                    <?php if ($isOut): ?>
                                        <span class="badge bg-danger">Habis (0)</span>
                                    <?php elseif ($isLow): ?>
                                        <span class="badge bg-warning text-dark">Menipis (<?= $p['stok'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">
                                            <?= $p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Quick Stock Modal Trigger -->
                                        <button type="button" class="btn btn-outline-info" title="Tambah Stok" onclick="openQuickStock(<?= $p['id_produk'] ?>, '<?= addslashes(htmlspecialchars($p['nama_produk'])) ?>', <?= $p['stok'] ?>)">
                                            <i class="bi bi-plus-slash-minus"></i>
                                        </button>
                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-warning" title="Edit" onclick="openEditProduct(<?= htmlspecialchars(json_encode($p)) ?>)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Delete Form -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus produk ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
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

<!-- MODAL TAMBAH PRODUK -->
<div class="modal fade" id="modalAddProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-plus-circle text-primary me-2"></i> Tambah Produk Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Kode Barcode / SKU *</label>
                            <div class="input-group">
                                <input type="text" name="kode_produk" id="addKodeProduk" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required autocomplete="off" placeholder="Scan atau ketik kode barcode">
                                <button type="button" class="btn btn-outline-primary" onclick="focusProductBarcode('addKodeProduk')" title="Scan dengan scanner barcode">
                                    <i class="bi bi-upc-scan me-1"></i> Scan
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="generateBarcode()">Auto</button>
                            </div>
                            <small class="text-muted">Klik Scan, lalu arahkan scanner USB/Bluetooth ke barcode.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Kategori</label>
                            <select name="id_kategori" class="form-select bg-slate-900 border-slate-700 text-light">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_kategori'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small text-slate-300">Nama Produk *</label>
                            <input type="text" name="nama_produk" class="form-control bg-slate-900 border-slate-700 text-light" required placeholder="Contoh: Minyak Goreng Bimoli 2 Liter">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-slate-300">Satuan *</label>
                            <input type="text" name="satuan" class="form-control bg-slate-900 border-slate-700 text-light" value="pcs" placeholder="pcs, botol, kg, sak, dll">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Harga Beli (Modal Rp) *</label>
                            <input type="number" name="harga_beli" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Harga Jual (Kasir Rp) *</label>
                            <input type="number" name="harga_jual" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Stok Awal *</label>
                            <input type="number" name="stok" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="10" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Peringatan Stok Minimal</label>
                            <input type="number" name="stok_minimal" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-slate-700">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT PRODUK -->
<div class="modal fade" id="modalEditProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Data Produk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_produk" id="editIdProduk">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Kode Barcode / SKU *</label>
                            <div class="input-group">
                                <input type="text" name="kode_produk" id="editKodeProduk" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required autocomplete="off">
                                <button type="button" class="btn btn-outline-primary" onclick="focusProductBarcode('editKodeProduk')" title="Scan dengan scanner barcode">
                                    <i class="bi bi-upc-scan me-1"></i> Scan
                                </button>
                            </div>
                            <small class="text-muted">Klik Scan, lalu arahkan scanner USB/Bluetooth ke barcode.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Kategori</label>
                            <select name="id_kategori" id="editIdKategori" class="form-select bg-slate-900 border-slate-700 text-light">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_kategori'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small text-slate-300">Nama Produk *</label>
                            <input type="text" name="nama_produk" id="editNamaProduk" class="form-control bg-slate-900 border-slate-700 text-light" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-slate-300">Satuan *</label>
                            <input type="text" name="satuan" id="editSatuan" class="form-control bg-slate-900 border-slate-700 text-light" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Harga Beli (Modal Rp) *</label>
                            <input type="number" name="harga_beli" id="editHargaBeli" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Harga Jual (Kasir Rp) *</label>
                            <input type="number" name="harga_jual" id="editHargaJual" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Stok Saat Ini *</label>
                            <input type="number" name="stok" id="editStok" class="form-control bg-slate-900 border-slate-700 text-light font-mono" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-slate-300">Peringatan Stok Minimal</label>
                            <input type="number" name="stok_minimal" id="editStokMinimal" class="form-control bg-slate-900 border-slate-700 text-light font-mono">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-slate-700">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Perbarui Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL QUICK UPDATE STOK -->
<div class="modal fade" id="modalQuickStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700 py-3">
                <h6 class="modal-title fw-bold text-white"><i class="bi bi-box-arrow-in-down text-info me-1"></i> Sesuaikan Stok</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="quick_stock">
                <input type="hidden" name="id_produk" id="stockIdProduk">
                <div class="modal-body p-3">
                    <p class="small text-slate-300 mb-1" id="stockProductName">Nama Produk</p>
                    <p class="small text-slate-400 mb-3">Stok sekarang: <b id="stockCurrent" class="text-white">0</b></p>
                    
                    <label class="form-label small text-slate-300">Jumlah Penambahan (bisa negatif)</label>
                    <div class="input-group">
                        <input type="number" name="tambah_stok" class="form-control bg-slate-900 border-slate-700 text-light font-mono" value="10" required>
                    </div>
                </div>
                <div class="modal-footer border-slate-700 p-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-info rounded-pill px-3">Update Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function focusProductBarcode(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.focus();
        input.select();
        showToast('Arahkan scanner USB/Bluetooth ke barcode produk', 'info');
    }

    function generateBarcode() {
        const rand = 'BRG-' + Math.floor(100 + Math.random() * 900);
        document.getElementById('addKodeProduk').value = rand;
    }

    function openEditProduct(p) {
        document.getElementById('editIdProduk').value = p.id_produk;
        document.getElementById('editKodeProduk').value = p.kode_produk;
        document.getElementById('editNamaProduk').value = p.nama_produk;
        document.getElementById('editIdKategori').value = p.id_kategori || '';
        document.getElementById('editSatuan').value = p.satuan || 'pcs';
        document.getElementById('editHargaBeli').value = p.harga_beli;
        document.getElementById('editHargaJual').value = p.harga_jual;
        document.getElementById('editStok').value = p.stok;
        document.getElementById('editStokMinimal').value = p.stok_minimal || 5;

        new bootstrap.Modal(document.getElementById('modalEditProduct')).show();
    }

    function openQuickStock(id, name, currentStock) {
        document.getElementById('stockIdProduk').value = id;
        document.getElementById('stockProductName').textContent = name;
        document.getElementById('stockCurrent').textContent = currentStock;

        new bootstrap.Modal(document.getElementById('modalQuickStock')).show();
    }

    document.querySelectorAll('#addKodeProduk, #editKodeProduk').forEach(input => {
        input.addEventListener('keydown', event => {
            if (event.key !== 'Enter') return;

            event.preventDefault();
            event.stopPropagation();
            input.value = input.value.trim();
        });
        input.addEventListener('blur', () => {
            input.value = input.value.trim();
        });
    });
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
