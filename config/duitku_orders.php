<?php

require_once __DIR__ . '/duitku.php';

function calculateDuitkuOrderTotal(array $items) {
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float)$item['harga'] * (int)$item['qty'];
    }
    $discount = (int)floor($subtotal * 2 / 100);
    $shipping = $subtotal >= 200000 ? 0 : 9000;
    return [$subtotal, $discount, $shipping, $subtotal - $discount + $shipping];
}

function releaseDuitkuOrderStock(PDO $db, array $order) {
    if (empty($order['stok_dipesan'])) return;

    $items = json_decode($order['detail_json'], true);
    if (!is_array($items)) throw new RuntimeException('Detail pesanan Duitku tidak valid saat mengembalikan stok.');

    $restore = $db->prepare('UPDATE tbl_produk SET stok = stok + ? WHERE id_produk = ?');
    foreach ($items as $item) {
        $restore->execute([(int)$item['qty'], (int)$item['id_produk']]);
        if ($restore->rowCount() !== 1) {
            throw new RuntimeException('Stok produk pada pesanan Duitku tidak dapat dikembalikan.');
        }
    }
}

function failDuitkuOrder(PDO $db, $orderId) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM tbl_pelanggan_order WHERE id_order = ? FOR UPDATE');
        $stmt->execute([(int)$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order && $order['status'] === 'menunggu_pembayaran') {
            releaseDuitkuOrderStock($db, $order);
            $stmt = $db->prepare("UPDATE tbl_pelanggan_order SET status='ditolak', stok_dipesan=0 WHERE id_order=?");
            $stmt->execute([$order['id_order']]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}

function createDuitkuCustomerOrder(PDO $db, $customerId, array $entries, $paymentMethod) {
    if (!preg_match('/^[A-Za-z0-9]{2}$/', (string)$paymentMethod)) {
        throw new InvalidArgumentException('Pilih metode pembayaran Duitku yang tersedia.');
    }
    $config = duitkuConfig();
    syncExpiredDuitkuOrders($db, (int)$customerId);

    $quantities = [];
    foreach ($entries as $entry) {
        $id = (int)($entry['id'] ?? 0);
        $qty = (int)($entry['qty'] ?? 0);
        if ($id < 1 || $qty < 1 || $qty > 99) continue;
        $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
        if ($quantities[$id] > 99) throw new InvalidArgumentException('Jumlah barang dalam keranjang melebihi batas.');
    }
    if (!$quantities) throw new InvalidArgumentException('Pilih barang terlebih dahulu.');

    $db->beginTransaction();
    try {
        $items = [];
        $subtotal = 0;
        $select = $db->prepare('SELECT id_produk,nama_produk,harga_jual,stok FROM tbl_produk WHERE id_produk=? FOR UPDATE');
        foreach ($quantities as $id => $qty) {
            $select->execute([$id]);
            $product = $select->fetch(PDO::FETCH_ASSOC);
            if (!$product || (int)$product['stok'] < $qty) {
                throw new RuntimeException('Stok barang yang dipilih tidak mencukupi.');
            }
            $lineTotal = (float)$product['harga_jual'] * $qty;
            $subtotal += $lineTotal;
            $items[] = [
                'id_produk' => (int)$product['id_produk'],
                'nama_produk' => $product['nama_produk'],
                'harga' => (float)$product['harga_jual'],
                'qty' => $qty,
                'subtotal' => $lineTotal,
            ];
        }
        list(, , , $total) = calculateDuitkuOrderTotal($items);
        if ($total < 1 || $total > 1000000000) throw new RuntimeException('Total pembayaran di luar batas yang didukung.');

        $stmt = $db->prepare(
            "INSERT INTO tbl_pelanggan_order(id_pelanggan,detail_json,total,status,metode_bayar,stok_dipesan,duitku_expires_at)
             VALUES(?,?,?,'menunggu_pembayaran',?,1,?)"
        );
        $stmt->execute([
            (int)$customerId,
            json_encode($items, JSON_UNESCAPED_UNICODE),
            $total,
            'Duitku ' . $paymentMethod,
            date('Y-m-d H:i:s', time() + ($config['expiry_minutes'] * 60)),
        ]);
        $orderId = (int)$db->lastInsertId();
        $merchantOrderId = 'AG-' . date('ymdHis') . '-' . $orderId;
        $stmt = $db->prepare('UPDATE tbl_pelanggan_order SET duitku_order_id=? WHERE id_order=?');
        $stmt->execute([$merchantOrderId, $orderId]);

        $decrement = $db->prepare('UPDATE tbl_produk SET stok=stok-? WHERE id_produk=? AND stok>=?');
        foreach ($items as $item) {
            $decrement->execute([$item['qty'], $item['id_produk'], $item['qty']]);
            if ($decrement->rowCount() !== 1) throw new RuntimeException('Stok barang baru saja berubah. Muat ulang keranjang.');
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    try {
        $methods = duitkuGetPaymentMethods((int)round($total));
        $available = false;
        foreach ($methods as $method) {
            if ((string)($method['paymentMethod'] ?? '') === (string)$paymentMethod) {
                $available = true;
                break;
            }
        }
        if (!$available) throw new RuntimeException('Metode pembayaran ini sedang tidak tersedia di akun Duitku.');

        $stmt = $db->prepare('SELECT nama_pelanggan,telepon,alamat,kota FROM tbl_pelanggan WHERE id_pelanggan=?');
        $stmt->execute([(int)$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) throw new RuntimeException('Data pelanggan tidak ditemukan.');
        $settings = getPengaturan();

        $name = trim((string)($customer['nama_pelanggan'] ?: ('Pelanggan ' . $customerId)));
        $phone = preg_replace('/[^0-9+]/', '', (string)($customer['telepon'] ?: ($settings['telepon'] ?? '')));
        if ($phone === '') $phone = '080000000000';
        $email = filter_var((string)($settings['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: 'kontak@agsmart.id';
        $address = trim((string)($customer['alamat'] ?: ($settings['alamat'] ?? '')));
        if ($address === '') $address = 'Alamat pelanggan';
        $city = trim((string)($customer['kota'] ?: 'Jakarta'));
        $nameParts = preg_split('/\s+/', $name, 2);
        $firstName = substr($nameParts[0] ?? 'Pelanggan', 0, 40);
        $lastName = substr($nameParts[1] ?? '-', 0, 40);
        $vaName = substr(preg_replace('/\s+/', ' ', $name), 0, 20);
        $addressData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'address' => substr($address, 0, 100),
            'city' => substr($city, 0, 50),
            'postalCode' => '00000',
            'phone' => substr($phone, 0, 20),
            'countryCode' => 'ID',
        ];
        $signature = hash_hmac('sha256', $config['merchant_code'] . $merchantOrderId . (int)round($total), $config['api_key']);
        $response = duitkuPostJson($config['base_url'] . '/webapi/api/merchant/v2/inquiry', [
            'merchantCode' => $config['merchant_code'],
            'paymentAmount' => (int)round($total),
            'paymentMethod' => (string)$paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Pembayaran Pesanan AGSMART #' . $orderId,
            'merchantUserInfo' => (string)$customerId,
            'customerVaName' => $vaName ?: 'Pelanggan AGSMART',
            'email' => $email,
            'phoneNumber' => $phone,
            'itemDetails' => [[
                'name' => 'Belanja AGSMART #' . $orderId,
                'price' => (int)round($total),
                'quantity' => 1,
            ]],
            'customerDetail' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'phoneNumber' => $phone,
                'billingAddress' => $addressData,
                'shippingAddress' => $addressData,
            ],
            'callbackUrl' => $config['callback_url'],
            'returnUrl' => $config['return_url'],
            'signature' => $signature,
            'expiryPeriod' => $config['expiry_minutes'],
        ]);

        $paymentUrl = (string)($response['paymentUrl'] ?? '');
        $paymentUrlParts = parse_url($paymentUrl);
        if (($response['statusCode'] ?? '') !== '00'
            || !$paymentUrlParts
            || ($paymentUrlParts['scheme'] ?? '') !== 'https'
            || empty($paymentUrlParts['host'])) {
            throw new RuntimeException('Duitku tidak berhasil membuat halaman pembayaran: ' . (string)($response['statusMessage'] ?? 'respons tidak valid.'));
        }
        if (isset($response['amount']) && (int)$response['amount'] !== (int)round($total)) {
            throw new RuntimeException('Nominal invoice yang dikembalikan Duitku tidak sesuai.');
        }

        $stmt = $db->prepare('UPDATE tbl_pelanggan_order SET duitku_reference=?, duitku_payment_url=?, duitku_payment_code=? WHERE id_order=? AND status=?');
        $stmt->execute([
            (string)($response['reference'] ?? ''),
            $paymentUrl,
            (string)$paymentMethod,
            $orderId,
            'menunggu_pembayaran',
        ]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Invoice Duitku dibuat tetapi pesanan tidak dapat diperbarui.');

        return [
            'id_order' => $orderId,
            'total' => $total,
            'payment_url' => $paymentUrl,
            'payment_method' => (string)$paymentMethod,
        ];
    } catch (Throwable $e) {
        try {
            failDuitkuOrder($db, $orderId);
        } catch (Throwable $releaseError) {
            error_log('Duitku stock release failed for order ' . $orderId . ': ' . $releaseError->getMessage());
        }
        throw $e;
    }
}

function completeDuitkuOrder(PDO $db, array $callback) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM tbl_pelanggan_order WHERE duitku_order_id = ? FOR UPDATE');
        $stmt->execute([$callback['merchantOrderId']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) throw new RuntimeException('Pesanan Duitku tidak ditemukan.');

        if ((int)round((float)$order['total']) !== (int)$callback['amount']) {
            throw new RuntimeException('Nominal callback Duitku tidak sesuai dengan pesanan.');
        }

        $reference = trim((string)($callback['reference'] ?? ''));
        $paymentCode = trim((string)($callback['paymentCode'] ?? ''));
        if ($paymentCode !== '' && $order['duitku_payment_code'] && !hash_equals((string)$order['duitku_payment_code'], $paymentCode)) {
            throw new RuntimeException('Metode pembayaran Duitku tidak cocok dengan pesanan.');
        }
        if ($paymentCode === '') $paymentCode = (string)($order['duitku_payment_code'] ?? '');
        if ($order['status'] === 'diproses') {
            if ($order['duitku_reference'] && $reference && !hash_equals((string)$order['duitku_reference'], $reference)) {
                throw new RuntimeException('Referensi pembayaran Duitku tidak cocok.');
            }
            $db->commit();
            return;
        }
        if ($order['status'] === 'ditolak' && $callback['resultCode'] === '01') {
            $db->commit();
            return;
        }

        if ($order['status'] !== 'menunggu_pembayaran' || empty($order['stok_dipesan'])) {
            throw new RuntimeException('Status pesanan tidak dapat diproses sebagai pembayaran Duitku.');
        }

        if ($callback['resultCode'] !== '00') {
            releaseDuitkuOrderStock($db, $order);
            $stmt = $db->prepare("UPDATE tbl_pelanggan_order SET status='ditolak', stok_dipesan=0, duitku_reference=?, duitku_payment_code=? WHERE id_order=?");
            $stmt->execute([$reference ?: null, $paymentCode ?: null, $order['id_order']]);
            $db->commit();
            return;
        }

        $items = json_decode($order['detail_json'], true);
        if (!is_array($items) || !$items) throw new RuntimeException('Detail produk pada pesanan Duitku tidak valid.');
        list($subtotal, $discount, $shipping, $expectedTotal) = calculateDuitkuOrderTotal($items);
        if ((int)round($expectedTotal) !== (int)round((float)$order['total'])) {
            throw new RuntimeException('Total pesanan Duitku tidak konsisten.');
        }

        $points = (int)floor($subtotal / 10000);
        $method = 'Duitku ' . substr($paymentCode ?: 'Online', 0, 20);
        $invoice = 'WEB-' . date('Ymd') . '-' . str_pad((string)$order['id_order'], 5, '0', STR_PAD_LEFT);
        $stmt = $db->prepare(
            'INSERT INTO tbl_transaksi(no_faktur,tanggal,waktu,id_pelanggan,subtotal,diskon,pajak,total_belanja,bayar,kembalian,metode_pembayaran,kasir,catatan,poin_didapat)
             VALUES(?,?,?,?,?,?,0,?,?,0,?,?,?,?)'
        );
        $stmt->execute([
            $invoice, date('Y-m-d'), date('H:i:s'), $order['id_pelanggan'],
            $subtotal, $discount, $order['total'], $order['total'],
            $method, 'Pembayaran Online', 'Duitku #' . $order['id_order'] . ($reference ? ' - ' . $reference : ''), $points
        ]);
        $transactionId = (int)$db->lastInsertId();

        $detailStmt = $db->prepare('INSERT INTO tbl_detail_transaksi(id_transaksi,id_produk,nama_produk,harga_satuan,qty,subtotal) VALUES(?,?,?,?,?,?)');
        foreach ($items as $item) {
            $detailStmt->execute([
                $transactionId, (int)$item['id_produk'], $item['nama_produk'],
                (float)$item['harga'], (int)$item['qty'], (float)$item['subtotal']
            ]);
        }
        if ($points > 0) {
            $stmt = $db->prepare('UPDATE tbl_pelanggan SET poin=poin+? WHERE id_pelanggan=?');
            $stmt->execute([$points, $order['id_pelanggan']]);
        }

        $stmt = $db->prepare("UPDATE tbl_pelanggan_order SET status='diproses', stok_dipesan=0, duitku_reference=?, duitku_payment_code=? WHERE id_order=?");
        $stmt->execute([$reference ?: null, $paymentCode ?: null, $order['id_order']]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}

function syncDuitkuOrderStatus(PDO $db, array $order) {
    try {
        $status = duitkuGetTransactionStatus((string)$order['duitku_order_id']);
        if (($status['merchantOrderId'] ?? '') !== (string)$order['duitku_order_id']
            || !isset($status['amount'])
            || (int)$status['amount'] !== (int)round((float)$order['total'])) {
            throw new RuntimeException('Status transaksi Duitku tidak cocok dengan pesanan.');
        }

        $statusCode = (string)($status['statusCode'] ?? '');
        if ($statusCode === '01') return;
        if ($statusCode !== '00' && $statusCode !== '02') {
            throw new RuntimeException('Status transaksi Duitku tidak dikenal.');
        }

        completeDuitkuOrder($db, [
            'merchantOrderId' => (string)$order['duitku_order_id'],
            'amount' => (string)$status['amount'],
            'resultCode' => $statusCode === '00' ? '00' : '01',
            'reference' => (string)($status['reference'] ?? ''),
            'paymentCode' => (string)($order['duitku_payment_code'] ?? ''),
        ]);
    } catch (Throwable $e) {
        error_log('Duitku status sync failed for order ' . (int)$order['id_order'] . ': ' . $e->getMessage());
    }
}

function syncExpiredDuitkuOrders(PDO $db, $customerId = null) {
    $sql = "SELECT * FROM tbl_pelanggan_order
            WHERE status='menunggu_pembayaran' AND stok_dipesan=1
            AND duitku_order_id IS NOT NULL AND duitku_expires_at <= NOW()";
    $params = [];
    if ($customerId !== null) {
        $sql .= ' AND id_pelanggan=?';
        $params[] = (int)$customerId;
    }
    $sql .= ' ORDER BY duitku_expires_at ASC LIMIT 2';
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $order) {
            syncDuitkuOrderStatus($db, $order);
        }
    } catch (Throwable $e) {
        error_log('Expired Duitku order sync failed: ' . $e->getMessage());
    }
}

function syncDuitkuReturn(PDO $db, $merchantOrderId, $customerId) {
    if (!is_string($merchantOrderId) || !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $merchantOrderId)) return;
    try {
        $stmt = $db->prepare(
            "SELECT * FROM tbl_pelanggan_order
             WHERE duitku_order_id=? AND id_pelanggan=? AND status='menunggu_pembayaran' LIMIT 1"
        );
        $stmt->execute([$merchantOrderId, (int)$customerId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order) syncDuitkuOrderStatus($db, $order);
    } catch (Throwable $e) {
        error_log('Duitku return sync failed: ' . $e->getMessage());
    }
}
