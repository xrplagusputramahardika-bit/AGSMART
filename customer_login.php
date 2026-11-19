<?php
/**
 * Customer Login - AGSmart Storefront (Shopee Style)
 */
$pageTitle = 'Login Pelanggan';
require_once __DIR__ . '/config/helper.php';
if (!empty($_SESSION['customer_id'])) {
    header('Location: pelanggan/dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telepon = cleanInput($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $db = getDB();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM tbl_pelanggan WHERE telepon = ? AND status_kartu = 'aktif' LIMIT 1");
            $stmt->execute([$telepon]);
            $cust = $stmt->fetch();
            if ($cust && password_verify($password, $cust['password'] ?? '')) {
                $_SESSION['customer_id'] = (int)$cust['id_pelanggan'];
                $_SESSION['customer_name'] = $cust['nama_pelanggan'];
                $_SESSION['customer_role'] = 'customer';
                header('Location: pelanggan/dashboard.php');
                exit;
            } else {
                $error = 'Nomor HP atau password salah.';
            }
        } catch (Exception $e) {
            $error = 'Login gagal: ' . $e->getMessage();
        }
    } else {
        $error = 'Database tidak tersedia.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - AGSMART POS</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
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
        .btn-login { width: 100%; height: 48px; border-radius: 12px; background: var(--shopee-orange); color: #fff; border: none; font-size: 1rem; font-weight: 800; margin-top: 8px; }
        .btn-login:hover { background: var(--shopee-orange-dark); }
        .divider { text-align: center; margin: 20px 0; font-size: 0.75rem; color: var(--shopee-muted); font-weight: 700; }
        .divider::before, .divider::after { content: ''; display: inline-block; width: 35%; height: 1px; background: var(--shopee-border); vertical-align: middle; }
        .divider span { display: inline-block; padding: 0 10px; }
        .footer-link { text-align: center; margin-top: 20px; font-size: 0.85rem; }
        .footer-link a { color: var(--shopee-orange); font-weight: 700; text-decoration: none; }
        .footer-link a:hover { text-decoration: underline; }
        .alert-danger { background: #fdecec; border: 1px solid #f5c6c7; color: #c0392b; border-radius: 10px; font-size: 0.85rem; padding: 10px 14px; }
    </style>
</head>
<body class="customer-app">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="orange">AGSMART</span> <span style="color:var(--shopee-text)">MART</span>
            </div>
            <h5 class="auth-title">Masuk ke akun Anda</h5>
            <p class="auth-sub">Selamat datang kembali di toko kami</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label class="form-label">Nomor HP</label>
                <input type="text" name="telepon" class="form-control" placeholder="08123456789" required value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                <label class="form-label mt-3">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <button type="submit" class="btn-login mt-4">Masuk</button>
            </form>
            <div class="divider"><span>Atau</span></div>
            <a href="customer_register.php" class="btn btn-outline-secondary w-100 py-2 border rounded-pill" style="font-weight:700; color:var(--shopee-text); border-color:var(--shopee-border);">Buat akun baru</a>
            <p class="footer-link">Belum punya akun? <a href="customer_register.php">Daftar sekarang</a></p>
        </div>
    </div>
</body>
</html>