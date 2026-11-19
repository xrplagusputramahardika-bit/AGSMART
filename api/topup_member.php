<?php
/**
 * API: Top Up Saldo Kartu Member
 * AGSmart POS System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
if (empty($_SESSION['user_id'])) jsonResponse(false, 'Login kasir diperlukan.', null, 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode request tidak diizinkan', null, 405);
}

$idPelanggan = (int)($_POST['id_pelanggan'] ?? 0);
$nominal = (float)($_POST['nominal'] ?? 0);

if ($idPelanggan <= 0 || $nominal <= 0) {
    jsonResponse(false, 'ID Pelanggan atau nominal top-up tidak valid!', null, 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(false, 'Koneksi database gagal.', null, 500);
}

try {
    $stmt = $db->prepare("UPDATE tbl_pelanggan SET saldo = saldo + ? WHERE id_pelanggan = ?");
    $stmt->execute([$nominal, $idPelanggan]);

    $stmtCheck = $db->prepare("SELECT nama_pelanggan, saldo, poin, no_kartu, tipe_member FROM tbl_pelanggan WHERE id_pelanggan = ?");
    $stmtCheck->execute([$idPelanggan]);
    $member = $stmtCheck->fetch();

    jsonResponse(true, "Top-up saldo sebesar " . formatRupiah($nominal) . " berhasil untuk " . $member['nama_pelanggan'] . "!", [
        'id_pelanggan'   => $idPelanggan,
        'nama_pelanggan' => $member['nama_pelanggan'],
        'saldo_baru'     => (float)$member['saldo'],
        'poin'           => (int)$member['poin'],
        'no_kartu'       => $member['no_kartu']
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Gagal melakukan top-up: ' . $e->getMessage(), null, 500);
}
