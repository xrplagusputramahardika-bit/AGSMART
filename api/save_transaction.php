<?php
/**
 * API: Save New POS Transaction (With Member Card Points & Saldo Support)
 * AGSmart POS System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
if (empty($_SESSION['user_id'])) jsonResponse(false, 'Login kasir diperlukan.', null, 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode request tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    jsonResponse(false, 'Keranjang belanja kosong!', null, 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(false, 'Koneksi database gagal. Silakan periksa MySQL.', null, 500);
}

try {
    $db->beginTransaction();

    $noFaktur = generateNoFaktur();
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    $idPelanggan = !empty($input['id_pelanggan']) ? (int)$input['id_pelanggan'] : null;
    $subtotal = (float)($input['subtotal'] ?? 0);
    $diskon = (float)($input['diskon'] ?? 0);
    $pajak = (float)($input['pajak'] ?? 0);
    $totalBelanja = (float)($input['total_belanja'] ?? 0);
    $bayar = (float)($input['bayar'] ?? 0);
    $kembalian = (float)($input['kembalian'] ?? 0);
    $metode = cleanInput($input['metode_pembayaran'] ?? 'Tunai');
    $kasir = 'Kasir 1';
    $catatan = cleanInput($input['catatan'] ?? '');

    // Calculate Points (1 point per Rp 10.000)
    $poinDidapat = ($idPelanggan && $totalBelanja >= 10000) ? (int)floor($totalBelanja / 10000) : 0;

    // Check if paying with Member Card Balance (Saldo Member)
    $memberInfo = null;
    if ($idPelanggan) {
        $stmtMember = $db->prepare("SELECT * FROM tbl_pelanggan WHERE id_pelanggan = ? FOR UPDATE");
        $stmtMember->execute([$idPelanggan]);
        $memberInfo = $stmtMember->fetch();

        if ($metode === 'Saldo Member') {
            $saldoCurrent = (float)($memberInfo['saldo'] ?? 0);
            if ($saldoCurrent < $totalBelanja) {
                throw new Exception("Saldo kartu member tidak mencukupi! Saldo: " . formatRupiah($saldoCurrent) . ", Total: " . formatRupiah($totalBelanja));
            }
            // Deduct balance
            $stmtDeduct = $db->prepare("UPDATE tbl_pelanggan SET saldo = saldo - ? WHERE id_pelanggan = ?");
            $stmtDeduct->execute([$totalBelanja, $idPelanggan]);
            $bayar = $totalBelanja;
            $kembalian = 0;
        }

        // Add points
        if ($poinDidapat > 0) {
            $stmtPoints = $db->prepare("UPDATE tbl_pelanggan SET poin = poin + ? WHERE id_pelanggan = ?");
            $stmtPoints->execute([$poinDidapat, $idPelanggan]);
        }
    }

    // Insert into tbl_transaksi
    $stmtTrx = $db->prepare("
        INSERT INTO tbl_transaksi (
            no_faktur, tanggal, waktu, id_pelanggan, 
            subtotal, diskon, pajak, total_belanja, 
            bayar, kembalian, metode_pembayaran, kasir, catatan, poin_didapat
        ) VALUES (
            :no_faktur, :tanggal, :waktu, :id_pelanggan,
            :subtotal, :diskon, :pajak, :total_belanja,
            :bayar, :kembalian, :metode, :kasir, :catatan, :poin_didapat
        )
    ");

    $stmtTrx->execute([
        ':no_faktur'      => $noFaktur,
        ':tanggal'        => $tanggal,
        ':waktu'          => $waktu,
        ':id_pelanggan'   => $idPelanggan,
        ':subtotal'       => $subtotal,
        ':diskon'         => $diskon,
        ':pajak'          => $pajak,
        ':total_belanja'  => $totalBelanja,
        ':bayar'          => $bayar,
        ':kembalian'      => $kembalian,
        ':metode'         => $metode,
        ':kasir'          => $kasir,
        ':catatan'        => $catatan,
        ':poin_didapat'   => $poinDidapat
    ]);

    $idTransaksi = $db->lastInsertId();

    // Prepare statements for details and stock update
    $stmtDetail = $db->prepare("
        INSERT INTO tbl_detail_transaksi (
            id_transaksi, id_produk, nama_produk, harga_satuan, qty, subtotal
        ) VALUES (
            :id_transaksi, :id_produk, :nama_produk, :harga_satuan, :qty, :subtotal
        )
    ");

    $stmtStock = $db->prepare("
        UPDATE tbl_produk 
        SET stok = GREATEST(0, stok - :qty) 
        WHERE id_produk = :id_produk
    ");

    $insertedItems = [];

    foreach ($input['items'] as $item) {
        $idProduk = (int)$item['id_produk'];
        $namaProduk = cleanInput($item['nama_produk']);
        $hargaSatuan = (float)$item['harga_jual'];
        $qty = (int)$item['qty'];
        $itemSubtotal = $hargaSatuan * $qty;

        $stmtDetail->execute([
            ':id_transaksi' => $idTransaksi,
            ':id_produk'    => $idProduk,
            ':nama_produk'  => $namaProduk,
            ':harga_satuan' => $hargaSatuan,
            ':qty'          => $qty,
            ':subtotal'     => $itemSubtotal
        ]);

        $stmtStock->execute([
            ':qty'       => $qty,
            ':id_produk' => $idProduk
        ]);

        $insertedItems[] = [
            'id_produk'    => $idProduk,
            'nama_produk'  => $namaProduk,
            'harga_satuan' => $hargaSatuan,
            'qty'          => $qty,
            'subtotal'     => $itemSubtotal
        ];
    }

    $db->commit();

    // Fetch refreshed customer info
    $namaPelanggan = 'Pelanggan Umum';
    $noKartu = null;
    $tipeMember = null;
    $sisaPoin = 0;
    $sisaSaldo = 0;

    if ($idPelanggan) {
        $stmtPlg = $db->prepare("SELECT * FROM tbl_pelanggan WHERE id_pelanggan = ?");
        $stmtPlg->execute([$idPelanggan]);
        $cust = $stmtPlg->fetch();
        if ($cust) {
            $namaPelanggan = $cust['nama_pelanggan'];
            $noKartu = $cust['no_kartu'];
            $tipeMember = $cust['tipe_member'];
            $sisaPoin = (int)$cust['poin'];
            $sisaSaldo = (float)$cust['saldo'];
        }
    }

    $pengaturan = getPengaturan();

    $responseReceipt = [
        'transaksi' => [
            'id_transaksi'      => $idTransaksi,
            'no_faktur'         => $noFaktur,
            'tanggal'           => formatTanggalIndo($tanggal),
            'waktu'             => $waktu,
            'id_pelanggan'      => $idPelanggan,
            'nama_pelanggan'    => $namaPelanggan,
            'no_kartu'          => $noKartu,
            'tipe_member'       => $tipeMember,
            'poin_didapat'      => $poinDidapat,
            'sisa_poin'         => $sisaPoin,
            'sisa_saldo'        => $sisaSaldo,
            'subtotal'          => $subtotal,
            'diskon'            => $diskon,
            'pajak'             => $pajak,
            'total_belanja'     => $totalBelanja,
            'bayar'             => $bayar,
            'kembalian'         => $kembalian,
            'metode_pembayaran' => $metode,
            'kasir'             => $kasir,
            'catatan'           => $catatan
        ],
        'items'      => $insertedItems,
        'pengaturan' => $pengaturan
    ];

    jsonResponse(true, 'Transaksi berhasil disimpan', $responseReceipt);

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(false, 'Gagal memproses transaksi: ' . $e->getMessage(), null, 500);
}
