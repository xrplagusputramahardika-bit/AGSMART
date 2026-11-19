<?php
$pageTitle='Pesanan & Top Up Online';
require_once __DIR__ . '/config/helper.php'; requireAdmin();
$db=getDB(); $notice='';
if($_SERVER['REQUEST_METHOD']==='POST' && $db){
    $kind=$_POST['kind']??''; $id=(int)($_POST['id']??0); $action=$_POST['action']??'';
    try{
        if($kind==='topup' && $id>0 && in_array($action,['approve','reject'],true)){
            $db->beginTransaction(); $q=$db->prepare("SELECT * FROM tbl_topup_request WHERE id_topup=? FOR UPDATE");$q->execute([$id]);$r=$q->fetch();
            if(!$r || $r['status']!=='menunggu') throw new Exception('Permintaan sudah diproses atau tidak ditemukan.');
            if($action==='approve' && empty($r['bukti_transfer'])) throw new Exception('Bukti pembayaran top up belum tersedia.');
            if($action==='approve'){$q=$db->prepare('UPDATE tbl_pelanggan SET saldo=saldo+? WHERE id_pelanggan=?');$q->execute([$r['nominal'],$r['id_pelanggan']]);$status='disetujui';}else $status='ditolak';
            $q=$db->prepare('UPDATE tbl_topup_request SET status=? WHERE id_topup=?');$q->execute([$status,$id]);$db->commit();$notice='Permintaan top up '.$status.'.';
        }elseif($kind==='order' && $id>0 && in_array($action,['approve','reject'],true)){
            $db->beginTransaction();$q=$db->prepare("SELECT * FROM tbl_pelanggan_order WHERE id_order=? FOR UPDATE");$q->execute([$id]);$r=$q->fetch();
            if(!$r || $r['status']!=='menunggu_verifikasi') throw new Exception('Pesanan tidak ditemukan atau sudah diproses.');
            if($action==='approve' && empty($r['bukti_transfer'])) throw new Exception('Bukti pembayaran pesanan belum tersedia.');
            if($action==='reject'){$q=$db->prepare("UPDATE tbl_pelanggan_order SET status='ditolak' WHERE id_order=?");$q->execute([$id]);$db->commit();$notice='Pesanan ditolak.';}
            else{
                $items=json_decode($r['detail_json'],true);if(!is_array($items)||!$items)throw new Exception('Detail pesanan tidak valid.');
                $details=[];$subtotal=0;
                foreach($items as $item){$q=$db->prepare('SELECT id_produk,nama_produk,harga_jual,stok FROM tbl_produk WHERE id_produk=? FOR UPDATE');$q->execute([(int)$item['id_produk']]);$p=$q->fetch();$qty=(int)$item['qty'];if(!$p||$qty<1||(int)$p['stok']<$qty)throw new Exception('Stok '.$item['nama_produk'].' tidak mencukupi.');$line=(float)$p['harga_jual']*$qty;$subtotal+=$line;$details[]=['product'=>$p,'qty'=>$qty,'line'=>$line];}
                $faktur='WEB-'.date('Ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT);$now=date('Y-m-d H:i:s');
                $q=$db->prepare("INSERT INTO tbl_transaksi(no_faktur,tanggal,waktu,id_pelanggan,subtotal,diskon,pajak,total_belanja,bayar,kembalian,metode_pembayaran,kasir,catatan,poin_didapat) VALUES(?,?,?,?,?,0,0,?,?,0,?,?,?,?)");
                $points=(int)floor($subtotal/10000);$q->execute([$faktur,date('Y-m-d'),date('H:i:s'),$r['id_pelanggan'],$subtotal,$subtotal,$subtotal,$r['metode_bayar'],'Pesanan Online', 'Pesanan #'.$id.' diverifikasi '.$r['metode_bayar'], $points]);$trxId=(int)$db->lastInsertId();
                $d=$db->prepare('INSERT INTO tbl_detail_transaksi(id_transaksi,id_produk,nama_produk,harga_satuan,qty,subtotal) VALUES(?,?,?,?,?,?)');$stock=$db->prepare('UPDATE tbl_produk SET stok=stok-? WHERE id_produk=?');
                foreach($details as $x){$p=$x['product'];$d->execute([$trxId,$p['id_produk'],$p['nama_produk'],$p['harga_jual'],$x['qty'],$x['line']]);$stock->execute([$x['qty'],$p['id_produk']]);}
                if($points>0){$q=$db->prepare('UPDATE tbl_pelanggan SET poin=poin+? WHERE id_pelanggan=?');$q->execute([$points,$r['id_pelanggan']]);}
                $q=$db->prepare("UPDATE tbl_pelanggan_order SET status='diproses' WHERE id_order=?");$q->execute([$id]);$db->commit();$notice='Pembayaran terverifikasi; pesanan masuk ke transaksi dan stok diperbarui.';
            }
        }
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$notice='Gagal: '.$e->getMessage();}
}
$topups = [];
$orders = [];
if ($db) {
    try {
        $topups = $db->query("SELECT r.*,p.nama_pelanggan,p.no_kartu FROM tbl_topup_request r JOIN tbl_pelanggan p ON p.id_pelanggan=r.id_pelanggan WHERE r.status='menunggu' ORDER BY r.id_topup DESC")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $orders = $db->query("SELECT o.*,p.nama_pelanggan,p.no_kartu FROM tbl_pelanggan_order o JOIN tbl_pelanggan p ON p.id_pelanggan=o.id_pelanggan WHERE o.status='menunggu_verifikasi' ORDER BY o.id_order DESC")->fetchAll();
    } catch (Throwable $e) {}
}
require_once __DIR__ . '/views/header.php';
?><div class="container-fluid px-4 py-4"><h4 class="fw-bold">Pesanan & Top Up Pelanggan</h4><p class="text-secondary">Verifikasi bukti transfer terlebih dahulu sebelum menyetujui. Persetujuan top up menambahkan saldo e-wallet.</p><?php if($notice):?><div class="alert alert-info"><?=htmlspecialchars($notice)?></div><?php endif;?>
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white fw-bold">Top up e-wallet menunggu verifikasi</div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>ID</th><th>Pelanggan</th><th>Kartu</th><th>Nominal</th><th>Metode bayar</th><th>Bukti</th><th>Waktu</th><th>Aksi</th></tr></thead><tbody><?php if(!$topups):?><tr><td colspan="8" class="text-center text-secondary p-4">Tidak ada permintaan top up.</td></tr><?php endif;?><?php foreach($topups as $t):?><tr><td>#<?=$t['id_topup']?></td><td><?=htmlspecialchars($t['nama_pelanggan'])?></td><td><?=htmlspecialchars($t['no_kartu']??'-')?></td><td><?=formatRupiah($t['nominal'])?></td><td><?=htmlspecialchars($t['metode_bayar'])?></td><td><a class="btn btn-sm btn-outline-primary" target="_blank" href="api/payment_proof.php?type=topup&amp;id=<?=(int)$t['id_topup']?>">Lihat bukti</a></td><td><?=htmlspecialchars($t['dibuat_pada'])?></td><td><form method="post" class="d-flex gap-2"><input type="hidden" name="kind" value="topup"><input type="hidden" name="id" value="<?=$t['id_topup']?>"><button name="action" value="approve" class="btn btn-sm btn-success">Setujui</button><button name="action" value="reject" class="btn btn-sm btn-outline-danger">Tolak</button></form></td></tr><?php endforeach;?></tbody></table></div></div>
<div class="card border-0 shadow-sm"><div class="card-header bg-white fw-bold">Pesanan pelanggan menunggu verifikasi</div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>ID</th><th>Pelanggan</th><th>Barang</th><th>Total</th><th>Metode bayar</th><th>Bukti</th><th>Waktu</th><th>Aksi</th></tr></thead><tbody><?php if(!$orders):?><tr><td colspan="8" class="text-center text-secondary p-4">Tidak ada pesanan online.</td></tr><?php endif;?><?php foreach($orders as $o):$detail=json_decode($o['detail_json'],true)?:[];?><tr><td>#<?=$o['id_order']?></td><td><?=htmlspecialchars($o['nama_pelanggan'])?><br><small><?=htmlspecialchars($o['no_kartu']??'')?></small></td><td><?php foreach($detail as $x):?><div><?=htmlspecialchars($x['nama_produk'])?> × <?= (int)$x['qty']?></div><?php endforeach;?></td><td><?=formatRupiah($o['total'])?></td><td><?=htmlspecialchars($o['metode_bayar'])?></td><td><a class="btn btn-sm btn-outline-primary" target="_blank" href="api/payment_proof.php?type=order&amp;id=<?=(int)$o['id_order']?>">Lihat bukti</a></td><td><?=htmlspecialchars($o['dibuat_pada'])?></td><td><form method="post" class="d-flex gap-2"><input type="hidden" name="kind" value="order"><input type="hidden" name="id" value="<?=$o['id_order']?>"><button name="action" value="approve" class="btn btn-sm btn-success">Konfirmasi pembayaran</button><button name="action" value="reject" class="btn btn-sm btn-outline-danger">Tolak</button></form></td></tr><?php endforeach;?></tbody></table></div></div></div><?php require_once __DIR__ . '/views/footer.php'; ?>
