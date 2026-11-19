<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../config/duitku.php';

if (empty($_SESSION['customer_id'])) jsonResponse(false, 'Silakan login sebagai pelanggan.', null, 401);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(false, 'Metode tidak diizinkan.', null, 405);

try {
    $amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_INT);
    if (!$amount || $amount < 1 || $amount > 1000000000) {
        throw new InvalidArgumentException('Total pembayaran tidak valid. Muat ulang checkout lalu coba lagi.');
    }

    $methods = duitkuGetPaymentMethods($amount);
    $data = [];
    foreach ($methods as $method) {
        if (empty($method['paymentMethod']) || empty($method['paymentName'])) continue;
        $data[] = [
            'code' => (string)$method['paymentMethod'],
            'name' => (string)$method['paymentName'],
            'fee' => (int)($method['totalFee'] ?? 0),
            'image' => (string)($method['paymentImage'] ?? ''),
        ];
    }
    jsonResponse(true, 'Metode pembayaran Duitku tersedia.', $data);
} catch (Throwable $e) {
    jsonResponse(false, $e->getMessage(), null, 503);
}
