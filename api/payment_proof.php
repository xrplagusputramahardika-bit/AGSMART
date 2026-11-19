<?php
require_once __DIR__ . '/../config/helper.php';
if (!isStaffUser()) {
    http_response_code(401); exit('Login kasir diperlukan.');
}
$type=$_GET['type']??'';
$id=(int)($_GET['id']??0);
if($id<1 || !in_array($type,['order','topup'],true)){http_response_code(400);exit('Permintaan tidak valid.');}
$db=getDB();
if(!$db){http_response_code(500);exit('Database tidak tersedia.');}
$table=$type==='order'?'tbl_pelanggan_order':'tbl_topup_request';
$key=$type==='order'?'id_order':'id_topup';
$stmt=$db->prepare("SELECT bukti_transfer FROM `$table` WHERE `$key`=? LIMIT 1");
$stmt->execute([$id]);
$filename=$stmt->fetchColumn();
if(!$filename || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/',$filename)){http_response_code(404);exit('Bukti pembayaran tidak ditemukan.');}
$path=__DIR__.'/../uploads/payment-proofs/'.$filename;
if(!is_file($path)){http_response_code(404);exit('File bukti tidak ditemukan.');}
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($path);
if(!in_array($mime,['image/jpeg','image/png','image/webp','application/pdf'],true)){http_response_code(415);exit('Jenis file tidak didukung.');}
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Disposition: '.($mime==='application/pdf'?'attachment':'inline').'; filename="bukti-'.$id.'.'.pathinfo($filename,PATHINFO_EXTENSION).'"');
readfile($path);
