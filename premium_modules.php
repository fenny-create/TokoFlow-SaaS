<?php
function handlePremiumAction($act){
 global $conn,$uid;
 if(!$act)return false;

 if($act==="promo"){
  requireFeature("promos");
  $code=strtoupper(trim($_POST["kode"]??""));$jenis=$_POST["jenis"]??"nominal";$nilai=max(0,(float)($_POST["nilai"]??0));$min=max(0,(float)($_POST["min_total"]??0));
  $mulai=$_POST["mulai"]??date("Y-m-d");$berakhir=$_POST["berakhir"]??date("Y-m-d",strtotime("+30 days"));
  if($code==="")die("Kode promo wajib diisi.");if(!in_array($jenis,["nominal","persen"],true))$jenis="nominal";if($jenis==="persen")$nilai=min(100,$nilai);
  $q=$conn->prepare("INSERT INTO promos(user_id,kode,jenis,nilai,min_total,mulai,berakhir,aktif) VALUES(?,?,?,?,?,?,?,1)");
  $q->bind_param("issddss",$uid,$code,$jenis,$nilai,$min,$mulai,$berakhir);if(!$q->execute())die("Kode promo sudah digunakan.");audit("Tambah promo",$code);go("?p=promos");
 }
 if($act==="promo_toggle"){
  requireFeature("promos");$id=(int)$_POST["id"];$q=$conn->prepare("UPDATE promos SET aktif=IF(aktif=1,0,1) WHERE id=? AND user_id=?");$q->bind_param("ii",$id,$uid);$q->execute();go("?p=promos");
 }
 if($act==="shift_open"){
  requireFeature("shifts");$cash=max(0,(float)($_POST["modal"]??0));
  $q=$conn->prepare("SELECT id FROM shifts WHERE user_id=? AND status='open' LIMIT 1");$q->bind_param("i",$uid);$q->execute();if($q->get_result()->fetch_assoc())die("Masih ada shift kasir yang terbuka.");
  $q=$conn->prepare("INSERT INTO shifts(user_id,kasir,modal_awal,status) VALUES(?,?,?,'open')");$name=$_SESSION["nama"]??"Kasir";$q->bind_param("isd",$uid,$name,$cash);$q->execute();audit("Buka shift","Modal awal ".rp($cash));go("?p=shifts");
 }
 if($act==="shift_close"){
  requireFeature("shifts");$id=(int)$_POST["id"];$actual=max(0,(float)($_POST["kas_akhir"]??0));$note=trim($_POST["catatan_selisih"]??"");
  $q=$conn->prepare("SELECT modal_awal FROM shifts WHERE id=? AND user_id=? AND status='open'");$q->bind_param("ii",$id,$uid);$q->execute();$s=$q->get_result()->fetch_assoc();if(!$s)die("Shift tidak ditemukan.");
  $q=$conn->prepare("SELECT COALESCE(SUM(total),0) t FROM sales WHERE user_id=? AND metode='Tunai' AND created_at >= (SELECT mulai FROM shifts WHERE id=?)");$q->bind_param("ii",$uid,$id);$q->execute();$cashSales=(float)$q->get_result()->fetch_assoc()["t"];
  $expected=(float)$s["modal_awal"]+$cashSales;$diff=$actual-$expected;
  $q=$conn->prepare("UPDATE shifts SET selesai=NOW(),kas_akhir=?,kas_diharapkan=?,selisih=?,status='closed' WHERE id=? AND user_id=?");$q->bind_param("dddii",$actual,$expected,$diff,$id,$uid);$q->execute();audit("Tutup shift","Selisih ".rp($diff).($note!==""?" · Catatan: ".$note:""));go("?p=shifts");
 }
 if($act==="opname"){
  requireFeature("stock_opname");$pid=(int)$_POST["product_id"];$actual=max(0,(int)$_POST["stok_fisik"]);$note=trim($_POST["catatan"]??"");
  $q=$conn->prepare("SELECT stok,nama FROM products WHERE id=? AND user_id=? AND aktif=1");$q->bind_param("ii",$pid,$uid);$q->execute();$p=$q->get_result()->fetch_assoc();if(!$p)die("Produk tidak ditemukan.");
  $system=(int)$p["stok"];$diff=$actual-$system;$conn->begin_transaction();
  try{
   $q=$conn->prepare("INSERT INTO stock_opnames(user_id,status,catatan) VALUES(?,'selesai',?)");$q->bind_param("is",$uid,$note);$q->execute();$oid=$q->insert_id;
   $q=$conn->prepare("INSERT INTO stock_opname_items(opname_id,product_id,stok_sistem,stok_fisik,selisih) VALUES(?,?,?,?,?)");$q->bind_param("iiiii",$oid,$pid,$system,$actual,$diff);$q->execute();
   $q=$conn->prepare("UPDATE products SET stok=? WHERE id=? AND user_id=?");$q->bind_param("iii",$actual,$pid,$uid);$q->execute();
   $type="opname";$q=$conn->prepare("INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)");$q->bind_param("iiiiiis",$uid,$pid,$type,$diff,$system,$actual,$note);$q->execute();$conn->commit();
  }catch(Throwable $e){$conn->rollback();die(e($e->getMessage()));}
  audit("Stock opname",$p["nama"]." ".$system." → ".$actual);go("?p=opname");
 }
 if($act==="receivable_payment"){
  requireFeature("receivables");$rid=(int)$_POST["id"];$amount=max(0,(float)$_POST["amount"]);
  $q=$conn->prepare("SELECT total,paid FROM receivables WHERE id=? AND user_id=? AND status<>'lunas'");$q->bind_param("ii",$rid,$uid);$q->execute();$r=$q->get_result()->fetch_assoc();if(!$r)die("Piutang tidak ditemukan.");
  $left=max(0,(float)$r["total"]-(float)$r["paid"]);$amount=min($amount,$left);$new=(float)$r["paid"]+$amount;$status=$new>=(float)$r["total"]?"lunas":"sebagian";
  $q=$conn->prepare("INSERT INTO receivable_payments(receivable_id,jumlah) VALUES(?,?)");$q->bind_param("id",$rid,$amount);$q->execute();
  $q=$conn->prepare("UPDATE receivables SET paid=?,status=? WHERE id=? AND user_id=?");$q->bind_param("dsii",$new,$status,$rid,$uid);$q->execute();audit("Bayar piutang",rp($amount));go("?p=receivables");
 }
 if($act==="return_sale"){
  requireFeature("returns");$saleId=(int)$_POST["sale_id"];$itemId=(int)$_POST["item_id"];$qty=max(1,(int)$_POST["qty"]);$reason=trim($_POST["reason"]??"");
  $q=$conn->prepare("SELECT si.*,s.invoice FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE si.id=? AND si.sale_id=? AND s.user_id=?");$q->bind_param("iii",$itemId,$saleId,$uid);$q->execute();$item=$q->get_result()->fetch_assoc();if(!$item)die("Item transaksi tidak ditemukan.");
  $q=$conn->prepare("SELECT COALESCE(SUM(qty),0) q FROM sales_return_items WHERE sale_item_id=?");$q->bind_param("i",$itemId);$q->execute();$returned=(int)$q->get_result()->fetch_assoc()["q"];if($returned+$qty>(int)$item["qty"])die("Jumlah retur melebihi jumlah yang dibeli.");
  $conn->begin_transaction();
  try{
   $q=$conn->prepare("INSERT INTO sales_returns(user_id,sale_id,invoice,alasan) VALUES(?,?,?,?)");$q->bind_param("iiss",$uid,$saleId,$item["invoice"],$reason);$q->execute();$retId=$q->insert_id;
   $refund=$qty*(float)$item["harga"];$q=$conn->prepare("INSERT INTO sales_return_items(return_id,sale_item_id,product_id,qty,refund) VALUES(?,?,?,?,?)");$q->bind_param("iiiid",$retId,$itemId,$item["product_id"],$qty,$refund);$q->execute();
   $q=$conn->prepare("SELECT stok FROM products WHERE id=? AND user_id=? FOR UPDATE");$q->bind_param("ii",$item["product_id"],$uid);$q->execute();$old=(int)$q->get_result()->fetch_assoc()["stok"];$new=$old+$qty;
   $q=$conn->prepare("UPDATE products SET stok=? WHERE id=? AND user_id=?");$q->bind_param("iii",$new,$item["product_id"],$uid);$q->execute();
   $type="retur";$note="Retur ".$item["invoice"];$q=$conn->prepare("INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)");$q->bind_param("iiiiiis",$uid,$item["product_id"],$type,$qty,$old,$new,$note);$q->execute();$conn->commit();
  }catch(Throwable $e){$conn->rollback();die(e($e->getMessage()));}
  audit("Retur penjualan",$item["invoice"]);go("?p=returns");
 }
 if($act==="gateway"){
  requireFeature("payment_gateway");$provider=in_array($_POST["provider"]??"manual",["manual","midtrans","xendit"],true)?$_POST["provider"]:"manual";
  $public=trim($_POST["public_key"]??"");$secret=trim($_POST["secret_key"]??"");$webhook=trim($_POST["webhook_token"]??"");
  $q=$conn->prepare("INSERT INTO gateway_settings(user_id,provider,public_key,secret_key,webhook_token) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE provider=VALUES(provider),public_key=VALUES(public_key),secret_key=VALUES(secret_key),webhook_token=VALUES(webhook_token)");
  $q->bind_param("issss",$uid,$provider,$public,$secret,$webhook);$q->execute();audit("Pengaturan payment gateway",$provider);go("?p=gateway");
 }
 return false;
}

