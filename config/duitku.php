<?php

function loadDuitkuEnvironment() {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile) || !is_readable($envFile)) return;

    foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) putenv($name . '=' . $value);
        $_ENV[$name] = getenv($name);
    }
}

function duitkuConfig() {
    loadDuitkuEnvironment();

    $merchantCode = trim((string)getenv('DUITKU_MERCHANT_CODE'));
    $apiKey = trim((string)getenv('DUITKU_API_KEY'));
    $environment = strtolower(trim((string)(getenv('DUITKU_ENV') ?: 'sandbox')));
    $publicBaseUrl = rtrim(trim((string)getenv('DUITKU_PUBLIC_BASE_URL')), '/');
    $expiryMinutes = (int)(getenv('DUITKU_EXPIRY_MINUTES') ?: 60);

    if ($merchantCode === '' || $apiKey === '') {
        throw new RuntimeException('Konfigurasi Duitku belum lengkap. Isi Merchant Code dan API Key di file .env.');
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,50}$/', $merchantCode)) {
        throw new RuntimeException('Merchant Code Duitku tidak valid.');
    }
    if (!in_array($environment, ['sandbox', 'production'], true)) {
        throw new RuntimeException('DUITKU_ENV harus bernilai sandbox atau production.');
    }
    $baseParts = parse_url($publicBaseUrl);
    if (!$baseParts || ($baseParts['scheme'] ?? '') !== 'https' || empty($baseParts['host']) || isset($baseParts['user']) || isset($baseParts['pass'])) {
        throw new RuntimeException('DUITKU_PUBLIC_BASE_URL harus berupa URL HTTPS publik yang valid.');
    }
    if ($expiryMinutes < 10 || $expiryMinutes > 1440) {
        throw new RuntimeException('DUITKU_EXPIRY_MINUTES harus antara 10 sampai 1440 menit.');
    }

    $host = $environment === 'production' ? 'https://passport.duitku.com' : 'https://sandbox.duitku.com';
    return [
        'merchant_code' => $merchantCode,
        'api_key' => $apiKey,
        'environment' => $environment,
        'base_url' => $host,
        'public_base_url' => $publicBaseUrl,
        'callback_url' => $publicBaseUrl . '/api/duitku_callback.php',
        'return_url' => $publicBaseUrl . '/customer_orders.php?payment=duitku',
        'expiry_minutes' => $expiryMinutes,
    ];
}

function duitkuPostJson($url, array $payload, $timeout = 25) {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Ekstensi PHP cURL belum aktif. Aktifkan extension=curl lalu restart Apache.');
    }

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) throw new RuntimeException('Data permintaan Duitku tidak dapat dibuat.');

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => (int)$timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    ]);

    $responseBody = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($responseBody === false) {
        throw new RuntimeException('Tidak dapat terhubung ke Duitku: ' . ($curlError ?: 'koneksi gagal.'));
    }
    $response = json_decode($responseBody, true);
    if (!is_array($response)) {
        throw new RuntimeException('Respons Duitku tidak valid (HTTP ' . $httpStatus . ').');
    }
    if ($httpStatus < 200 || $httpStatus >= 300) {
        $message = $response['Message'] ?? $response['statusMessage'] ?? ('HTTP ' . $httpStatus);
        throw new RuntimeException('Duitku menolak permintaan: ' . (string)$message);
    }

    return $response;
}

function duitkuGetPaymentMethods($amount) {
    $config = duitkuConfig();
    $amount = (int)$amount;
    if ($amount < 1) throw new InvalidArgumentException('Jumlah pembayaran tidak valid.');

    $datetime = date('Y-m-d H:i:s');
    $signature = hash_hmac('sha256', $config['merchant_code'] . $amount . $datetime, $config['api_key']);
    $response = duitkuPostJson($config['base_url'] . '/webapi/api/merchant/paymentmethod/getpaymentmethod', [
        'merchantcode' => $config['merchant_code'],
        'amount' => $amount,
        'datetime' => $datetime,
        'signature' => $signature,
    ]);

    if (($response['responseCode'] ?? '') !== '00' || !isset($response['paymentFee']) || !is_array($response['paymentFee'])) {
        throw new RuntimeException('Duitku tidak mengembalikan metode pembayaran aktif.');
    }
    return $response['paymentFee'];
}

function duitkuGetTransactionStatus($merchantOrderId) {
    $config = duitkuConfig();
    $signature = hash_hmac('sha256', $config['merchant_code'] . $merchantOrderId, $config['api_key']);
    return duitkuPostJson($config['base_url'] . '/webapi/api/merchant/transactionStatus', [
        'merchantCode' => $config['merchant_code'],
        'merchantOrderId' => (string)$merchantOrderId,
        'signature' => $signature,
    ], 10);
}
