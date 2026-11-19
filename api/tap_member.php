<?php
/**
 * API: Tap / Scan Kartu Member (RFID / NFC / Barcode)
 * AGSmart POS System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
if (empty($_SESSION['user_id'])) jsonResponse(false, 'Login kasir diperlukan.', null, 401);

$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = trim($input['card_number'] ?? $input['code'] ?? '');
} else {
    $code = trim($_GET['card_number'] ?? $_GET['code'] ?? '');
}

if (empty($code)) {
    jsonResponse(false, 'Kode atau nomor kartu member tidak boleh kosong!', null, 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(false, 'Koneksi database tidak tersedia.', null, 500);
}

try {
    // 1. Check exact card number match
    $stmt = $db->prepare("
        SELECT * FROM tbl_pelanggan 
        WHERE no_kartu = ? OR no_kartu = ?
        LIMIT 1
    ");
    $cleanCode = strtoupper($code);
    $stmt->execute([$code, $cleanCode]);
    $member = $stmt->fetch();

    // 2. If not found by card number, try by phone or numeric ID
    if (!$member) {
        $stmtPhone = $db->prepare("SELECT * FROM tbl_pelanggan WHERE telepon = ? LIMIT 1");
        $stmtPhone->execute([$code]);
        $member = $stmtPhone->fetch();
    }

    if (!$member) {
        jsonResponse(false, "Kartu member '$code' tidak terdaftar dalam sistem!", null, 404);
    }

    // Check card status
    if (isset($member['status_kartu']) && $member['status_kartu'] === 'blokir') {
        jsonResponse(false, "Kartu member atas nama " . $member['nama_pelanggan'] . " DIBLOKIR. Silakan hubungi kasir/layanan pelanggan.", null, 403);
    }
    if (isset($member['status_kartu']) && $member['status_kartu'] === 'nonaktif') {
        jsonResponse(false, "Kartu member atas nama " . $member['nama_pelanggan'] . " belum aktif.", null, 403);
    }

    // Determine default discount based on tier if diskon_member is not set
    $tipe = $member['tipe_member'] ?? 'Silver';
    $diskon = (float)($member['diskon_member'] ?? 0);
    if ($diskon <= 0) {
        if ($tipe === 'Platinum') $diskon = 10.0;
        elseif ($tipe === 'Gold') $diskon = 5.0;
        elseif ($tipe === 'Silver') $diskon = 2.0;
    }

    $response = [
        'id_pelanggan'  => (int)$member['id_pelanggan'],
        'nama_pelanggan'=> $member['nama_pelanggan'],
        'kota'          => $member['kota'] ?? '-',
        'telepon'       => $member['telepon'] ?? '-',
        'no_kartu'      => $member['no_kartu'] ?: ('MBR-' . str_pad($member['id_pelanggan'], 6, '0', STR_PAD_LEFT)),
        'tipe_member'   => $tipe,
        'diskon_member' => $diskon,
        'poin'          => (int)($member['poin'] ?? 0),
        'saldo'         => (float)($member['saldo'] ?? 0),
        'status_kartu'  => $member['status_kartu'] ?? 'aktif'
    ];

    jsonResponse(true, "Kartu member terverifikasi: " . $member['nama_pelanggan'], $response);

} catch (Exception $e) {
    jsonResponse(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
