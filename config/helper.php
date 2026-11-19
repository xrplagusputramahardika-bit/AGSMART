<?php
/**
 * Global Helpers & Utility Functions
 * AGSmart POS System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function currentStaffRole() {
    $role = strtolower(trim((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? '')));
    if ($role === 'administrator') $role = 'admin';
    if ($role === 'cashier') $role = 'kasir';
    return $role;
}

function isStaffUser() {
    return !empty($_SESSION['user_id']) && in_array(currentStaffRole(), ['admin', 'kasir'], true);
}

function requireCashier() {
    if (!isStaffUser()) {
        header('Location: welcome.php');
        exit;
    }
}

function requireAdmin() {
    if (currentStaffRole() !== 'admin' || empty($_SESSION['user_id'])) {
        header('Location: pos.php');
        exit;
    }
}

function requireCustomer() {
    if (empty($_SESSION['customer_id'])) {
        header('Location: welcome.php?mode=customer');
        exit;
    }
}

/**
 * Format number to Indonesian Rupiah (Rp 150.000)
 */
function formatRupiah($nominal) {
    return 'Rp ' . number_format((float)$nominal, 0, ',', '.');
}

/**
 * Format date to Indonesian text
 */
function formatTanggalIndo($tanggal, $denganWaktu = false) {
    if (empty($tanggal)) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($tanggal);
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    
    $result = "$tgl $bln $thn";
    if ($denganWaktu) {
        $result .= ' ' . date('H:i', $timestamp);
    }
    return $result;
}

/**
 * Generate unique Invoice Number: TRX-YYYYMMDD-XXXX
 */
function generateNoFaktur() {
    $db = getDB();
    $prefix = 'TRX-' . date('Ymd') . '-';
    
    if (!$db) {
        return $prefix . rand(1000, 9999);
    }
    
    try {
        $stmt = $db->prepare("SELECT no_faktur FROM tbl_transaksi WHERE no_faktur LIKE ? ORDER BY id_transaksi DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $lastFaktur = $stmt->fetchColumn();
        
        if ($lastFaktur) {
            $lastNum = (int)substr($lastFaktur, -4);
            $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '0001';
        }
        return $prefix . $nextNum;
    } catch (Exception $e) {
        return $prefix . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Get Store Settings
 */
function getPengaturan() {
    $default = [
        'nama_toko'    => 'AGSMART MART',
        'slogan'       => 'Pusat Belanja Hemat & Modern',
        'alamat'       => 'Jl. Merdeka Raya No. 45, Jawa Timur',
        'telepon'      => '0812-8888-9999',
        'email'        => 'admin@agsmart.com',
        'pesan_struk'  => 'Terima kasih telah berbelanja di AGSMART MART!',
        'footer_struk' => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.',
        'pajak_persen' => 0.00,
        'lebar_struk'  => '58mm',
        'bank_transfer' => 'BCA',
        'rekening_transfer' => '0008305215',
        'pemilik_rekening' => '',
        'qris_image_url' => '',
        'ewallet_info' => ''
    ];

    $db = getDB();
    if (!$db) return $default;

    try {
        $stmt = $db->query("SELECT * FROM tbl_pengaturan WHERE id = 1 LIMIT 1");
        $data = $stmt->fetch();
        if (!$data) return $default;
        $settings = array_merge($default, $data);
        foreach (['bank_transfer', 'rekening_transfer', 'pemilik_rekening', 'qris_image_url', 'ewallet_info'] as $field) {
            if (trim((string)($settings[$field] ?? '')) === '' && $default[$field] !== '') {
                $settings[$field] = $default[$field];
            }
        }
        return $settings;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Output JSON response and exit
 */
function jsonResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

/**
 * Sanitize string input
 */
function cleanInput($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}