function renderPremiumPage($page){
 global $conn,$uid;
 if($page==="promos"){
  requireFeature("promos");$rows=$conn->query("SELECT * FROM promos WHERE user_id=$uid ORDER BY id DESC");
  echo '<div class="twopan"><div class="panel"><h3>Buat Promo</h3><p class="muted">Kode promo nominal atau persentase dengan minimum belanja dan periode aktif.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="promo"><label>Kode promo</label><input name="kode" placeholder="HEMAT10" required><label>Jenis</label><select name="jenis"><option value="nominal">Nominal</option><option value="persen">Persentase</option></select><label>Nilai</label><input type="number" name="nilai" min="0" required><label>Minimal belanja</label><input type="number" name="min_total" min="0" value="0"><div class="twocol"><div><label>Mulai</label><input type="date" name="mulai" value="'.date("Y-m-d").'"></div><div><label>Berakhir</label><input type="date" name="berakhir" value="'.date("Y-m-d",strtotime("+30 days")).'"></div></div><button class="primary full">Simpan Promo</button></form></div><div class="panel"><h3>Daftar Promo</h3>';
  while($r=$rows->fetch_assoc()){ $val=$r["jenis"]==="persen"?$r["nilai"]."%":rp($r["nilai"]); echo '<div class="row"><div><b>'.e($r["kode"]).'</b><small>'.$val.' · min '.rp($r["min_total"]).' · '.e($r["mulai"]).' s/d '.e($r["berakhir"]).'</small></div><form method="post" class="inlineform"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="promo_toggle"><input type="hidden" name="id" value="'.$r["id"].'"><button class="small">'.($r["aktif"]?"Nonaktifkan":"Aktifkan").'</button></form></div>'; }
  echo '</div></div>';return true;
 }
 if($page==="shifts"){
  requireFeature("shifts");$open=$conn->query("SELECT * FROM shifts WHERE user_id=$uid AND status='open' ORDER BY id DESC LIMIT 1")->fetch_assoc();
  echo '<div class="shift-page">';
  if($open){
    $sid=(int)$open["id"];
    $cash=(float)$conn->query("SELECT COALESCE(SUM(total),0) t FROM sales WHERE user_id=$uid AND metode='Tunai' AND created_at >= (SELECT mulai FROM shifts WHERE id=$sid)")->fetch_assoc()["t"];
    $noncash=(float)$conn->query("SELECT COALESCE(SUM(total),0) t FROM sales WHERE user_id=$uid AND metode IN ('QRIS','Transfer','Debit') AND created_at >= (SELECT mulai FROM shifts WHERE id=$sid)")->fetch_assoc()["t"];
    $expected=(float)$open["modal_awal"]+$cash;
    echo '<section class="shift-live panel"><div class="shift-live-head"><div><span class="section-kicker">SHIFT AKTIF</span><h3>'.e($open["kasir"]).'</h3><small>Dibuka '.date("d M Y · H:i",strtotime($open["mulai"])).'</small></div><span class="shift-status"><i></i> Berjalan</span></div>';
    echo '<div class="shift-summary-grid"><div class="shift-card"><small>Modal Awal</small><strong>'.rp($open["modal_awal"]).'</strong><span>Kas pembukaan</span></div><div class="shift-card"><small>Penjualan</small><strong>'.rp($cash+$noncash).'</strong><span>Tunai '.rp($cash).' · Non-tunai '.rp($noncash).'</span></div><div class="shift-card expected"><small>Kas Diharapkan</small><strong>'.rp($expected).'</strong><span>Modal + penjualan tunai</span></div></div>';
    echo '<button class="primary shift-close-trigger" type="button" onclick="openShiftCloseModal('.$open["id"].','.json_encode((float)$open["modal_awal"]).','.json_encode($cash).','.json_encode($noncash).','.json_encode($expected).')">Tutup Shift</button></section>';
    echo '<div class="modal" id="shiftCloseModal" aria-hidden="true"><div class="modalbox shift-modal"><button type="button" class="x" onclick="closeShiftCloseModal()">×</button><div class="shift-modal-head"><span class="section-kicker">REKONSILIASI KAS</span><h2>Tutup Shift</h2><p>Periksa ringkasan kas sebelum menyelesaikan shift.</p></div>';
    echo '<div class="shift-modal-cards"><div><small>Modal Awal</small><b id="shiftModalStart">'.rp($open["modal_awal"]).'</b></div><div><small>Penjualan Tunai / Non-Tunai</small><b id="shiftModalSales">'.rp($cash+$noncash).'</b><span id="shiftModalSalesMeta">Tunai '.rp($cash).' · Non-tunai '.rp($noncash).'</span></div><div><small>Kas Akhir Aktual vs Diharapkan</small><b><span id="shiftActualPreview">Rp 0</span> <span class="versus">vs</span> <span id="shiftExpectedPreview">'.rp($expected).'</span></b><span id="shiftDiffPreview" class="shift-diff neutral">Masukkan kas akhir fisik</span></div></div>';
    echo '<form method="post" id="shiftCloseForm"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="shift_close"><input type="hidden" name="id" value="'.$open["id"].'"><label>Kas akhir aktual <span class="required">*</span></label><input type="number" id="shiftActual" name="kas_akhir" min="0" step="100" required placeholder="Masukkan jumlah kas fisik" oninput="updateShiftDiff()"><label>Catatan Selisih <span class="required">*</span></label><textarea id="shiftNote" name="catatan_selisih" required placeholder="Jelaskan jika terdapat selisih kas..."></textarea><button class="primary full" type="submit">✓ Konfirmasi Tutup Shift</button></form></div></div>';
  } else {
    echo '<section class="panel shift-open-card"><span class="section-kicker">SHIFT KASIR</span><h3>Buka shift kasir</h3><p class="muted">Masukkan modal awal sebelum mulai menerima transaksi.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="shift_open"><label>Modal awal</label><input type="number" name="modal" min="0" value="0" required><button class="primary full">Buka Shift</button></form></section>';
  }
  echo '<section class="panel"><div class="panelhead"><div><h3>Riwayat Shift</h3><small class="muted">30 shift terakhir</small></div></div>';$rows=$conn->query("SELECT * FROM shifts WHERE user_id=$uid ORDER BY id DESC LIMIT 30");
  while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["kasir"]).'</b><small>'.date("d/m/Y H:i",strtotime($r["mulai"])).' · '.e($r["status"]).'</small></div><span class="tag '.((float)$r["selisih"]<0?"red":"").'">'.rp($r["selisih"]).'</span></div>';
  echo '</section></div>';return true;
 }
 if($page==="opname"){
  requireFeature("stock_opname");$products=$conn->query("SELECT id,nama,kode,stok,satuan FROM products WHERE user_id=$uid AND aktif=1 ORDER BY nama");
  echo '<div class="twopan"><div class="panel"><h3>Stock Opname</h3><p class="muted">Bandingkan stok sistem dengan stok fisik lalu koreksi otomatis.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="opname"><label>Produk</label><select name="product_id">';
  while($p=$products->fetch_assoc())echo '<option value="'.$p["id"].'">'.e($p["nama"]).' · sistem '.$p["stok"].' '.$p["satuan"].'</option>';
  echo '</select><label>Stok fisik</label><input type="number" name="stok_fisik" min="0" required><label>Catatan</label><textarea name="catatan"></textarea><button class="primary full">Simpan Opname & Koreksi</button></form></div><div class="panel"><h3>Opname terakhir</h3>';
  $rows=$conn->query("SELECT o.created_at,p.nama,i.stok_sistem,i.stok_fisik,i.selisih FROM stock_opnames o JOIN stock_opname_items i ON i.opname_id=o.id JOIN products p ON p.id=i.product_id WHERE o.user_id=$uid ORDER BY o.id DESC LIMIT 30");
  while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["nama"]).'</b><small>'.$r["stok_sistem"].' → '.$r["stok_fisik"].' · '.date("d/m/Y H:i",strtotime($r["created_at"])).'</small></div><span class="tag">'.($r["selisih"]>0?"+":"").e($r["selisih"]).'</span></div>';
  echo '</div></div>';return true;
 }
 if($page==="returns"){
  requireFeature("returns");$sales=$conn->query("SELECT id,invoice,pelanggan,created_at,total FROM sales WHERE user_id=$uid ORDER BY id DESC LIMIT 50");
  echo '<div class="panel"><h3>Retur Penjualan</h3><p class="muted">Masukkan ID item dari daftar di bawah. Sistem membatasi jumlah retur agar tidak melebihi pembelian.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="return_sale"><label>Transaksi</label><select name="sale_id">';
  while($s=$sales->fetch_assoc())echo '<option value="'.$s["id"].'">'.e($s["invoice"]).' · '.e($s["pelanggan"]).' · '.rp($s["total"]).'</option>';
  echo '</select><label>ID item penjualan</label><input type="number" name="item_id" min="1" required><label>Jumlah retur</label><input type="number" name="qty" min="1" required><label>Alasan</label><input name="reason" placeholder="Barang rusak / salah barang / lainnya"><button class="primary">Proses Retur</button></form></div><div class="panel"><h3>Item transaksi terbaru</h3><div class="tablewrap"><table><thead><tr><th>ID</th><th>Invoice</th><th>Produk</th><th>Qty</th><th>Harga</th></tr></thead><tbody>';
  $items=$conn->query("SELECT si.id item_id,s.invoice,si.nama_produk,si.qty,si.harga FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.user_id=$uid ORDER BY si.id DESC LIMIT 80");
  while($r=$items->fetch_assoc())echo '<tr><td><b>'.$r["item_id"].'</b></td><td>'.e($r["invoice"]).'</td><td>'.e($r["nama_produk"]).'</td><td>'.$r["qty"].'</td><td>'.rp($r["harga"]).'</td></tr>';
  echo '</tbody></table></div></div>';return true;
 }
 if($page==="receivables"){
  requireFeature("receivables");$rows=$conn->query("SELECT * FROM receivables WHERE user_id=$uid ORDER BY id DESC");
  echo '<div class="panel"><h3>Piutang Pelanggan</h3><p class="muted">Transaksi dengan metode Piutang dari kasir akan muncul di sini.</p>';
  while($r=$rows->fetch_assoc()){ $sisa=max(0,(float)$r["total"]-(float)$r["paid"]); echo '<div class="row"><div><b>'.e($r["customer_name"]).'</b><small>'.e($r["invoice"]).' · jatuh tempo '.e($r["due_date"]).' · '.e($r["status"]).'</small></div><div style="text-align:right"><b>'.rp($sisa).'</b>'; if($sisa>0)echo '<form method="post" class="inlineform"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="receivable_payment"><input type="hidden" name="id" value="'.$r["id"].'"><input type="number" name="amount" min="1" max="'.e($sisa).'" placeholder="Bayar" required><button class="small">Catat</button></form>'; echo '</div></div>'; }
  echo '</div>';return true;
 }
 if($page==="gateway"){
  requireFeature("payment_gateway");$r=$conn->query("SELECT * FROM gateway_settings WHERE user_id=$uid")->fetch_assoc()?:[];
  echo '<div class="panel narrow"><h3>Payment Gateway</h3><p class="muted">Fondasi konfigurasi gateway dan webhook. Pembayaran produksi tetap harus diverifikasi oleh provider.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="gateway"><label>Provider</label><select name="provider"><option value="manual">Manual</option><option value="midtrans">Midtrans</option><option value="xendit">Xendit</option></select><label>Public/Client Key</label><input name="public_key" value="'.e($r["public_key"]??"").'"><label>Secret/Server Key</label><input type="password" name="secret_key" value="'.e($r["secret_key"]??"").'"><label>Webhook Token</label><input name="webhook_token" value="'.e($r["webhook_token"]??"").'"><button class="primary full">Simpan Konfigurasi</button></form><div class="alert" style="margin-top:15px">QRIS/Transfer/Debit tidak menjadi otomatis hanya karena konfigurasi disimpan. Hubungkan SDK/API dan webhook resmi provider saat akan dipakai produksi.</div></div>';return true;
 }
 return false;
}
?>