<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/helper.php';
if (!isStaffUser()) jsonResponse(false, 'Login kasir diperlukan.', null, 401);
$db=getDB();
if(!$db) jsonResponse(false,'Database tidak tersedia.',null,500);
$orders=(int)$db->query("SELECT COUNT(*) FROM tbl_pelanggan_order WHERE status='menunggu_verifikasi'")->fetchColumn();
$topups=(int)$db->query("SELECT COUNT(*) FROM tbl_topup_request WHERE status='menunggu'")->fetchColumn();
jsonResponse(true,'Jumlah permintaan menunggu verifikasi.',['orders'=>$orders,'topups'=>$topups,'total'=>$orders+$topups]);
