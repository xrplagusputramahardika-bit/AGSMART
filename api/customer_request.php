<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../config/duitku_orders.php';

if (empty($_SESSION['customer_id'])) jsonResponse(false, 'Silakan login sebagai pelanggan.', null, 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Metode tidak diizinkan.', null, 405);

$db = getDB();
if (!$db) jsonResponse(false, 'Database tidak tersedia.', null, 500);
$input = $_POST;
if (empty($input)) $input = json_decode(file_get_contents('php://input'), true) ?: [];
$allowedMethods = ['Transfer Bank', 'QRIS', 'E-Wallet', 'Duitku'];
$methodMap = ['transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'ewallet' => 'E-Wallet', 'duitku' => 'Duitku'];
$proofPath = null;

function saveCustomerPaymentProof($file) {
    if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Unggah bukti pembayaran terlebih dahulu.');
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Ukuran bukti maksimal 5 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'application/pdf'=>'pdf'];
    if (!isset($extensions[$mime])) throw new Exception('Bukti harus berupa JPG, PNG, WEBP, atau PDF.');
    $directory = __DIR__ . '/../uploads/payment-proofs';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new Exception('Folder penyimpanan bukti pembayaran tidak dapat dibuat.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new Exception('Bukti pembayaran gagal disimpan.');
    }
    return $filename;
}

try {
    $rawMethod = $input['method'] ?? 'transfer';
    $method = $methodMap[$rawMethod] ?? $rawMethod;
    if (!in_array($method, $allowedMethods, true)) {
        jsonResponse(false, 'Metode pembayaran tidak tersedia.', null, 400);
    }

    if (($input['action'] ?? '') === 'topup') {
        if ($method === 'Duitku') throw new Exception('Top up saldo melalui Duitku belum tersedia.');
        $amount = (float)($input['amount'] ?? 0);
        if ($amount < 10000 || $amount > 10000000) {
            throw new Exception('Top up minimal Rp 10.000 dan maksimal Rp 10.000.000.');
        }
        $proofPath = saveCustomerPaymentProof($_FILES['bukti_transfer'] ?? null);
        $stmt = $db->prepare('INSERT INTO tbl_topup_request(id_pelanggan, nominal, metode_bayar, bukti_transfer) VALUES(?,?,?,?)');
        $stmt->execute([$_SESSION['customer_id'], $amount, $method, $proofPath]);
        jsonResponse(true, 'Bukti pembayaran top up terkirim. Saldo akan bertambah setelah kasir memverifikasi pembayaran.', ['id_topup'=>$db->lastInsertId()]);
    }

    // Upload/Ubah bukti pembayaran untuk order yang sudah exists
    if (($input['action'] ?? '') === 'order' && !empty($input['id_order'])) {
        $idOrder = (int)$input['id_order'];
        $proofPath = saveCustomerPaymentProof($_FILES['bukti_transfer'] ?? null);
        $stmt = $db->prepare("UPDATE tbl_pelanggan_order SET bukti_transfer = ?, status = 'menunggu_verifikasi' WHERE id_order = ? AND id_pelanggan = ? AND metode_bayar NOT LIKE 'Duitku%' AND status IN ('menunggu_verifikasi','ditolak')");
        $stmt->execute([$proofPath, $idOrder, $_SESSION['customer_id']]);
        if ($stmt->rowCount() !== 1) throw new Exception('Pesanan tidak ditemukan atau tidak dapat menerima bukti pembayaran manual.');
        jsonResponse(true, 'Bukti pembayaran berhasil diunggah. Pesanan akan diproses setelah kasir verifikasi.', ['id_order'=>$idOrder]);
    }

    $items = $input['items'] ?? [];
    if (is_string($items)) $items = json_decode($items, true) ?: [];
    if (!is_array($items) || !$items) throw new Exception('Pilih barang terlebih dahulu.');

    if ($method === 'Duitku') {
        if (($input['action'] ?? '') !== 'order') throw new Exception('Jenis pesanan Duitku tidak valid.');
        $payment = createDuitkuCustomerOrder($db, $_SESSION['customer_id'], $items, (string)($input['payment_method'] ?? ''));
        jsonResponse(true, 'Invoice berhasil dibuat. Anda akan diarahkan ke halaman pembayaran Duitku.', $payment);
    }

    $db->beginTransaction();
    $order = [];
    $total = 0;
    foreach ($items as $entry) {
        $id = (int)($entry['id'] ?? 0);
        $qty = max(0, min(99, (int)($entry['qty'] ?? 0)));
        if (!$id || !$qty) continue;
        $stmt = $db->prepare('SELECT id_produk,nama_produk,harga_jual,stok FROM tbl_produk WHERE id_produk=? FOR UPDATE');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product || (int)$product['stok'] < $qty) throw new Exception('Stok barang yang dipilih tidak mencukupi.');
        $lineTotal = (float)$product['harga_jual'] * $qty;
        $total += $lineTotal;
        $order[] = ['id_produk'=>(int)$product['id_produk'], 'nama_produk'=>$product['nama_produk'], 'harga'=>(float)$product['harga_jual'], 'qty'=>$qty, 'subtotal'=>$lineTotal];
    }
    if (!$order) throw new Exception('Keranjang kosong.');

    $needsProof = in_array($method, ['Transfer Bank', 'QRIS'], true);
    $status = 'menunggu_verifikasi';
    $finalProof = null;

    if ($method === 'E-Wallet') {
        $custStmt = $db->prepare('SELECT saldo FROM tbl_pelanggan WHERE id_pelanggan=? FOR UPDATE');
        $custStmt->execute([$_SESSION['customer_id']]);
        $cust = $custStmt->fetch();
        $saldo = (float)($cust['saldo'] ?? 0);
        if ($saldo < $total) {
            throw new Exception('Saldo e-wallet tidak mencukupi. Saldo: '.formatRupiah($saldo).', Total: '.formatRupiah($total).'.');
        }
        $db->prepare('UPDATE tbl_pelanggan SET saldo = saldo - ? WHERE id_pelanggan = ?')
            ->execute([$total, $_SESSION['customer_id']]);
        $status = 'diproses';
        $finalProof = null;
    } else {
        $finalProof = saveCustomerPaymentProof($_FILES['bukti_transfer'] ?? null);
    }

    $stmt = $db->prepare(
        "INSERT INTO tbl_pelanggan_order(id_pelanggan, detail_json, total, status, metode_bayar, bukti_transfer)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $_SESSION['customer_id'],
        json_encode($order, JSON_UNESCAPED_UNICODE),
        $total,
        $status,
        $method,
        $finalProof
    ]);
    $id = (int)$db->lastInsertId();
    $db->commit();

    $msg = $method === 'E-Wallet'
        ? 'Pesanan berhasil! Saldo e-wallet telah dipotong.'
        : 'Pesanan dan bukti pembayaran terkirim. Diproses setelah kasir verifikasi.';

    jsonResponse(true, $msg, [
        'id_order' => $id,
        'total' => $total,
        'metode_bayar' => $method,
        'status' => $status
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    if ($proofPath) { $proofFile = __DIR__ . '/../uploads/payment-proofs/' . $proofPath; if (is_file($proofFile)) @unlink($proofFile); }
    jsonResponse(false, $e->getMessage(), null, 400);
}
