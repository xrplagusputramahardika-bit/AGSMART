<?php
/**
 * API: Get Receipt Data for Printing (With Member Card Info)
 * AGSmart POS System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
if (empty($_SESSION['user_id'])) jsonResponse(false, 'Login kasir diperlukan.', null, 401);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(false, 'ID Transaksi tidak valid', null, 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(false, 'Koneksi database tidak tersedia', null, 500);
}

try {
    $stmt = $db->prepare("
        SELECT t.*, p.nama_pelanggan, p.kota, p.telepon as telp_pelanggan,
               p.no_kartu, p.tipe_member, p.poin as member_poin, p.saldo as member_saldo
        FROM tbl_transaksi t
        LEFT JOIN tbl_pelanggan p ON t.id_pelanggan = p.id_pelanggan
        WHERE t.id_transaksi = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $trx = $stmt->fetch();

    if (!$trx) {
        jsonResponse(false, 'Transaksi tidak ditemukan', null, 404);
    }

    // Fetch items
    $stmtItems = $db->prepare("
        SELECT d.*, p.kode_produk, p.satuan
        FROM tbl_detail_transaksi d
        LEFT JOIN tbl_produk p ON d.id_produk = p.id_produk
        WHERE d.id_transaksi = ?
        ORDER BY d.id_detail ASC
    ");
    $stmtItems->execute([$id]);
    $items = $stmtItems->fetchAll();

    if (empty($items)) {
        $items = [
            [
                'id_detail'    => 0,
                'id_produk'    => 0,
                'nama_produk'  => 'Belanja Toko (Paket)',
                'harga_satuan' => $trx['total_belanja'],
                'qty'          => 1,
                'subtotal'     => $trx['total_belanja'],
                'satuan'       => 'paket'
            ]
        ];
    }

    $pengaturan = getPengaturan();

    $data = [
        'transaksi' => [
            'id_transaksi'      => $trx['id_transaksi'],
            'no_faktur'         => $trx['no_faktur'] ?: ('TRX-' . str_pad($trx['id_transaksi'], 6, '0', STR_PAD_LEFT)),
            'tanggal'           => formatTanggalIndo($trx['tanggal']),
            'waktu'             => $trx['waktu'] ?: '12:00:00',
            'id_pelanggan'      => $trx['id_pelanggan'],
            'nama_pelanggan'    => $trx['nama_pelanggan'] ?: 'Pelanggan Umum',
            'no_kartu'          => $trx['no_kartu'] ?? null,
            'tipe_member'       => $trx['tipe_member'] ?? null,
            'poin_didapat'      => (int)($trx['poin_didapat'] ?? 0),
            'sisa_poin'         => (int)($trx['member_poin'] ?? 0),
            'sisa_saldo'        => (float)($trx['member_saldo'] ?? 0),
            'kota'              => $trx['kota'] ?: '-',
            'subtotal'          => (float)($trx['subtotal'] ?: $trx['total_belanja']),
            'diskon'            => (float)($trx['diskon'] ?: 0),
            'pajak'             => (float)($trx['pajak'] ?: 0),
            'total_belanja'     => (float)$trx['total_belanja'],
            'bayar'             => (float)($trx['bayar'] ?: $trx['total_belanja']),
            'kembalian'         => (float)($trx['kembalian'] ?: 0),
            'metode_pembayaran' => $trx['metode_pembayaran'] ?: 'Tunai',
            'kasir'             => $trx['kasir'] ?: 'Kasir 1',
            'catatan'           => $trx['catatan'] ?: ''
        ],
        'items'      => $items,
        'pengaturan' => $pengaturan
    ];

    jsonResponse(true, 'Berhasil memuat struk', $data);

} catch (Exception $e) {
    jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
}
