<?php
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../config/duitku_orders.php';

function duitkuCallbackResponse($code, $message) {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') duitkuCallbackResponse(405, 'Method Not Allowed');

try {
    $config = duitkuConfig();
    $merchantCode = (string)($_POST['merchantCode'] ?? '');
    $amount = (string)($_POST['amount'] ?? '');
    $merchantOrderId = (string)($_POST['merchantOrderId'] ?? '');
    $signature = (string)($_POST['signature'] ?? '');
    $resultCode = (string)($_POST['resultCode'] ?? '');

    if (!hash_equals($config['merchant_code'], $merchantCode)
        || !preg_match('/^[0-9]+$/', $amount)
        || $merchantOrderId === ''
        || !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $merchantOrderId)
        || !in_array($resultCode, ['00', '01'], true)
        || !preg_match('/^[a-fA-F0-9]{64}$/', $signature)) {
        duitkuCallbackResponse(400, 'Invalid callback parameters');
    }

    $expectedSignature = hash_hmac('sha256', $merchantCode . $amount . $merchantOrderId, $config['api_key']);
    if (!hash_equals($expectedSignature, strtolower($signature))) {
        duitkuCallbackResponse(403, 'Invalid signature');
    }

    $db = getDB();
    if (!$db) duitkuCallbackResponse(503, 'Database unavailable');

    $transactionStatus = duitkuGetTransactionStatus($merchantOrderId);
    if (($transactionStatus['merchantOrderId'] ?? '') !== $merchantOrderId
        || !isset($transactionStatus['amount'])
        || (int)$transactionStatus['amount'] !== (int)$amount) {
        duitkuCallbackResponse(503, 'Duitku transaction status did not match');
    }
    $confirmedResultCode = (string)($transactionStatus['statusCode'] ?? '');
    if ($confirmedResultCode === '01') {
        duitkuCallbackResponse(503, 'Duitku transaction is still pending');
    }
    if ($confirmedResultCode === '02') $confirmedResultCode = '01';
    if ($confirmedResultCode !== '00' && $confirmedResultCode !== '01') {
        duitkuCallbackResponse(503, 'Unknown Duitku transaction status');
    }

    completeDuitkuOrder($db, [
        'merchantOrderId' => $merchantOrderId,
        'amount' => $amount,
        'resultCode' => $confirmedResultCode,
        'reference' => (string)($transactionStatus['reference'] ?? ($_POST['reference'] ?? '')),
        'paymentCode' => (string)($_POST['paymentCode'] ?? ''),
    ]);
    duitkuCallbackResponse(200, 'OK');
} catch (Throwable $e) {
    error_log('Duitku callback failed: ' . $e->getMessage());
    duitkuCallbackResponse(500, 'Callback processing failed');
}
