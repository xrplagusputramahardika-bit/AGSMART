<?php
/**
 * Product Categories Management
 * AGSmart POS System
 */
$pageTitle = 'Kategori Produk';
require_once __DIR__ . '/config/helper.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $nama = cleanInput($_POST['nama_kategori']);
        $ikon = cleanInput($_POST['ikon'] ?: 'bi-tag');

        try {
            $stmt = $db->prepare("INSERT INTO tbl_kategori (nama_kategori, ikon) VALUES (?, ?)");
            $stmt->execute([$nama, $ikon]);
            setFlash('success', "Kategori <b>$nama</b> berhasil dibuat!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal menambah kategori: " . $e->getMessage());
        }
        header("Location: kategori.php");
        exit;
    }

    if ($action === 'update') {
        $id = (int)$_POST['id_kategori'];
        $nama = cleanInput($_POST['nama_kategori']);
        $ikon = cleanInput($_POST['ikon'] ?: 'bi-tag');

        try {
            $stmt = $db->prepare("UPDATE tbl_kategori SET nama_kategori = ?, ikon = ? WHERE id_kategori = ?");
            $stmt->execute([$nama, $ikon, $id]);
            setFlash('success', "Kategori berhasil diperbarui!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal memperbarui kategori: " . $e->getMessage());
        }
        header("Location: kategori.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id_kategori'];
        try {
            $stmt = $db->prepare("DELETE FROM tbl_kategori WHERE id_kategori = ?");
            $stmt->execute([$id]);
            setFlash('success', "Kategori berhasil dihapus!");
        } catch (Exception $e) {
            setFlash('danger', "Gagal menghapus kategori: " . $e->getMessage());
        }
        header("Location: kategori.php");
        exit;
    }
}

$categories = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT k.*, COUNT(p.id_produk) as total_produk 
            FROM tbl_kategori k 
            LEFT JOIN tbl_produk p ON k.id_kategori = p.id_kategori 
            GROUP BY k.id_kategori, k.nama_kategori, k.ikon 
            ORDER BY k.id_kategori ASC
        ");
        $categories = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/views/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-tags-fill text-primary"></i> Kategori Produk
            </h4>
            <p class="text-slate-400 small mb-0">Kelompokkan produk agar kasir mudah mencari barang saat transaksi.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kategori
        </button>
    </div>

    <div class="row g-3">
        <?php if (empty($categories)): ?>
            <div class="col-12">
                <div class="text-center py-5 card card-custom p-4">
                    <i class="bi bi-tags text-slate-500 fs-1 mb-2"></i>
                    <p class="text-slate-400 mb-0">Belum ada kategori. Silakan tambahkan kategori baru.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-custom p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-20 text-primary p-3 rounded-4 fs-4 border border-primary border-opacity-25">
                                    <i class="bi <?= htmlspecialchars($cat['ikon'] ?: 'bi-tag') ?>"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-white mb-1"><?= htmlspecialchars($cat['nama_kategori']) ?></h6>
                                    <span class="badge bg-slate-900 border border-slate-700 text-slate-300 small">
                                        <?= $cat['total_produk'] ?> Produk Terdaftar
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="border-top border-slate-700 pt-2 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="openEditCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini? Produk dalam kategori ini akan menjadi tanpa kategori.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_kategori" value="<?= $cat['id_kategori'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    <i class="bi bi-trash3 me-1"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL ADD CATEGORY -->
<div class="modal fade" id="modalAddCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h6 class="modal-title fw-bold text-white"><i class="bi bi-plus-circle text-primary me-1"></i> Tambah Kategori</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small text-slate-300">Nama Kategori *</label>
                        <input type="text" name="nama_kategori" class="form-control bg-slate-900 border-slate-700 text-light" required placeholder="Contoh: Frozen Food">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-slate-300">Ikon (Bootstrap Icons)</label>
                        <select name="ikon" class="form-select bg-slate-900 border-slate-700 text-light">
                            <option value="bi-basket2-fill">Sembako / Keranjang (bi-basket2-fill)</option>
                            <option value="bi-egg-fried">Makanan (bi-egg-fried)</option>
                            <option value="bi-cup-straw">Minuman (bi-cup-straw)</option>
                            <option value="bi-fire">Bumbu Dapur (bi-fire)</option>
                            <option value="bi-heart-pulse-fill">Kesehatan (bi-heart-pulse-fill)</option>
                            <option value="bi-shield-check">Kebersihan (bi-shield-check)</option>
                            <option value="bi-pencil-fill">ATK / Kantor (bi-pencil-fill)</option>
                            <option value="bi-tag">Umum (bi-tag)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-slate-700 p-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT CATEGORY -->
<div class="modal fade" id="modalEditCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-slate-800 border-slate-700 text-light rounded-4">
            <div class="modal-header border-slate-700">
                <h6 class="modal-title fw-bold text-white"><i class="bi bi-pencil-square text-warning me-1"></i> Edit Kategori</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_kategori" id="editCatId">
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small text-slate-300">Nama Kategori *</label>
                        <input type="text" name="nama_kategori" id="editCatNama" class="form-control bg-slate-900 border-slate-700 text-light" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-slate-300">Ikon (Bootstrap Icons)</label>
                        <select name="ikon" id="editCatIkon" class="form-select bg-slate-900 border-slate-700 text-light">
                            <option value="bi-basket2-fill">Sembako / Keranjang (bi-basket2-fill)</option>
                            <option value="bi-egg-fried">Makanan (bi-egg-fried)</option>
                            <option value="bi-cup-straw">Minuman (bi-cup-straw)</option>
                            <option value="bi-fire">Bumbu Dapur (bi-fire)</option>
                            <option value="bi-heart-pulse-fill">Kesehatan (bi-heart-pulse-fill)</option>
                            <option value="bi-shield-check">Kebersihan (bi-shield-check)</option>
                            <option value="bi-pencil-fill">ATK / Kantor (bi-pencil-fill)</option>
                            <option value="bi-tag">Umum (bi-tag)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-slate-700 p-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditCategory(c) {
        document.getElementById('editCatId').value = c.id_kategori;
        document.getElementById('editCatNama').value = c.nama_kategori;
        document.getElementById('editCatIkon').value = c.ikon || 'bi-tag';
        new bootstrap.Modal(document.getElementById('modalEditCategory')).show();
    }
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
