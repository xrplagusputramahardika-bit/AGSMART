<?php
/**
 * Customer Register - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Daftar Pelanggan';
require_once __DIR__ . '/config/helper.php';
if (!empty($_SESSION['customer_id'])) {
    header('Location: pelanggan/dashboard.php');
    exit;
}
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = cleanInput($_POST['nama'] ?? '');
    $telepon = cleanInput($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $kota = cleanInput($_POST['kota'] ?? '');
    $alamat = cleanInput($_POST['alamat'] ?? '');
    if (empty($nama) || empty($telepon) || empty($password)) {
        $error = 'Nama, nomor HP, dan password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $db = getDB();
        if ($db) {
            try {
                $chk = $db->prepare("SELECT id_pelanggan FROM tbl_pelanggan WHERE telepon = ? LIMIT 1");
                $chk->execute([$telepon]);
                if ($chk->fetch()) {
                    $error = 'Nomor HP sudah terdaftar. Silakan login.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $noKartu = 'MBR-' . date('y') . rand(100000, 999999);
                    $stmt = $db->prepare("
                        INSERT INTO tbl_pelanggan
                        (nama_pelanggan, telepon, kota, alamat, no_kartu, tipe_member, diskon_member, poin, saldo, status_kartu, password)
                        VALUES (?, ?, ?, ?, ?, 'Silver', 2.00, 0, 0.00, 'aktif', ?)
                    ");
                    $stmt->execute([$nama, $telepon, $kota, $alamat, $noKartu, $hash]);
                    $success = 'Akun berhasil dibuat. Silakan login.';
                }
            } catch (Exception $e) {
                $error = 'Gagal mendaftar: ' . $e->getMessage();
            }
        } else {
            $error = 'Database tidak tersedia.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - AGSMART POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/customer.css">
    <style>
        body.customer-app { background: #fff; }
        .auth-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: var(--shopee-gray); }
        .auth-card { width: 100%; max-width: 420px; background: var(--shopee-white); border-radius: 20px; padding: 32px 24px; box-shadow: 0 4px 20px rgba(0,0,0,.06); border: 1px solid var(--shopee-border); }
        .auth-brand { text-align: center; margin-bottom: 24px; }
        .auth-brand .orange { color: var(--shopee-orange); font-weight: 800; font-size: 1.5rem; }
        .auth-title { font-size: 1.25rem; font-weight: 800; text-align: center; margin-bottom: 8px; }
        .auth-sub { text-align: center; color: var(--shopee-muted); font-size: 0.85rem; margin-bottom: 24px; }
        .form-label { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--shopee-muted); margin-bottom: 6px; display: block; }
        .form-control { height: 46px; border-radius: 12px; border: 1px solid var(--shopee-border); background: var(--shopee-gray); font-size: 0.95rem; padding: 0 14px; }
        .form-control:focus { background: #fff; border-color: var(--shopee-orange); box-shadow: 0 0 0 3px rgba(255,120,73,.15); }
        textarea.form-control { height: auto; min-height: 90px; resize: vertical; }
        .btn-reg { width: 100%; height: 48px; border-radius: 12px; background: var(--shopee-orange); color: #fff; border: none; font-size: 1rem; font-weight: 800; margin-top: 8px; }
        .btn-reg:hover { background: var(--shopee-orange-dark); }
        .footer-link { text-align: center; margin-top: 20px; font-size: 0.85rem; }
        .footer-link a { color: var(--shopee-orange); font-weight: 700; text-decoration: none; }
        .footer-link a:hover { text-decoration: underline; }
        .alert-danger { background: #fdecec; border: 1px solid #f5c6c7; color: #c0392b; border-radius: 10px; font-size: 0.85rem; padding: 10px 14px; }
        .alert-success { background: #e7f7e9; border: 1px solid #b7e4c7; color: #207a3c; border-radius: 10px; font-size: 0.85rem; padding: 10px 14px; }
    </style>
</head>
<body class="customer-app">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="orange">AGSMART</span> <span style="color:var(--shopee-text)">MART</span>
            </div>
            <h5 class="auth-title">Buat akun baru</h5>
            <p class="auth-sub">Bergabung dengan jutaan pelanggan setia kami</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <form method="POST" action="">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                <label class="form-label mt-3">Nomor HP</label>
                <input type="text" name="telepon" class="form-control" placeholder="08123456789" required value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                <label class="form-label mt-3">Kota / Daerah</label>
                <input type="text" name="kota" class="form-control" placeholder="Contoh: Jakarta" value="<?= htmlspecialchars($_POST['kota'] ?? '') ?>">
                <label class="form-label mt-3">Alamat Lengkap</label>
                <textarea name="alamat" class="form-control" placeholder="Contoh: Jl. Merdeka No. 10"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                <label class="form-label mt-3">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                <button type="submit" class="btn-reg mt-4">Daftar &amp; Masuk</button>
            </form>
            <p class="footer-link">Sudah punya akun? <a href="pelanggan/login.php">Masuk di sini</a></p>
        </div>
    </div>
</body>
</html>