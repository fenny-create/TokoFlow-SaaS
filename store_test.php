<?php
declare(strict_types=1);
require_once 'config.php';
if(!isset($_SESSION['uid'])){header('Location: index.php');exit;}
$uid=(int)$_SESSION['uid'];
$me=$conn->query("SELECT nama,email,role FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
if(($me['role']??'')!=='developer'){header('Location: index.php?p=dashboard');exit;}
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function money($v){return 'Rp '.number_format((float)$v,0,',','.');}
function token(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(20));return $_SESSION['csrf'];}
function auditTest($conn,$uid,$action,$detail){$s=$conn->prepare('INSERT INTO audit_logs(user_id,aksi,detail) VALUES(?,?,?)');$s->bind_param('iss',$uid,$action,$detail);$s->execute();$s->close();}
function backupUser($conn,$uid){
  $dir=__DIR__.'/storage/backups'; if(!is_dir($dir))mkdir($dir,0775,true);
  $file=$dir.'/tokoflow_test_backup_'.date('Ymd_His').'.sql'; $fp=fopen($file,'wb'); if(!$fp)return false;
  fwrite($fp,"-- TokoFlow Developer Test Backup\n-- User ID: $uid\n-- Created: ".date('c')."\n\n");
  $tables=['settings','categories','products','customers','suppliers','sales','sale_items','stock_logs','expenses','purchases','purchase_items','audit_logs','shifts','promos','receivables','receivable_payments','sales_returns','sales_return_items','stock_opnames','stock_opname_items'];
  foreach($tables as $table){
    $where=in_array($table,['settings','categories','products','customers','suppliers','sales','stock_logs','expenses','purchases','audit_logs','shifts','promos','receivables'],true)?" WHERE user_id=".(int)$uid:'';
    if($table==='sale_items')$where=" WHERE sale_id IN (SELECT id FROM sales WHERE user_id=".(int)$uid.")";
    if($table==='purchase_items')$where=" WHERE purchase_id IN (SELECT id FROM purchases WHERE user_id=".(int)$uid.")";
    if($table==='receivable_payments')$where=" WHERE receivable_id IN (SELECT id FROM receivables WHERE user_id=".(int)$uid.")";
    if($table==='sales_return_items')$where=" WHERE return_id IN (SELECT id FROM sales_returns WHERE user_id=".(int)$uid.")";
    if($table==='stock_opnames')$where=" WHERE user_id=".(int)$uid;
    if($table==='stock_opname_items')$where=" WHERE opname_id IN (SELECT id FROM stock_opnames WHERE user_id=".(int)$uid.")";
    $res=$conn->query("SELECT * FROM `$table`$where"); if(!$res)continue;
    $fields=$conn->query("SHOW COLUMNS FROM `$table`");$cols=[];while($f=$fields->fetch_assoc())$cols[]=$f['Field'];
    while($row=$res->fetch_assoc()){$vals=[];foreach($cols as $c)$vals[]=$row[$c]===null?'NULL':"'".$conn->real_escape_string((string)$row[$c])."'";fwrite($fp,"INSERT INTO `$table` (`".implode('`,`',$cols)."`) VALUES (".implode(',',$vals).");\n");}
  }
  fclose($fp); return $file;
}
$results=[];$runCode='';$running=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);die('Token keamanan tidak valid.');}
  $action=$_POST['test_action']??'';
  if($action==='run'){
    $running=true;$started=microtime(true);
    $step=function($time,$name,$ok=true,$detail='')use(&$results){$results[]=['time'=>$time,'name'=>$name,'ok'=>$ok,'detail'=>$detail];if(!$ok)throw new RuntimeException($detail?:$name.' gagal.');};
    try{
      $step('09:00','Buka toko',true,'Aplikasi, session, dan database dapat diakses.');
      $kas='Automated Test Cashier';$modal=500000.0;$st=$conn->prepare("INSERT INTO shifts(user_id,kasir,modal_awal,status) VALUES(?,?,?,'open')");$st->bind_param('isd',$uid,$kas,$modal);$st->execute();$shiftId=$st->insert_id;$st->close();
      $check=$conn->query("SELECT status FROM shifts WHERE id=$shiftId AND user_id=$uid")->fetch_assoc();$step('09:05','Buka shift',($check['status']??'')==='open','Shift #'.$shiftId.' berhasil dibuka.');
      $product=$conn->query("SELECT id,nama,stok,harga_beli,harga_jual FROM products WHERE user_id=$uid AND aktif=1 ORDER BY id LIMIT 1")->fetch_assoc();
      $supplier=$conn->query("SELECT id,nama FROM suppliers WHERE user_id=$uid ORDER BY id LIMIT 1")->fetch_assoc();
      if(!$product)throw new RuntimeException('Produk demo tidak ditemukan.');
      $qty=10;$before=(int)$product['stok'];$buy=(float)$product['harga_beli'];$inv='TEST-PO-'.date('YmdHis').'-'.random_int(10,99);$total=$qty*$buy;
      $conn->begin_transaction();
      $st=$conn->prepare('INSERT INTO purchases(user_id,supplier_id,invoice,total) VALUES(?,?,?,?)');$sid=$supplier?(int)$supplier['id']:null;$st->bind_param('iisd',$uid,$sid,$inv,$total);$st->execute();$purchaseId=$st->insert_id;$st->close();
      $st=$conn->prepare('INSERT INTO purchase_items(purchase_id,product_id,qty,harga,subtotal) VALUES(?,?,?,?,?)');$st->bind_param('iiidd',$purchaseId,$product['id'],$qty,$buy,$total);$st->execute();$st->close();
      $after=$before+$qty;$st=$conn->prepare('UPDATE products SET stok=?,harga_beli=? WHERE id=? AND user_id=?');$st->bind_param('idii',$after,$buy,$product['id'],$uid);$st->execute();$st->close();
      $kind='masuk';$note='Automated test '.$inv;$st=$conn->prepare('INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)');$st->bind_param('iiiiiis',$uid,$product['id'],$kind,$qty,$before,$after,$note);$st->execute();$st->close();$conn->commit();
      $check=(int)$conn->query("SELECT stok FROM products WHERE id=".(int)$product['id']." AND user_id=$uid")->fetch_assoc()['stok'];$step('09:10','Barang datang',$check===$after,$product['nama'].' +'.$qty.' unit.');
      $step('09:15','Input pembelian',true,$inv.' · '.money($total));$step('09:20','Barang masuk stok',$check===$after,'Stok '.$before.' → '.$after.'.');
      $saleInv='TEST-QRIS-'.date('YmdHis').'-'.random_int(10,99);$saleTotal=(float)$product['harga_jual'];$ref='QRIS-DEMO-'.date('His').'-'.random_int(10,99);$customer='Pelanggan Uji QRIS';
      $conn->begin_transaction();
      $st=$conn->prepare('INSERT INTO sales(user_id,invoice,pelanggan,subtotal,diskon,total,bayar,kembalian,metode,payment_ref) VALUES(?,?,?,?,?,?,?,?,?,?)');$disc=0.0;$pay=$saleTotal;$change=0.0;$method='QRIS';$st->bind_param('issdddddss',$uid,$saleInv,$customer,$saleTotal,$disc,$saleTotal,$pay,$change,$method,$ref);$st->execute();$saleId=$st->insert_id;$st->close();
      $saleQty=1;$st=$conn->prepare('INSERT INTO sale_items(sale_id,product_id,nama_produk,qty,harga,subtotal) VALUES(?,?,?,?,?,?)');$st->bind_param('iisidd',$saleId,$product['id'],$product['nama'],$saleQty,$product['harga_jual'],$saleTotal);$st->execute();$itemId=$st->insert_id;$st->close();
      $afterSale=$after-1;$st=$conn->prepare('UPDATE products SET stok=? WHERE id=? AND user_id=?');$st->bind_param('iii',$afterSale,$product['id'],$uid);$st->execute();$st->close();$kind='keluar';$note='Automated QRIS '.$saleInv;$st=$conn->prepare('INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)');$st->bind_param('iiiiiis',$uid,$product['id'],$kind,$saleQty,$after,$afterSale,$note);$st->execute();$st->close();$conn->commit();
      $saved=$conn->query("SELECT invoice,metode,payment_ref FROM sales WHERE id=$saleId AND user_id=$uid")->fetch_assoc();$step('09:30','Pelanggan membeli',($saved['invoice']??'')===$saleInv,$saleInv);$step('09:35','Pembayaran QRIS',($saved['metode']??'')==='QRIS','Demo payment ref: '.$ref);$step('09:40','Cetak struk',true,'Struk tersedia di halaman transaksi #'.$saleId.'.');
      $conn->begin_transaction();$reason='Automated test return';$st=$conn->prepare('INSERT INTO sales_returns(user_id,sale_id,invoice,alasan) VALUES(?,?,?,?)');$st->bind_param('iiss',$uid,$saleId,$saleInv,$reason);$st->execute();$returnId=$st->insert_id;$st->close();$refund=$saleTotal;$st=$conn->prepare('INSERT INTO sales_return_items(return_id,sale_item_id,product_id,qty,refund) VALUES(?,?,?,?,?)');$st->bind_param('iiiid',$returnId,$itemId,$product['id'],$saleQty,$refund);$st->execute();$st->close();$restock=$afterSale+1;$st=$conn->prepare('UPDATE products SET stok=? WHERE id=? AND user_id=?');$st->bind_param('iii',$restock,$product['id'],$uid);$st->execute();$st->close();$kind='retur';$note='Automated return '.$saleInv;$st=$conn->prepare('INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)');$st->bind_param('iiiiiis',$uid,$product['id'],$kind,$saleQty,$afterSale,$restock,$note);$st->execute();$st->close();$conn->commit();
      $check=(int)$conn->query("SELECT stok FROM products WHERE id=".(int)$product['id'])->fetch_assoc()['stok'];$step('10:00','Retur barang',$check===$restock,'Stok kembali ke '.$restock.'.');
      $expense=25000.0;$desc='Automated test expense';$st=$conn->prepare('INSERT INTO expenses(user_id,keterangan,jumlah) VALUES(?,?,?)');$st->bind_param('isd',$uid,$desc,$expense);$st->execute();$st->close();$step('12:00','Cek stok',true,'Produk uji: '.$check.' unit.');$step('15:00','Tambah pengeluaran',true,money($expense));
      $kasAkhir=$modal;$expected=$modal;$selisih=$kasAkhir-$expected;$closed=date('Y-m-d H:i:s');$st=$conn->prepare("UPDATE shifts SET selesai=?,kas_akhir=?,kas_diharapkan=?,selisih=?,status='closed' WHERE id=? AND user_id=?");$st->bind_param('sddiii',$closed,$kasAkhir,$expected,$selisih,$shiftId,$uid);$st->execute();$st->close();$closedRow=$conn->query("SELECT status FROM shifts WHERE id=$shiftId")->fetch_assoc();$step('20:00','Tutup shift',($closedRow['status']??'')==='closed','Selisih '.$selisih.'.');
      $report=$conn->query("SELECT COUNT(*) c,COALESCE(SUM(total),0) total FROM sales WHERE user_id=$uid")->fetch_assoc();$step('20:05','Lihat laporan',true,$report['c'].' transaksi tersimpan · omzet '.money($report['total']));
      $backup=backupUser($conn,$uid);$step('20:10','Backup database',(bool)$backup,$backup?'File: '.basename($backup):'Gagal membuat file backup.');
      $runCode='RUN-'.date('Ymd-His').'-'.random_int(100,999);$status='passed';$json=json_encode($results,JSON_UNESCAPED_UNICODE);$st=$conn->prepare('INSERT INTO test_runs(user_id,run_code,status,steps_json) VALUES(?,?,?,?)');$st->bind_param('isss',$uid,$runCode,$status,$json);$st->execute();$st->close();auditTest($conn,$uid,'Uji Toko Nyata',$runCode);$done=true;
    }catch(Throwable $e){if($conn->errno)$conn->rollback();$results[]=['time'=>'ERROR','name'=>'Pengujian berhenti','ok'=>false,'detail'=>$e->getMessage()];$runCode='FAILED-'.date('Ymd-His');$json=json_encode($results,JSON_UNESCAPED_UNICODE);$status='failed';$st=$conn->prepare('INSERT INTO test_runs(user_id,run_code,status,steps_json) VALUES(?,?,?,?)');$st->bind_param('isss',$uid,$runCode,$status,$json);$st->execute();$st->close();auditTest($conn,$uid,'Uji Toko Nyata Gagal',$runCode);$done=false;}
  }
}
$history=$conn->query("SELECT run_code,status,created_at FROM test_runs WHERE user_id=$uid ORDER BY id DESC LIMIT 10");
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Uji Toko Nyata · TokoFlow</title><link rel="stylesheet" href="assets/style.css"></head><body class="theme-indigo mode-light"><main class="test-page"><div class="test-head"><div><span class="eyebrow">DEVELOPER LAB</span><h1>Uji Toko Nyata</h1><p>Simulasi alur minimarket dari buka toko sampai backup. Ini menguji database dan logika bisnis, bukan sekadar memeriksa apakah tombol terlihat cantik.</p></div><a class="button" href="index.php?p=dashboard">← Kembali</a></div>
<div class="test-banner"><div><b>Akun Developer</b><span><?=h($me['email'])?></span></div><div><b>Mode</b><span>Full access</span></div><div><b>Metode online</b><span>QRIS DEMO, tidak memotong saldo</span></div></div>
<div class="test-grid"><div class="panel"><h3>Skenario pengujian</h3><div class="test-list"><?php foreach([['09:00','Buka toko'],['09:05','Buka shift kasir'],['09:10','Barang datang'],['09:15','Input pembelian'],['09:20','Barang masuk stok'],['09:30','Pelanggan membeli'],['09:35','Pembayaran QRIS'],['09:40','Cetak struk'],['10:00','Retur barang'],['12:00','Cek stok'],['15:00','Tambah pengeluaran'],['20:00','Tutup shift'],['20:05','Lihat laporan'],['20:10','Backup database']] as $x):?><div class="test-row"><span><?=$x[0]?></span><b><?=$x[1]?></b><em>Siap diuji</em></div><?php endforeach;?></div><form method="post" style="margin-top:18px"><input type="hidden" name="csrf" value="<?=h(token())?>"><input type="hidden" name="test_action" value="run"><button class="primary full" onclick="return confirm('Jalankan simulasi toko? Ini akan menambah data transaksi uji ke akun developer.')">▶ Jalankan Uji Toko Nyata</button></form><p class="muted test-note">Setiap run membuat transaksi bertanda TEST, mengubah stok, mencatat retur/pengeluaran, menutup shift uji, membuat laporan, dan menghasilkan backup SQL. QRIS hanya simulasi lokal.</p></div>
<div class="panel"><h3>Hasil run ini</h3><?php if($results): foreach($results as $r):?><div class="test-result <?=!empty($r['ok'])?'pass':'fail'?>"><span><?=!empty($r['ok'])?'✓':'×'?></span><div><b><?=h($r['time'])?> · <?=h($r['name'])?></b><small><?=h($r['detail'])?></small></div></div><?php endforeach; ?><div class="run-code">Run: <?=h($runCode)?></div><?php else: ?><div class="empty">Belum ada pengujian pada sesi ini. Data demo sudah tersedia untuk pengujian manual.</div><?php endif; ?></div></div>
<div class="panel"><h3>Riwayat pengujian</h3><div class="tablewrap"><table><thead><tr><th>Run</th><th>Status</th><th>Waktu</th></tr></thead><tbody><?php while($r=$history->fetch_assoc()):?><tr><td><b><?=h($r['run_code'])?></b></td><td><span class="tag <?=($r['status']==='passed'?'green':'red')?>"><?=h($r['status'])?></span></td><td><?=h(date('d/m/Y H:i:s',strtotime($r['created_at'])))?></td></tr><?php endwhile;?></tbody></table></div></div>
<div class="panel developer-credentials"><h3>Kredensial developer lokal</h3><div><span>Email</span><code>developer@tokoflow.local</code></div><div><span>Password</span><code>dev123456</code></div><div><span>Akun demo pemilik</span><code>admin@tokoflow.local / admin123</code></div></div>
</main></body></html>
