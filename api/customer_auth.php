<?php
/**
 * API: Customer Authentication (Login / Register)
 * AGSmart POS System - Customer App (Shopee Style)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';

$db = getDB();
if (!$db) {
    jsonResponse(false, 'Database tidak tersedia.', null, 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode request tidak diizinkan.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? 'login';

if ($action === 'register') {
    $nama = cleanInput($input['nama'] ?? '');
    $telepon = cleanInput($input['telepon'] ?? '');
    $password = $input['password'] ?? '';
    $kota = cleanInput($input['kota'] ?? '');
    $alamat = cleanInput($input['alamat'] ?? '');

    if (empty($nama) || empty($telepon) || empty($password)) {
        jsonResponse(false, 'Nama, nomor HP, dan password wajib diisi.', null, 400);
    }
    if (strlen($password) < 6) {
        jsonResponse(false, 'Password minimal 6 karakter.', null, 400);
    }

    // Cek duplikat nomor HP
    $chk = $db->prepare("SELECT id_pelanggan FROM tbl_pelanggan WHERE telepon = ? LIMIT 1");
    $chk->execute([$telepon]);
    if ($chk->fetch()) {
        jsonResponse(false, 'Nomor HP sudah terdaftar. Silakan login.', null, 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $noKartu = 'MBR-' . date('y') . rand(100000, 999999);

    try {
        $stmt = $db->prepare("
            INSERT INTO tbl_pelanggan
            (nama_pelanggan, telepon, kota, alamat, no_kartu, tipe_member, diskon_member, poin, saldo, status_kartu, password)
            VALUES (?, ?, ?, ?, ?, 'Silver', 2.00, 0, 0.00, 'aktif', ?)
        ");
        $stmt->execute([$nama, $telepon, $kota, $alamat, $noKartu, $hash]);
        $id = (int)$db->lastInsertId();
        jsonResponse(true, 'Akun berhasil dibuat. Silakan login.', [
            'id_pelanggan' => $id,
            'no_kartu' => $noKartu
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Gagal mendaftar: ' . $e->getMessage(), null, 500);
    }
}

// ---------- LOGIN ----------
$telepon = cleanInput($input['telepon'] ?? '');
$password = $input['password'] ?? '';

if (empty($telepon) || empty($password)) {
    jsonResponse(false, 'Nomor HP dan password wajib diisi.', null, 400);
}

try {
    $stmt = $db->prepare("SELECT * FROM tbl_pelanggan WHERE telepon = ? AND status_kartu = 'aktif' LIMIT 1");
    $stmt->execute([$telepon]);
    $cust = $stmt->fetch();

    if (!$cust || !password_verify($password, $cust['password'] ?? '')) {
        jsonResponse(false, 'Nomor HP atau password salah.', null, 401);
    }

    $_SESSION['customer_id'] = (int)$cust['id_pelanggan'];
    $_SESSION['customer_name'] = $cust['nama_pelanggan'];
    $_SESSION['customer_role'] = 'customer';

    jsonResponse(true, 'Login berhasil.', [
        'id_pelanggan' => (int)$cust['id_pelanggan'],
        'nama_pelanggan' => $cust['nama_pelanggan'],
        'no_kartu' => $cust['no_kartu'],
        'tipe_member' => $cust['tipe_member'],
        'saldo' => (float)$cust['saldo'],
        'poin' => (int)$cust['poin'],
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Login gagal: ' . $e->getMessage(), null, 500);
}
