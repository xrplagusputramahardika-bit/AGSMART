<?php
require_once __DIR__ . '/config/helper.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
if (!empty($_SESSION['customer_id'])) { header('Location: pelanggan/dashboard.php'); exit; }
$mode = $_GET['mode'] ?? '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    if (!$db) $error = 'Database belum tersedia.';
    elseif (($_POST['mode'] ?? '') === 'cashier') {
        $identity = trim($_POST['identity'] ?? '');
        $password = $_POST['password'] ?? '';
        if (($_POST['action'] ?? '') === 'register_card') {
            $card = trim($_POST['card'] ?? '');
            $stmt=$db->prepare('SELECT id_user,password FROM tbl_user WHERE username=? LIMIT 1');$stmt->execute([$identity]);$account=$stmt->fetch();
            if(!$account || !password_verify($password,$account['password'])) $error='Username atau password kasir tidak benar.';
            elseif($card==='') $error='Tempelkan kartu kasir ke kolom nomor kartu.';
            else { try{$stmt=$db->prepare('UPDATE tbl_user SET no_kartu=? WHERE id_user=?');$stmt->execute([$card,$account['id_user']]);$error='Kartu kasir berhasil didaftarkan. Silakan tap kartu untuk login.';}catch(Throwable $e){$error='Kartu sudah terdaftar ke akun lain.';} }
        } else {
        $stmt = $db->prepare('SELECT id_user, username, no_kartu, password, nama_lengkap, role FROM tbl_user WHERE username = ? OR no_kartu = ? LIMIT 1');
        $stmt->execute([$identity, $identity]); $user = $stmt->fetch();
        $role = strtolower(trim((string)($user['role'] ?? '')));
        if ($role === 'administrator') $role = 'admin';
        if ($role === 'cashier') $role = 'kasir';
        $passwordLogin = $user && $password !== '' && password_verify($password, (string)$user['password']);
        $cardLogin = $user && $password === '' && !empty($user['no_kartu']) && hash_equals((string)$user['no_kartu'], $identity);
        if ($user && in_array($role, ['admin', 'kasir'], true) && ($passwordLogin || $cardLogin)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id_user'];
            $_SESSION['user_role'] = $role;
            $_SESSION['user_name'] = $user['nama_lengkap'];
            unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_role']);
            header('Location: ' . ($role === 'admin' ? 'admin/index.php' : 'kasir/index.php'));
            exit;
        }
        $error = 'Login gagal. Masukkan username dan password atau tap kartu kasir yang sudah terdaftar.';
        }
    } else {
        $card = strtoupper(trim($_POST['card'] ?? '')); $phone = trim($_POST['phone'] ?? '');
        $stmt=$db->prepare("SELECT id_pelanggan,nama_pelanggan FROM tbl_pelanggan WHERE UPPER(no_kartu)=? AND telepon=? AND status_kartu='aktif' LIMIT 1");
        $stmt->execute([$card,$phone]); $customer=$stmt->fetch();
        if ($customer) { session_regenerate_id(true); $_SESSION['customer_id']=(int)$customer['id_pelanggan']; $_SESSION['customer_name']=$customer['nama_pelanggan']; header('Location: pelanggan/dashboard.php'); exit; }
        $error='Kartu atau nomor telepon tidak cocok. Silakan hubungi kasir untuk bantuan.';
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Selamat Datang - AGSMART</title><link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="assets/css/style.css"><style>body{min-height:100vh;background:radial-gradient(circle at 15% 10%,#e5f5ff,transparent 35%),linear-gradient(135deg,#f7fafc,#eaf1fb);display:grid;place-items:center}.welcome{width:min(1050px,94vw);background:#fff;border-radius:28px;box-shadow:0 24px 80px #18365c1c;overflow:hidden}.hero{background:linear-gradient(145deg,#102b55,#1768a5);color:#fff;padding:3rem;height:100%;display:flex;flex-direction:column;justify-content:center}.hero img{width:190px;background:#fff;padding:8px 12px;border:1px solid rgba(255,255,255,.75);border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.14)}.choice{border:1px solid #e5ebf2;border-radius:18px;padding:22px;transition:.2s}.choice:hover{border-color:#2b7ab8;transform:translateY(-2px)}.tap{font-family:monospace;letter-spacing:.1em}</style></head><body><main class="welcome"><div class="row g-0"><section class="col-lg-5"><div class="hero"><img src="assets/img/logo-agsmart.svg" alt="AGSMART"><p class="text-uppercase small fw-bold mt-5 mb-2 text-info">Belanja praktis setiap hari</p><h1 class="display-6 fw-bold">Selamat datang di AGSMART</h1><p class="text-white-50">Pilih akses sesuai kebutuhan Anda.</p><div class="mt-4 small text-white-50"><i class="bi bi-shield-check me-2"></i>Akses kasir dan pelanggan dipisahkan</div></div></section><section class="col-lg-7 p-4 p-md-5"><h2 class="fw-bold mb-1">Mulai di sini</h2><p class="text-secondary mb-4">Kasir mendapatkan akses penuh. Pelanggan dapat belanja dan mengelola e-wallet.</p><?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="choice mb-3"><div class="d-flex gap-3"><i class="bi bi-person-workspace fs-2 text-primary"></i><div class="flex-grow-1"><h5 class="fw-bold">Login Kasir</h5><p class="text-secondary small mb-3">Gunakan username dan password, atau tap kartu kasir.</p><form method="post" class="row g-2"><input type="hidden" name="mode" value="cashier"><div class="col-md-6"><input class="form-control tap" name="identity" placeholder="Username / tap kartu" required autofocus></div><div class="col-md-4"><input class="form-control" name="password" type="password" placeholder="Password (kosongkan untuk tap)"></div><div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-arrow-right"></i></button></div></form><details class="mt-3"><summary class="small text-primary">Daftarkan kartu kasir</summary><form method="post" class="row g-2 mt-2"><input type="hidden" name="mode" value="cashier"><input type="hidden" name="action" value="register_card"><div class="col-md-4"><input class="form-control form-control-sm" name="identity" placeholder="Username kasir" required></div><div class="col-md-4"><input class="form-control form-control-sm" name="password" type="password" placeholder="Password kasir" required></div><div class="col-md-4"><input class="form-control form-control-sm tap" name="card" placeholder="Tap kartu di sini" required></div><div class="col-12"><button class="btn btn-sm btn-outline-primary">Simpan kartu</button></div></form></details></div></div></div>
<div class="choice"><div class="d-flex gap-3"><i class="bi bi-bag-heart fs-2 text-success"></i><div class="flex-grow-1"><h5 class="fw-bold">Belanja Pelanggan</h5><p class="text-secondary small mb-3">Masuk dengan kartu member dan nomor telepon terdaftar.</p><form method="post" class="row g-2"><input type="hidden" name="mode" value="customer"><div class="col-md-5"><input class="form-control tap" name="card" placeholder="Nomor kartu member" required></div><div class="col-md-5"><input class="form-control" name="phone" placeholder="Nomor telepon" required></div><div class="col-md-2 d-grid"><button class="btn btn-success"><i class="bi bi-arrow-right"></i></button></div></form></div></div></div><p class="small text-secondary mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Top up e-wallet dilakukan dengan transfer dan saldo masuk setelah dikonfirmasi.</p></section></div></main></body></html>
