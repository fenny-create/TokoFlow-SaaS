<?php
// TokoFlow V7 production security baseline.
if (!headers_sent()) {
 header("X-Frame-Options: SAMEORIGIN");
 header("X-Content-Type-Options: nosniff");
 header("Referrer-Policy: strict-origin-when-cross-origin");
 header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
 header("Cross-Origin-Opener-Policy: same-origin");
}
require_once "config.php";
if(!isset($_SESSION["uid"])){
 if(empty($_SESSION["csrf"])) $_SESSION["csrf"]=bin2hex(random_bytes(20));
 $authView="login";
 $err="";$ok="";
 if($_SERVER["REQUEST_METHOD"]==="POST"){
  $act=$_POST["auth_action"]??"";
  if(!hash_equals($_SESSION["csrf"],$_POST["csrf"]??"")){$err="Sesi keamanan tidak valid. Silakan muat ulang halaman.";}
  elseif($act==="login"){
   $em=strtolower(trim($_POST["email"]??""));$pw=$_POST["password"]??"";
   $st=$conn->prepare("SELECT id,password,must_change_password FROM users WHERE email=? LIMIT 1");$st->bind_param("s",$em);$st->execute();$u=$st->get_result()->fetch_assoc();$st->close();
   if($u&&password_verify($pw,$u["password"])){session_regenerate_id(true);$_SESSION["uid"]=(int)$u["id"];$_SESSION["csrf"]=bin2hex(random_bytes(20));$_SESSION["must_change_password"]=(int)($u["must_change_password"]??0)===1;go($_SESSION["must_change_password"]?"index.php?p=change_password":"index.php");}
   $err="Email atau password salah.";$authView="login";
  }elseif($act==="register"){
   $nama=trim($_POST["nama"]??"");$shop=trim($_POST["shop"]??"");$em=strtolower(trim($_POST["email"]??""));$pw=$_POST["password"]??"";$pw2=$_POST["password2"]??"";
   if(mb_strlen($nama)<2)$err="Nama pemilik minimal 2 karakter.";
   elseif(mb_strlen($shop)<2)$err="Nama usaha minimal 2 karakter.";
   elseif(!filter_var($em,FILTER_VALIDATE_EMAIL))$err="Format email tidak valid.";
   elseif(strlen($pw)<8)$err="Password minimal 8 karakter.";
   elseif($pw!==$pw2)$err="Konfirmasi password tidak sama.";
   else{
    $st=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");$st->bind_param("s",$em);$st->execute();$exists=$st->get_result()->fetch_assoc();$st->close();
    if($exists)$err="Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.";
    else{
     $conn->begin_transaction();
     try{
      $hash=password_hash($pw,PASSWORD_DEFAULT);$role="owner";
      $st=$conn->prepare("INSERT INTO users(nama,email,password,role,owner_id) VALUES(?,?,?,?,NULL)");$st->bind_param("ssss",$nama,$em,$hash,$role);$st->execute();$newUid=$st->insert_id;$st->close();
      $st=$conn->prepare("UPDATE users SET owner_id=id WHERE id=?");$st->bind_param("i",$newUid);$st->execute();$st->close();
      $footer="Terima kasih sudah berbelanja.";$st=$conn->prepare("INSERT INTO settings(user_id,nama_usaha,footer,theme,mode) VALUES(?,?,?,?,?)");$theme="indigo";$mode="light";$st->bind_param("issss",$newUid,$shop,$footer,$theme,$mode);$st->execute();$st->close();
      $cat="Umum";$st=$conn->prepare("INSERT INTO categories(user_id,nama) VALUES(?,?)");$st->bind_param("is",$newUid,$cat);$st->execute();$st->close();
      $conn->commit();
      $ok="Akun berhasil dibuat. Silakan masuk dengan akun baru Anda.";$authView="login";
     }catch(Throwable $ex){$conn->rollback();$err="Akun gagal dibuat. Silakan coba lagi.";$authView="register";}
    }
   }
  }
 }
 ?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><link rel="manifest" href="manifest.json"><meta name="theme-color" content="#121826"><link rel="apple-touch-icon" href="icons/icon-192.png"><title><?=($authView==="register"?"Buat Akun":"Masuk")?> · TokoFlow</title><link rel="stylesheet" href="assets/style.css?v=saas-v7"></head><body class="auth-shell">
 <div class="auth-orb orb-a"></div><div class="auth-orb orb-b"></div>
 <div class="auth-layout">
  <section class="auth-showcase"><div class="showcase-brand"><span>T</span><b>TokoFlow</b></div><div class="showcase-copy"><div class="eyebrow">POS MODERN UNTUK BISNIS</div><h1>Kelola toko.<br><span>Lebih cepat.</span><br>Lebih profesional.</h1><p>Satu aplikasi untuk kasir, stok, transaksi, laporan, dan pengaturan toko dalam pengalaman yang modern.</p></div><div class="feature-pills"><span>✦ Kasir cepat</span><span>▣ Stok terkontrol</span><span>◔ Laporan rapi</span><span>☾ Dark mode</span></div><div class="showcase-card"><div class="mini-chart"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><div><small>Omzet hari ini</small><strong>Kelola dengan mudah</strong></div></div></section>
  <section class="auth-panel"><div class="authbox modern-auth">
   <div class="mobile-brand"><span>T</span><b>TokoFlow</b></div>
   
    <div class="auth-heading"><div class="auth-icon">↗</div><h2>Selamat datang kembali</h2><p>Masuk ke dashboard TokoFlow Anda.</p></div>
    <?php if($err):?><div class="alert bad"><?=e($err)?></div><?php endif;?><?php if($ok):?><div class="alert good"><?=e($ok)?></div><?php endif;?>
    <form method="post" autocomplete="on"><input type="hidden" name="csrf" value="<?=e($_SESSION["csrf"])?>"><input type="hidden" name="auth_action" value="login"><label>Email</label><input name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?=e($_POST["email"]??"")?>" required><label>Password</label><input name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required><button class="primary full auth-submit">Masuk ke TokoFlow <span>→</span></button></form><div class="auth-divider"><span>atau</span></div><a class="secondary-action" href="?auth=register">＋ Buat akun baru</a><div class="demo-note"><b>Demo</b><span></span><span>••••••••</span></div>
   
  </div><div class="auth-footer">TokoFlow · POS & Manajemen Toko.</div></section>
 </div></body></html><?php exit;
}
$uid=(int)$_SESSION["uid"];
if(isset($_GET["logout"])){session_destroy();go("index.php");}
$st=$conn->prepare("SELECT nama,email,role,owner_id FROM users WHERE id=?");$st->bind_param("i",$uid);$st->execute();$me=$st->get_result()->fetch_assoc();$st->close();
$isDeveloper=(($me["role"]??"")==="developer");
$isStaff=(($me["role"]??"")==="kasir" || ($me["role"]??"")==="staff");
$actorUid=$uid;
if($isStaff && !empty($me["owner_id"])) $uid=(int)$me["owner_id"];
if(!empty($_SESSION["must_change_password"]) && ($_GET["p"]??"dashboard")!=="change_password" && $_SERVER["REQUEST_METHOD"]!=="POST"){ go("?p=change_password"); }
$st=$conn->prepare("SELECT plan_code,plan_status,plan_expires_at FROM users WHERE id=?");$st->bind_param("i",$uid);$st->execute();$planRow=$st->get_result()->fetch_assoc();$st->close();
$planCode=$planRow["plan_code"]??"basic";
if($isDeveloper){$planCode="business";}
// TokoFlow V6: semua akun tidak memiliki masa berlaku / unlimited.
if($planRow["plan_status"]!=="active" && !$isDeveloper){$planCode=$planCode ?: "basic";}
$planInfo=planMeta($planCode);
function hasFeature($f){global $planInfo,$isDeveloper;if($isDeveloper)return true;return in_array($f,$planInfo["features"],true);}
function requireFeature($f){if(!hasFeature($f)){go("?p=upgrade&locked=".urlencode($f));}}
function requireOwner(){global $me,$isDeveloper;if(!$isDeveloper && (($me["role"]??"")!=="owner")){go("?p=dashboard&denied=owner");}}
function audit($aksi,$detail=''){global $conn,$uid;$st=$conn->prepare("INSERT INTO audit_logs(user_id,aksi,detail) VALUES(?,?,?)");$st->bind_param("iss",$uid,$aksi,$detail);$st->execute();$st->close();}
require_once "premium_modules.php";
$page=$_GET["p"]??"dashboard";
if($isStaff && in_array($page,["staff","settings","backup","audit_log","developer_panel","store_test"],true)){ go("?p=dashboard&denied=owner"); }
if($_SERVER["REQUEST_METHOD"]==="POST"){check_csrf();$act=$_POST["act"]??""; if($isStaff && !in_array($act,["sale","change_password","shift_open","shift_close","return_sale"],true)){http_response_code(403);exit("Akses ditolak. Fitur ini hanya dapat digunakan oleh Owner.");} if(handlePremiumAction($act)) exit;
 if($act==="product"){
  $id=(int)$_POST["id"];$kode=trim($_POST["kode"]);$nama=trim($_POST["nama"]);$beli=max(0,(float)$_POST["beli"]);$jual=max(0,(float)$_POST["jual"]);$stok=max(0,(int)$_POST["stok"]);$min=max(0,(int)$_POST["min"]);$cat=(int)($_POST["cat"]??0)?:null;
  if($id){$q=$conn->prepare("UPDATE products SET category_id=?,kode=?,nama=?,harga_beli=?,harga_jual=?,stok=?,stok_min=? WHERE id=? AND user_id=?");$q->bind_param("issddiiii",$cat,$kode,$nama,$beli,$jual,$stok,$min,$id,$uid);}
  else{$q=$conn->prepare("INSERT INTO products(user_id,category_id,kode,nama,harga_beli,harga_jual,stok,stok_min) VALUES(?,?,?,?,?,?,?,?)");$q->bind_param("iissddii",$uid,$cat,$kode,$nama,$beli,$jual,$stok,$min);}
  $q->execute();audit("Simpan produk",$nama);go("?p=products");
 }
 if($act==="delete_product"){$id=(int)$_POST["id"];$q=$conn->prepare("UPDATE products SET aktif=0 WHERE id=? AND user_id=?");$q->bind_param("ii",$id,$uid);$q->execute();audit("Hapus produk",(string)$id);go("?p=products");}
 if($act==="category"){$n=trim($_POST["nama"]);if($n){$q=$conn->prepare("INSERT IGNORE INTO categories(user_id,nama) VALUES(?,?)");$q->bind_param("is",$uid,$n);$q->execute();}go("?p=categories");}
 if($act==="expense"){$n=trim($_POST["keterangan"]);$v=(float)$_POST["jumlah"];$q=$conn->prepare("INSERT INTO expenses(user_id,keterangan,jumlah) VALUES(?,?,?)");$q->bind_param("isd",$uid,$n,$v);$q->execute();audit("Tambah pengeluaran",$n);go("?p=expenses");}
 if($act==="stock"){$pid=(int)$_POST["pid"];$type=$_POST["type"];$qty=(int)$_POST["qty"];$note=trim($_POST["note"]);$q=$conn->prepare("SELECT stok FROM products WHERE id=? AND user_id=?");$q->bind_param("ii",$pid,$uid);$q->execute();$old=(int)$q->get_result()->fetch_assoc()["stok"];$new=$type==="masuk"?$old+$qty:($type==="keluar"?max(0,$old-$qty):$qty);$q=$conn->prepare("UPDATE products SET stok=? WHERE id=? AND user_id=?");$q->bind_param("iii",$new,$pid,$uid);$q->execute();$q=$conn->prepare("INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)");$q->bind_param("iiiiiis",$uid,$pid,$type,$qty,$old,$new,$note);$q->execute();audit("Update stok",$note);go("?p=stock");}
 if($act==="customer"){requireFeature("customers");$n=trim($_POST["nama"]);$t=trim($_POST["telepon"]??"");$em=trim($_POST["email"]??"");$a=trim($_POST["alamat"]??"");$q=$conn->prepare("INSERT INTO customers(user_id,nama,telepon,email,alamat) VALUES(?,?,?,?,?)");$q->bind_param("issss",$uid,$n,$t,$em,$a);$q->execute();audit("Tambah pelanggan",$n);go("?p=customers");}
 if($act==="supplier"){requireFeature("suppliers");$n=trim($_POST["nama"]);$t=trim($_POST["telepon"]??"");$em=trim($_POST["email"]??"");$a=trim($_POST["alamat"]??"");$q=$conn->prepare("INSERT INTO suppliers(user_id,nama,telepon,email,alamat) VALUES(?,?,?,?,?)");$q->bind_param("issss",$uid,$n,$t,$em,$a);$q->execute();audit("Tambah supplier",$n);go("?p=suppliers");}
 if($act==="purchase"){requireFeature("purchases");$sid=(int)($_POST["supplier_id"]??0);$pid=(int)$_POST["product_id"];$qty=max(1,(int)$_POST["qty"]);$harga=max(0,(float)$_POST["harga"]);$q=$conn->prepare("SELECT nama,stok FROM products WHERE id=? AND user_id=? FOR UPDATE");$q->bind_param("ii",$pid,$uid);$q->execute();$prod=$q->get_result()->fetch_assoc();if(!$prod)die("Produk tidak ditemukan.");$total=$qty*$harga;$inv="PO-".date("ymdHis")."-".random_int(10,99);$conn->begin_transaction();try{$q=$conn->prepare("INSERT INTO purchases(user_id,supplier_id,invoice,total) VALUES(?,?,?,?)");$q->bind_param("iisd",$uid,$sid,$inv,$total);$q->execute();$purchaseId=$q->insert_id;$q=$conn->prepare("INSERT INTO purchase_items(purchase_id,product_id,qty,harga,subtotal) VALUES(?,?,?,?,?)");$q->bind_param("iiidd",$purchaseId,$pid,$qty,$harga,$total);$q->execute();$new=$prod["stok"]+$qty;$q=$conn->prepare("UPDATE products SET stok=?,harga_beli=? WHERE id=? AND user_id=?");$q->bind_param("idii",$new,$harga,$pid,$uid);$q->execute();$type="masuk";$note="Pembelian ".$inv;$q=$conn->prepare("INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)");$q->bind_param("iiiiiis",$uid,$pid,$type,$qty,$prod["stok"],$new,$note);$q->execute();$conn->commit();audit("Pembelian",$inv);go("?p=purchases");}catch(Throwable $e){$conn->rollback();die(e($e->getMessage()));}}
 if($act==="reset_cashier_password"){
  requireOwner();
  $cashierId=(int)($_POST["cashier_id"]??0);
  $newPw=$_POST["new_password"]??"";
  if($cashierId<=0) die("Akun kasir tidak valid.");
  if(strlen($newPw)<8) die("Password baru minimal 8 karakter.");
  $q=$conn->prepare("SELECT id,nama,email FROM users WHERE id=? AND owner_id=? AND role IN ('kasir','staff') LIMIT 1");
  $q->bind_param("ii",$cashierId,$actorUid);$q->execute();$cashier=$q->get_result()->fetch_assoc();$q->close();
  if(!$cashier) die("Kasir tidak ditemukan atau bukan bagian dari toko Anda.");
  $hash=password_hash($newPw,PASSWORD_DEFAULT);
  $q=$conn->prepare("UPDATE users SET password=?,must_change_password=1 WHERE id=? AND owner_id=? AND role IN ('kasir','staff')");
  $q->bind_param("sii",$hash,$cashierId,$actorUid);$q->execute();$q->close();
  audit("Reset password kasir",$cashier["email"]);
  go("?p=owner_panel&reset=1");
 }
 if($act==="delete_cashier"){
  requireOwner();
  $cashierId=(int)($_POST["cashier_id"]??0);
  if($cashierId<=0) die("Akun kasir tidak valid.");
  $q=$conn->prepare("SELECT id,nama,email FROM users WHERE id=? AND owner_id=? AND role IN ('kasir','staff') LIMIT 1");
  $q->bind_param("ii",$cashierId,$actorUid);$q->execute();$cashier=$q->get_result()->fetch_assoc();$q->close();
  if(!$cashier) die("Kasir tidak ditemukan atau bukan bagian dari toko Anda.");
  $q=$conn->prepare("DELETE FROM users WHERE id=? AND owner_id=? AND role IN ('kasir','staff')");
  $q->bind_param("ii",$cashierId,$actorUid);$q->execute();$q->close();
  audit("Hapus kasir",$cashier["email"]);
  go("?p=owner_panel&deleted=1");
 }
 if($act==="staff"){requireFeature("staff");requireOwner();$n=trim($_POST["nama"]??"");$em=strtolower(trim($_POST["email"]??""));$pw=$_POST["password"]??"";if(mb_strlen($n)<2)die("Nama kasir minimal 2 karakter.");if(!filter_var($em,FILTER_VALIDATE_EMAIL))die("Email kasir tidak valid.");if(strlen($pw)<8)die("Password kasir minimal 8 karakter.");$hash=password_hash($pw,PASSWORD_DEFAULT);$role="kasir";$ownerId=(int)$actorUid;$q=$conn->prepare("INSERT INTO users(nama,email,password,role,owner_id,plan_code,plan_status,must_change_password) VALUES(?,?,?,?,?,'basic','active',1)");$q->bind_param("ssssi",$n,$em,$hash,$role,$ownerId);if(!$q->execute())die("Email kasir sudah digunakan atau data tidak valid.");audit("Tambah kasir",$em);go("?p=staff");}
 if($act==="change_password"){
  $current=$_POST["current_password"]??"";$new=$_POST["new_password"]??"";$confirm=$_POST["confirm_password"]??"";
  $q=$conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");$q->bind_param("i",$actorUid);$q->execute();$row=$q->get_result()->fetch_assoc();$q->close();
  if(!$row || !password_verify($current,$row["password"])) die("Password saat ini salah.");
  if(strlen($new)<8) die("Password baru minimal 8 karakter.");
  if($new!==$confirm) die("Konfirmasi password tidak sama.");
  if(password_verify($new,$row["password"])) die("Password baru harus berbeda dari password lama.");
  $hash=password_hash($new,PASSWORD_DEFAULT);$q=$conn->prepare("UPDATE users SET password=?,must_change_password=0 WHERE id=?");$q->bind_param("si",$hash,$actorUid);$q->execute();$q->close();
  unset($_SESSION["must_change_password"]); audit("Ganti password","Password akun berhasil diperbarui"); go("?p=settings&password_changed=1");
 }
 if($act==="settings"){
  $shop=trim($_POST["shop"]);$addr=trim($_POST["addr"]);$tel=trim($_POST["tel"]);$foot=trim($_POST["foot"]);
  $theme=hasFeature("custom_theme")&&in_array($_POST["theme"]??"indigo",["indigo","emerald","orange","rose","blue","violet"],true)?$_POST["theme"]:(($conn->query("SELECT theme FROM settings WHERE user_id=$uid")->fetch_assoc()["theme"]??"indigo"));
  $mode=($_POST["mode"]??"light")==="dark"?"dark":"light";
  $q=$conn->prepare("INSERT INTO settings(user_id,nama_usaha,alamat,telepon,footer,theme,mode) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nama_usaha=VALUES(nama_usaha),alamat=VALUES(alamat),telepon=VALUES(telepon),footer=VALUES(footer),theme=VALUES(theme),mode=VALUES(mode)");
  $q->bind_param("issssss",$uid,$shop,$addr,$tel,$foot,$theme,$mode);$q->execute();audit("Pengaturan toko","Tampilan/identitas toko diperbarui");go("?p=settings");
}
 if($act==="sale"){
  $cart=json_decode($_POST["cart"]??"[]",true)?:[];$discount=max(0,(float)$_POST["discount"]);$pay=max(0,(float)$_POST["pay"]);$promoCode=strtoupper(trim($_POST["promo_code"]??""));$dueDate=$_POST["due_date"]??date("Y-m-d",strtotime("+7 days"));
  $method=in_array($_POST["method"]??"Tunai",["Tunai","QRIS","Transfer","Debit","Piutang"],true)?$_POST["method"]:"Tunai";
  $customer=trim($_POST["customer"])?: "Umum";$ref=trim($_POST["payment_ref"]??"");
  if(!$cart)die("Keranjang kosong.");
  $conn->begin_transaction();
  try{
   $sub=0;$valid=[];
   foreach($cart as $x){
    $pid=(int)$x["id"];$qty=(int)$x["qty"];
    $q=$conn->prepare("SELECT * FROM products WHERE id=? AND user_id=? AND aktif=1 FOR UPDATE");$q->bind_param("ii",$pid,$uid);$q->execute();$p=$q->get_result()->fetch_assoc();
    if(!$p||$qty<1||$qty>$p["stok"])throw new Exception("Stok ".$p["nama"]." tidak cukup.");
    $line=$p["harga_jual"]*$qty;$sub+=$line;$valid[]=[$p,$qty,$line];
   }
   $discount=min($discount,$sub);
   if($promoCode!==""){
    $pq=$conn->prepare("SELECT jenis,nilai,min_total FROM promos WHERE user_id=? AND kode=? AND aktif=1 AND CURDATE() BETWEEN mulai AND berakhir LIMIT 1");
    $pq->bind_param("is",$uid,$promoCode);$pq->execute();$promo=$pq->get_result()->fetch_assoc();
    if($promo && $sub >= (float)$promo["min_total"]){
     $promoDisc=$promo["jenis"]==="persen"?($sub*(float)$promo["nilai"]/100):(float)$promo["nilai"];
     $discount=min($sub,$discount+$promoDisc);
    }
   }
   $total=$sub-$discount;
   if($method==="Tunai"&&$pay<$total)throw new Exception("Pembayaran tunai kurang ".rp($total-$pay).".");
   if($method==="Piutang"){$pay=0;$ref=$ref!==""?$ref:"Piutang tempo";}
   elseif($method!=="Tunai"){$pay=$total;if($ref==="")$ref="Pembayaran ".$method;}
   $change=$method==="Tunai"?$pay-$total:0;
   $inv="INV-".date("ymdHis")."-".random_int(10,99);
   $q=$conn->prepare("INSERT INTO sales(user_id,invoice,pelanggan,subtotal,diskon,total,bayar,kembalian,metode,payment_ref) VALUES(?,?,?,?,?,?,?,?,?,?)");
   $q->bind_param("issdddddss",$uid,$inv,$customer,$sub,$discount,$total,$pay,$change,$method,$ref);$q->execute();$sid=$q->insert_id;
   if($method==="Piutang"){
    $dueDate=max($dueDate,date("Y-m-d"));$status="belum_lunas";
    $rq=$conn->prepare("INSERT INTO receivables(user_id,sale_id,invoice,customer_name,total,paid,due_date,status) VALUES(?,?,?,?,?,?,?,?)");
    $rq->bind_param("iissddss",$uid,$sid,$inv,$customer,$total,$pay,$dueDate,$status);$rq->execute();
   }
   foreach($valid as [$p,$qty,$line]){
    $q=$conn->prepare("INSERT INTO sale_items(sale_id,product_id,nama_produk,qty,harga,subtotal) VALUES(?,?,?,?,?,?)");$q->bind_param("iisidd",$sid,$p["id"],$p["nama"],$qty,$p["harga_jual"],$line);$q->execute();
    $new=$p["stok"]-$qty;$q=$conn->prepare("UPDATE products SET stok=? WHERE id=? AND user_id=?");$q->bind_param("iii",$new,$p["id"],$uid);$q->execute();
    $type="keluar";$note="Penjualan ".$inv;$q=$conn->prepare("INSERT INTO stock_logs(user_id,product_id,jenis,qty,stok_sebelum,stok_sesudah,catatan) VALUES(?,?,?,?,?,?,?)");$q->bind_param("iiiiiis",$uid,$p["id"],$type,$qty,$p["stok"],$new,$note);$q->execute();
   }
   $conn->commit();audit("Penjualan",$inv." · ".rp($total));go("?p=receipt&id=".$sid);
  }catch(Throwable $ex){$conn->rollback();die(e($ex->getMessage()));}
 }
 if($act==="purchase_premium"){
  $plan=in_array($_POST["plan"]??"",["pro","business"],true)?$_POST["plan"]:"pro";
  $method=in_array($_POST["method"]??"",["transfer","qris","manual"],true)?$_POST["method"]:"manual";
  $pi=planMeta($plan);
  $q=$conn->prepare("INSERT INTO premium_orders(user_id,plan_code,amount,method,status) VALUES(?,?,?,?,'pending')");
  $q->bind_param("isds",$uid,$plan,$pi["price"],$method);$q->execute();
  audit("Pengajuan pembelian Premium",strtoupper($plan)." via ".$method);
  go("?p=upgrade&order=created");
}
if($act==="activate_license"){
  $code=strtoupper(trim($_POST["license"]??""));
  $st=$conn->prepare("SELECT id,plan_code,days,used_by FROM licenses WHERE code=? LIMIT 1");$st->bind_param("s",$code);$st->execute();$lic=$st->get_result()->fetch_assoc();$st->close();
  if(!$lic)die("Kode lisensi tidak ditemukan.");
  if($lic["used_by"]!==null && (int)$lic["used_by"]!==$uid)die("Kode lisensi sudah digunakan.");
  $plan=$lic["plan_code"];$expires=null;
  $q=$conn->prepare("UPDATE users SET plan_code=?,plan_status='active',plan_expires_at=NULL WHERE id=?");$q->bind_param("si",$plan,$uid);$q->execute();
  $q=$conn->prepare("UPDATE licenses SET used_by=?,used_at=NOW() WHERE id=?");$q->bind_param("ii",$uid,$lic["id"]);$q->execute();audit("Aktivasi paket","Paket ".$plan);go("?p=upgrade&activated=1");
 }
 if($act==="developer_create_customer"){
  if(!$isDeveloper)die("Akses ditolak.");
  $nama=trim($_POST["nama"]??"");
  $shop=trim($_POST["shop"]??"");
  $em=strtolower(trim($_POST["email"]??""));
  $plan=in_array($_POST["plan"]??"",["basic","pro","business"],true)?$_POST["plan"]:"basic";
  $days=0;
  if(mb_strlen($nama)<2 || mb_strlen($shop)<2)die("Nama pemilik dan nama toko minimal 2 karakter.");
  if(!filter_var($em,FILTER_VALIDATE_EMAIL))die("Email pelanggan tidak valid.");
  $st=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");$st->bind_param("s",$em);$st->execute();$exists=$st->get_result()->fetch_assoc();$st->close();
  if($exists)die("Email pelanggan sudah terdaftar.");
  $hash=password_hash(bin2hex(random_bytes(8)),PASSWORD_DEFAULT);
  $tempPassword="TF@".strtoupper(bin2hex(random_bytes(4)));
  $hash=password_hash($tempPassword,PASSWORD_DEFAULT);
  $code="TF-".strtoupper(bin2hex(random_bytes(3)))."-".strtoupper(bin2hex(random_bytes(3)))."-".strtoupper(bin2hex(random_bytes(2)));
  $expires=null;
  $role="owner";$status="active";
  $conn->begin_transaction();
  try{
    $st=$conn->prepare("INSERT INTO users(nama,email,password,role,plan_code,plan_status,plan_expires_at,must_change_password) VALUES(?,?,?,?,?,?,?,1)");
    $st->bind_param("sssssss",$nama,$em,$hash,$role,$plan,$status,$expires);$st->execute();$customerId=$st->insert_id;$st->close();
    $footer="Terima kasih sudah berbelanja.";$theme="indigo";$mode="light";
    $st=$conn->prepare("INSERT INTO settings(user_id,nama_usaha,footer,theme,mode) VALUES(?,?,?,?,?)");
    $st->bind_param("issss",$customerId,$shop,$footer,$theme,$mode);$st->execute();$st->close();
    $cat="Umum";$st=$conn->prepare("INSERT INTO categories(user_id,nama) VALUES(?,?)");$st->bind_param("is",$customerId,$cat);$st->execute();$st->close();
    $st=$conn->prepare("INSERT INTO licenses(code,plan_code,days,used_by,used_at) VALUES(?,?,?, ?,NOW())");
    $st->bind_param("ssii",$code,$plan,$days,$customerId);$st->execute();$licenseId=$st->insert_id;$st->close();
    $event="customer_created";$detail=$plan." / unlimited / ".$em;
    $st=$conn->prepare("INSERT INTO license_events(license_id,admin_id,action,detail) VALUES(?,?,?,?)");$st->bind_param("iiss",$licenseId,$uid,$event,$detail);$st->execute();$st->close();
    $conn->commit();
    $_SESSION["new_customer"]=["nama"=>$nama,"shop"=>$shop,"email"=>$em,"password"=>$tempPassword,"license"=>$code,"plan"=>$plan,"expires"=>"UNLIMITED"];
    go("?p=developer_panel&customer_created=1");
  }catch(Throwable $ex){$conn->rollback();die("Gagal membuat akun pelanggan: ".e($ex->getMessage()));}
 }
 if($act==="developer_create_license"){
  if(!$isDeveloper)die("Akses ditolak.");
  $plan=in_array($_POST["plan"]??"",["basic","pro","business"],true)?$_POST["plan"]:"pro";
  $days=0;
  $code="TF-".strtoupper(bin2hex(random_bytes(3)))."-".strtoupper(bin2hex(random_bytes(3)))."-".strtoupper(bin2hex(random_bytes(2)));
  $q=$conn->prepare("INSERT INTO licenses(code,plan_code,days) VALUES(?,?,?)");$q->bind_param("ssi",$code,$plan,$days);$q->execute();$licenseId=$q->insert_id;$q->close();
  $q=$conn->prepare("INSERT INTO license_events(license_id,admin_id,action,detail) VALUES(?,?,?,?)");$event="created";$detail=$plan." / unlimited";$q->bind_param("iiss",$licenseId,$uid,$event,$detail);$q->execute();$q->close();
  go("?p=developer_panel&created=".urlencode($code));
 }
 if($act==="developer_update_customer"){
  if(!$isDeveloper)die("Akses ditolak.");
  $customerId=(int)($_POST["customer_id"]??0);$plan=in_array($_POST["plan"]??"",["basic","pro","business"],true)?$_POST["plan"]:"basic";$status=in_array($_POST["status"]??"",["active","suspended"],true)?$_POST["status"]:"active";
  $q=$conn->prepare("UPDATE users SET plan_code=?,plan_status=?,plan_expires_at=NULL WHERE id=? AND role<>'developer'");$q->bind_param("ssi",$plan,$status,$customerId);$q->execute();$q->close();
  audit("Developer update customer","user_id=".$customerId." plan=".$plan." status=".$status);go("?p=developer_panel&updated=1");
 }
}
$settings=$conn->query("SELECT * FROM settings WHERE user_id=$uid")->fetch_assoc()?:[];
$shop=$settings["nama_usaha"]??"TokoFlow";$theme=$settings["theme"]??"indigo";$mode=in_array(($settings["mode"]??"light"),["light","dark"],true)?$settings["mode"]:"light";
function headerUI($title,$page,$shop,$me){global $theme,$mode,$planInfo,$isDeveloper,$isStaff;?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><link rel="manifest" href="manifest.json"><meta name="theme-color" content="#121826"><link rel="apple-touch-icon" href="icons/icon-192.png"><title><?=e($title)?> · <?=e($shop)?></title><link rel="stylesheet" href="assets/style.css?v=saas-v7"><script defer src="assets/app.js"></script></head><body class="theme-<?=e($theme)?> mode-<?=e($mode)?>"><aside><div class="brand"><span>T</span><div><b>TokoFlow</b><small>POS & Manajemen Toko</small></div></div><nav><?php $groups=[['UTAMA',[['dashboard','⌂','Dashboard',''],['pos','＋','Kasir',''],['history','◷','Riwayat Transaksi','']]],['MANAJEMEN',[['products','▦','Produk',''],['stock','↕','Stok',''],['purchases','↙','Pembelian','purchases'],['suppliers','◇','Supplier','suppliers'],['customers','♙','Pelanggan','customers']]],['LAPORAN',[['reports','▥','Laporan','']]],['PREMIUM',[['promos','✦','Promo','promos'],['returns','↩','Retur','returns'],['receivables','Rp','Piutang','receivables'],['shifts','◷','Shift Kasir','shifts'],['staff','♟','Tim','staff'],['backup','↥','Backup','backup'],['settings','⚙','Pengaturan',''],['audit_log','⌁','Audit Log','audit']]]];if((($me['role']??'')==='owner') && !$isDeveloper) $groups[]=['OWNER',[['owner_panel','♛','Panel Owner','']]]; if($isDeveloper)$groups[]=['DEVELOPER',[['developer_panel','⚙','Panel Developer',''],['store_test','✓','Uji Toko Nyata','']]];foreach($groups as $group):?><div class="nav-group-title"><?=$group[0]?></div><?php foreach($group[1] as $v):$k=$v[0];if($isStaff && in_array($k,["staff","settings","backup","audit_log"],true)) continue;$locked=$v[3]&&!hasFeature($v[3]);?><a class="<?=$page===$k?'on':''?> <?=$locked?'locked':''?>" href="?p=<?=$locked?'upgrade&locked='.urlencode($v[3]):$k?>"><span class="navicon"><?=$v[1]?></span><span><?=$v[2]?></span><?php if($locked):?><?php endif;?></a><?php endforeach;endforeach;?></nav><div class="sideuser"><b><?=e($me["nama"])?></b><small><?=e($me["role"])?></small><span class="plan-mini"><?=e($planInfo["name"])?></span><?php if(!$isStaff): ?><a href="?p=upgrade">Kelola Paket</a><?php endif; ?><a href="?logout=1">Keluar</a></div></aside><main><header class="tf-topbar"><button type="button" class="mobile-menu-btn" aria-label="Buka menu" onclick="toggleMobileMenu()">☰</button><div class="tf-title"><small><?=date("d M Y")?></small><h2><?=e($title)?></h2></div><div class="tf-global-tools"><form class="tf-search" method="get" action="index.php"><input type="hidden" name="p" value="history"><span>⌕</span><input name="q" placeholder="Search orders, products..." aria-label="Search"><button type="submit" aria-label="Cari">↵</button></form><div class="tf-top-actions"><?php if(!$isStaff): ?><a class="button" href="?p=settings">⚙ Pengaturan</a><?php endif; ?><a class="button danger" href="?logout=1">↪ Keluar</a><a class="quick" href="?p=pos">＋ Transaksi</a></div></div></header><?php }
function footerUI(){?><script>if("serviceWorker" in navigator){navigator.serviceWorker.register("sw.js").catch(()=>{});}function toggleMobileMenu(){document.body.classList.toggle("menu-open");}document.addEventListener("click",e=>{if(e.target.closest("main")&&!e.target.closest(".mobile-menu-btn"))document.body.classList.remove("menu-open");});</script></main></body></html><?php }
$pageTitles=["dashboard"=>"Dashboard","pos"=>"Kasir","history"=>"Riwayat Transaksi","advanced_reports"=>"Analitik","analytics"=>"Analitik","reports"=>"Laporan","products"=>"Produk","stock"=>"Stok","purchases"=>"Pembelian","suppliers"=>"Supplier","customers"=>"Pelanggan","settings"=>"Pengaturan","upgrade"=>"Kelola Paket","developer_panel"=>"Panel Developer","owner_panel"=>"Panel Owner","change_password"=>"Ganti Password"];
if($isStaff && in_array($page,["settings","backup","audit_log","developer_panel","store_test","upgrade","staff"],true)){go("?p=dashboard&denied=owner");}
if($page==="owner_panel" && (($me["role"]??"")!=="owner" || $isDeveloper)){go("?p=dashboard&denied=owner");}
if($page==="store_test"){require "store_test.php";exit;} if($page==="audit_log"){require "audit_log.php";exit;}
headerUI($pageTitles[$page]??ucfirst(str_replace("_"," ",$page)),$page,$shop,$me);
if($page==="developer_panel"){require "developer_panel.php";footerUI();exit;}
$gates=["customers"=>"customers","suppliers"=>"suppliers","purchases"=>"purchases","promos"=>"promos","returns"=>"returns","opname"=>"stock_opname","receivables"=>"receivables","shifts"=>"shifts","staff"=>"staff","advanced_reports"=>"advanced_reports","backup"=>"backup"];
if(isset($gates[$page]))requireFeature($gates[$page]);
if($page==="dashboard"){
 $d=$conn->query("SELECT COUNT(*) c,COALESCE(SUM(total),0) t FROM sales WHERE user_id=$uid AND DATE(created_at)=CURDATE()")->fetch_assoc();
 $profit=$conn->query("SELECT COALESCE(SUM(si.qty*(si.harga-p.harga_beli)),0) p FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id WHERE s.user_id=$uid AND DATE(s.created_at)=CURDATE()")->fetch_assoc()["p"];
 $lowCount=(int)$conn->query("SELECT COUNT(*) c FROM products WHERE user_id=$uid AND aktif=1 AND stok<=stok_min")->fetch_assoc()["c"];
 $yday=$conn->query("SELECT COUNT(*) c,COALESCE(SUM(total),0) t FROM sales WHERE user_id=$uid AND DATE(created_at)=DATE_SUB(CURDATE(),INTERVAL 1 DAY)")->fetch_assoc();
 $yprofit=$conn->query("SELECT COALESCE(SUM(si.qty*(si.harga-p.harga_beli)),0) p FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id WHERE s.user_id=$uid AND DATE(s.created_at)=DATE_SUB(CURDATE(),INTERVAL 1 DAY)")->fetch_assoc()["p"];
 $receivableTotal=(float)$conn->query("SELECT COALESCE(SUM(total-paid),0) t FROM receivables WHERE user_id=$uid AND status<>'lunas'")->fetch_assoc()["t"];
 $pct=function($now,$prev){$now=(float)$now;$prev=(float)$prev;if($prev==0)return $now>0?100:0;return (($now-$prev)/abs($prev))*100;};
 $salesTrend=$pct($d["t"],$yday["t"]);$trxTrend=$pct($d["c"],$yday["c"]);$profitTrend=$pct($profit,$yprofit);

 // Last 7 days for the dashboard chart.
 $chartRows=[];$chartMax=1;
 $chartQ=$conn->query("SELECT DATE(created_at) d,COALESCE(SUM(total),0) total FROM sales WHERE user_id=$uid AND DATE(created_at)>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY d");
 while($cr=$chartQ->fetch_assoc()){$chartRows[$cr["d"]]=(float)$cr["total"];$chartMax=max($chartMax,(float)$cr["total"]);}

 $points=[];
 for($i=6;$i>=0;$i--){
   $day=date("Y-m-d",strtotime("-$i day"));
   $val=$chartRows[$day]??0;
   $x=12 + (6-$i)*112;
   $y=178 - (($val/$chartMax)*142);
   $points[]=["x"=>$x,"y"=>$y,"value"=>$val,"label"=>date("d",strtotime($day))];
 }
 $greenPath="";$areaPath="";
 foreach($points as $idx=>$pt){$greenPath.=($idx===0?"M":" L").$pt["x"]." ".$pt["y"];}
 $areaPath=$greenPath." L ".$points[count($points)-1]["x"]." 178 L ".$points[0]["x"]." 178 Z";

 $catRows=[];
 $catQ=$conn->query("SELECT COALESCE(c.nama,'Umum') kategori,COALESCE(SUM(si.subtotal),0) omzet FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id LEFT JOIN categories c ON c.id=p.category_id WHERE s.user_id=$uid AND DATE(s.created_at)>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY COALESCE(c.nama,'Umum') ORDER BY omzet DESC LIMIT 6");
 while($cr=$catQ->fetch_assoc())$catRows[]=$cr;
 $catMax=1;foreach($catRows as $cr)$catMax=max($catMax,(float)$cr["omzet"]);

 $recent=$conn->query("SELECT invoice,pelanggan,created_at,metode,total FROM sales WHERE user_id=$uid ORDER BY id DESC LIMIT 5");

 echo '<div class="tf-stat-grid dashboard-kpis">
   <div class="tf-stat kpi-indigo"><span class="tf-stat-icon">◉</span><div><small>Omzet Hari Ini</small><strong>'.rp($d["t"]).'</strong><em class="'.($salesTrend>=0?'trend-up':'trend-down').'">'.($salesTrend>=0?'↗':'↘').' '.number_format(abs($salesTrend),1).'% <span>vs kemarin</span></em></div></div>
   <div class="tf-stat kpi-blue"><span class="tf-stat-icon">🛒</span><div><small>Total Transaksi</small><strong>'.$d["c"].'</strong><em class="'.($trxTrend>=0?'trend-up':'trend-down').'">'.($trxTrend>=0?'↗':'↘').' '.number_format(abs($trxTrend),1).'% <span>vs kemarin</span></em></div></div>
   <div class="tf-stat kpi-emerald"><span class="tf-stat-icon">▣</span><div><small>Laba Kotor</small><strong>'.rp($profit).'</strong><em class="'.($profitTrend>=0?'trend-up':'trend-down').'">'.($profitTrend>=0?'↗':'↘').' '.number_format(abs($profitTrend),1).'% <span>vs kemarin</span></em></div></div>
   <div class="tf-stat kpi-amber"><span class="tf-stat-icon">Rp</span><div><small>Piutang</small><strong>'.rp($receivableTotal).'</strong><em>saldo belum lunas</em></div></div>
 </div>
 <div class="dashboard-secondary"><span>Stok menipis <b>'.$lowCount.'</b> produk</span><a href="?p=products">Kelola produk →</a></div>';

 echo '<div class="tf-chart-grid">
   <section class="tf-panel tf-sales">
     <div class="tf-panel-head"><h2>Sales Performance</h2><div class="tf-legend"><span><i class="tf-dot green"></i>7 Hari</span><span><i class="tf-dot blue"></i>Tren</span></div></div>
     <div class="tf-line-chart">
       <div class="tf-yaxis"><span>'.rp($chartMax).'</span><span>'.rp($chartMax*.75).'</span><span>'.rp($chartMax*.5).'</span><span>'.rp($chartMax*.25).'</span><span>Rp 0</span></div>
       <svg viewBox="0 0 690 205" preserveAspectRatio="none">
         <defs><linearGradient id="tfFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#20a27b" stop-opacity=".18"/><stop offset="1" stop-color="#20a27b" stop-opacity="0"/></linearGradient></defs>
         <g class="tf-grid-lines"><line x1="0" y1="30" x2="690" y2="30"/><line x1="0" y1="72" x2="690" y2="72"/><line x1="0" y1="114" x2="690" y2="114"/><line x1="0" y1="156" x2="690" y2="156"/><line x1="0" y1="190" x2="690" y2="190"/></g>
         <path class="tf-area" d="'.e($areaPath).'"/>
         <path class="tf-green-line" d="'.e($greenPath).'"/>
         <path class="tf-blue-line" d="M12 175 C75 160 110 145 172 135 S265 160 315 122 S405 145 460 98 S555 130 678 32"/>
         <g class="tf-points">';
 foreach($points as $pt) echo '<circle cx="'.$pt["x"].'" cy="'.$pt["y"].'" r="4"></circle>';
 echo '</g></svg>
       <div class="tf-xaxis">';
 foreach($points as $pt) echo '<span>'.$pt["label"].'</span>';
 echo '</div>
     </div>
   </section>
   <section class="tf-panel tf-categories">
     <div class="tf-panel-head"><h2>Top Categories</h2><a href="?p=reports">Lihat</a></div>
     <div class="tf-category-chart">';
 foreach($catRows as $cr){
   $h=max(10,round(((float)$cr["omzet"]/$catMax)*150));
   echo '<div class="tf-cat"><div class="tf-bars"><i style="height:'.round($h*.82).'px"></i><b style="height:'.$h.'px"></b></div><span>'.e(mb_strimwidth($cr["kategori"],0,8,"…")).'</span></div>';
 }
 if(!$catRows) echo '<div class="tf-empty">Belum ada data kategori penjualan.</div>';
 echo '</div>
   </section>
 </div>';

 echo '<section class="tf-panel tf-orders">
   <div class="tf-panel-head"><h2>Recent Orders</h2><div class="tf-table-actions"><a class="tf-round" href="?p=history">‹</a><a class="tf-round" href="?p=history">›</a><a class="tf-filter" href="?p=history">☷ &nbsp; Semua transaksi</a></div></div>
   <div class="tf-table-wrap"><table><thead><tr><th>Order ID</th><th>Pelanggan</th><th>Tanggal</th><th>Metode</th><th>Status</th><th>Total</th></tr></thead><tbody>';
 if($recent->num_rows){
   while($r=$recent->fetch_assoc()){
     $method=e($r["metode"]??"Tunai");
     echo '<tr><td>'.e($r["invoice"]).'</td><td>'.e($r["pelanggan"]??"Umum").'</td><td>'.date("d M",strtotime($r["created_at"])).'</td><td>'.$method.'</td><td><span class="tf-status">Selesai</span></td><td>'.rp($r["total"]).'</td></tr>';
   }
 }else{
   echo '<tr><td colspan="6"><div class="tf-empty">Belum ada transaksi. Mulai dari Kasir untuk membuat transaksi pertama.</div></td></tr>';
 }
 echo '</tbody></table></div>
 </section>';

 echo '<div class="tf-quick"><a href="?p=pos">＋ Transaksi Baru</a><a href="?p=products">＋ Tambah Produk</a><a href="?p=stock">↕ Kelola Stok</a><a href="?p=history">▤ Lihat Transaksi</a><a href="?p=reports">▥ Lihat Laporan</a></div>';
}

elseif($page==="history"){
 $search=trim($_GET["q"]??"");$safeSearch=$conn->real_escape_string($search);
 $whereSearch=$search!==""?" AND (invoice LIKE '%$safeSearch%' OR pelanggan LIKE '%$safeSearch%' OR metode LIKE '%$safeSearch%')":"";
 $rows=$conn->query("SELECT * FROM sales WHERE user_id=$uid $whereSearch ORDER BY id DESC LIMIT 100");
 echo '<div class="toolbar"><div><b>Riwayat Transaksi</b><small class="muted">100 transaksi terakhir</small></div><a class="button" href="?p=pos">+ Transaksi</a></div><div class="panel tablewrap"><table><thead><tr><th>Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Metode</th><th>Total</th><th>Bayar</th><th>Kembalian</th><th></th></tr></thead><tbody>';
 while($r=$rows->fetch_assoc()){echo '<tr><td><b>'.e($r["invoice"]).'</b></td><td>'.date("d/m/Y H:i",strtotime($r["created_at"])).'</td><td>'.e($r["pelanggan"]).'</td><td><span class="tag">'.e($r["metode"]).'</span></td><td>'.rp($r["total"]).'</td><td>'.rp($r["bayar"]).'</td><td><b>'.rp($r["kembalian"]).'</b></td><td><a class="small button" href="?p=receipt&id='.$r["id"].'">Struk</a></td></tr>';}
 echo '</tbody></table></div>';
}
elseif($page==="products"){
 $cats=$conn->query("SELECT id,nama FROM categories WHERE user_id=$uid ORDER BY nama");$prods=$conn->query("SELECT p.*,c.nama cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.user_id=$uid AND p.aktif=1 ORDER BY p.id DESC");
 echo '<div class="toolbar"><input id="search" placeholder="Cari produk..."><button class="primary" onclick="openM()">+ Produk</button></div><div class="panel tablewrap"><table id="tbl"><thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th></th></tr></thead><tbody>';
 while($p=$prods->fetch_assoc()){echo '<tr><td><b>'.e($p["nama"]).'</b><small>'.e($p["kode"]).'</small></td><td>'.e($p["cat"]??"Umum").'</td><td>'.rp($p["harga_jual"]).'</td><td>'.$p["stok"].' '.$p["satuan"].'</td><td><button class="small" onclick=\'editP('.json_encode($p).')\'>Edit</button> <form class="inline" method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="delete_product"><input type="hidden" name="id" value="'.$p["id"].'"><button class="small danger" onclick="return confirm(\'Arsipkan produk?\')">Arsip</button></form></td></tr>';}
 echo '</tbody></table></div><div class="modal" id="modal"><div class="modalbox"><button class="x" onclick="closeM()">×</button><h2 id="mt">Produk Baru</h2><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="product"><input type="hidden" name="id" id="pid"><label>Kode</label><input name="kode" id="pk" required><label>Nama</label><input name="nama" id="pn" required><div class="twocol"><div><label>Harga beli</label><input type="number" name="beli" id="pb" min="0"></div><div><label>Harga jual</label><input type="number" name="jual" id="pj" min="1" required></div><div><label>Stok</label><input type="number" name="stok" id="ps" min="0"></div><div><label>Stok minimum</label><input type="number" name="min" id="pm" min="0" value="5"></div></div><label>Kategori</label><select name="cat" id="pc"><option value="">Umum</option>';while($c=$cats->fetch_assoc())echo '<option value="'.$c["id"].'">'.e($c["nama"]).'</option>';echo '</select><button class="primary full">Simpan</button></form></div></div>';
}
elseif($page==="categories"){
 $cats=$conn->query("SELECT c.id,c.nama,COUNT(p.id) n FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.aktif=1 WHERE c.user_id=$uid GROUP BY c.id ORDER BY c.nama");echo '<div class="panel narrow"><form class="inlineform" method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="category"><input name="nama" placeholder="Nama kategori" required><button class="primary">Tambah</button></form>';while($c=$cats->fetch_assoc())echo '<div class="row"><div><b>'.e($c["nama"]).'</b><small>'.$c["n"].' produk</small></div></div>';echo '</div>';
}
elseif($page==="stock"){
 $prods=$conn->query("SELECT id,nama,stok,satuan FROM products WHERE user_id=$uid AND aktif=1 ORDER BY nama");$logs=$conn->query("SELECT l.*,p.nama FROM stock_logs l JOIN products p ON p.id=l.product_id WHERE l.user_id=$uid ORDER BY l.id DESC LIMIT 20");echo '<div class="twopan"><div class="panel"><h3>Update stok</h3><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="stock"><label>Produk</label><select name="pid">';while($p=$prods->fetch_assoc())echo '<option value="'.$p["id"].'">'.e($p["nama"]).' · '.$p["stok"].' '.$p["satuan"].'</option>';echo '</select><label>Jenis</label><select name="type"><option value="masuk">Masuk</option><option value="keluar">Keluar</option><option value="set">Set stok</option></select><label>Jumlah</label><input type="number" name="qty" min="1" required><label>Catatan</label><input name="note"><button class="primary full">Simpan</button></form></div><div class="panel"><h3>Riwayat</h3>';while($l=$logs->fetch_assoc())echo '<div class="row"><div><b>'.e($l["nama"]).'</b><small>'.$l["jenis"].' · '.e($l["catatan"]).'</small></div><b>'.$l["stok_sebelum"].' → '.$l["stok_sesudah"].'</b></div>';echo '</div></div>';
}
elseif($page==="expenses"){
 $rows=$conn->query("SELECT * FROM expenses WHERE user_id=$uid ORDER BY id DESC LIMIT 30");echo '<div class="twopan"><div class="panel"><h3>Tambah pengeluaran</h3><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="expense"><label>Keterangan</label><input name="keterangan" required><label>Jumlah</label><input type="number" name="jumlah" min="1" required><button class="primary full">Simpan</button></form></div><div class="panel"><h3>Riwayat</h3>';while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["keterangan"]).'</b><small>'.date("d/m/Y H:i",strtotime($r["created_at"])).'</small></div><b>'.rp($r["jumlah"]).'</b></div>';echo '</div></div>';
}
elseif($page==="reports"){
 $from=$_GET["from"]??date("Y-m-01");$to=$_GET["to"]??date("Y-m-d");$q=$conn->query("SELECT COUNT(*) n,COALESCE(SUM(total),0) t,COALESCE(SUM(diskon),0) d FROM sales WHERE user_id=$uid AND DATE(created_at) BETWEEN '".$conn->real_escape_string($from)."' AND '".$conn->real_escape_string($to)."'");$s=$q->fetch_assoc();echo '<div class="panel"><form class="filter"><div><label>Dari</label><input type="date" name="from" value="'.e($from).'"></div><div><label>Sampai</label><input type="date" name="to" value="'.e($to).'"></div><button class="primary">Tampilkan</button></form></div><div class="cards"><div><small>Transaksi</small><b>'.$s["n"].'</b></div><div><small>Omzet</small><b>'.rp($s["t"]).'</b></div><div><small>Diskon</small><b>'.rp($s["d"]).'</b></div></div><div class="panel"><a class="button" href="export.php?from='.e($from).'&to='.e($to).'">Export CSV</a></div>';
}
elseif($page==="upgrade"){
 $locked=$_GET["locked"]??"";$activated=isset($_GET["activated"]);
 if(isset($_GET['order']) && $_GET['order']==='created') echo '<div class="alert good">Pengajuan pembelian berhasil dicatat. Status: menunggu pembayaran/verifikasi.</div>';
echo '<div class="upgrade-hero"><div><span class="eyebrow">TOKOFLOW PLANS</span><h1>Pilih paket sesuai pertumbuhan bisnis.</h1><p>Fitur premium benar-benar dibatasi berdasarkan paket. Upgrade hanya membuka kemampuan yang memang termasuk paket tersebut.</p></div><div class="plan-current"><small>Paket Anda</small><strong>'.e($planInfo["name"]).'</strong><span>Rp '.number_format($planInfo["price"],0,",",".").'/30 hari</span></div></div>';
 if($locked)echo '<div class="alert bad">Fitur <b>'.e(featureLabel($locked)).'</b> membutuhkan paket lebih tinggi.</div>';if($activated)echo '<div class="alert good">Lisensi berhasil diaktifkan. Fitur paket Anda sekarang terbuka.</div>';
 echo '<div class="pricing-grid">';foreach(["basic","pro","business"] as $pc){$pi=planMeta($pc);$current=$pc===$planCode;echo '<div class="price-card '.($current?'current ':'').($pc==="pro"?'popular ':'').'">'.($pc==="pro"?'<span class="popular-badge">PALING POPULER</span>':'').'<div class="price-top"><span class="plan-name">'.e($pi["name"]).'</span><small>'.($current?'PAKET AKTIF':'').'</small></div><div class="price"><b>Rp '.number_format($pi["price"],0,",",".").'</b><span>/ 30 hari</span></div><ul>';foreach($pi["features"] as $f)echo '<li>✓ '.e(featureLabel($f)).'</li>';echo '</ul>';if($current)echo '<button class="button full" disabled>Paket Aktif</button>';else echo '<form method="post" class="purchase-form"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="purchase_premium"><input type="hidden" name="plan" value="'.$pc.'"><label>Metode pembayaran</label><select name="method"><option value="manual">Konfirmasi manual</option><option value="transfer">Transfer bank</option><option value="qris">QRIS</option></select><button class="primary full" type="submit">Beli '.$pi["name"].'</button><small class="muted">Pesanan dicatat sebagai pending. Pembayaran nyata perlu diverifikasi admin/provider.</small></form>';echo '</div>';}echo '</div>';
 echo '<div class="panel license-panel"><h3>Aktivasi lisensi</h3><p>Masukkan kode lisensi yang Anda terima setelah pembelian paket.</p><form method="post" class="license-form"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="activate_license"><input name="license" placeholder="Contoh: TOKOFLOW-PRO-XXXX" required><button class="primary">Aktifkan Paket</button></form><small>Demo pengujian: <b>TOKOFLOW-PRO-DEMO</b> atau <b>TOKOFLOW-BUSINESS-DEMO</b>.</small></div>';
}
elseif($page==="customers"){ $rows=$conn->query("SELECT * FROM customers WHERE user_id=$uid ORDER BY id DESC");echo '<div class="twopan"><div class="panel"><h3>Tambah pelanggan</h3><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="customer"><label>Nama</label><input name="nama" required><label>Telepon</label><input name="telepon"><label>Email</label><input type="email" name="email"><label>Alamat</label><textarea name="alamat"></textarea><button class="primary full">Simpan Pelanggan</button></form></div><div class="panel"><h3>CRM Pelanggan</h3>';while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["nama"]).'</b><small>'.e($r["telepon"]).' · '.e($r["email"]).'</small></div><span class="tag">'.(int)$r["points"].' poin</span></div>';echo '</div></div>';
}elseif($page==="suppliers"){ $rows=$conn->query("SELECT * FROM suppliers WHERE user_id=$uid ORDER BY id DESC");echo '<div class="twopan"><div class="panel"><h3>Tambah supplier</h3><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="supplier"><label>Nama</label><input name="nama" required><label>Telepon</label><input name="telepon"><label>Email</label><input type="email" name="email"><label>Alamat</label><textarea name="alamat"></textarea><button class="primary full">Simpan Supplier</button></form></div><div class="panel"><h3>Daftar supplier</h3>';while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["nama"]).'</b><small>'.e($r["telepon"]).' · '.e($r["email"]).'</small></div></div>';echo '</div></div>';
}elseif($page==="purchases"){ $prods=$conn->query("SELECT id,nama,harga_beli,stok FROM products WHERE user_id=$uid AND aktif=1 ORDER BY nama");$sup=$conn->query("SELECT id,nama FROM suppliers WHERE user_id=$uid ORDER BY nama");echo '<div class="panel"><h3>Pembelian & Restock</h3><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="purchase"><label>Supplier</label><select name="supplier_id"><option value="">Tanpa supplier</option>';while($r=$sup->fetch_assoc())echo '<option value="'.$r["id"].'">'.e($r["nama"]).'</option>';echo '</select><label>Produk</label><select name="product_id">';while($r=$prods->fetch_assoc())echo '<option value="'.$r["id"].'">'.e($r["nama"]).' · stok '.$r["stok"].'</option>';echo '</select><div class="twocol"><div><label>Jumlah</label><input type="number" name="qty" min="1" required></div><div><label>Harga beli/unit</label><input type="number" name="harga" min="0" required></div></div><button class="primary full">Catat Pembelian & Tambah Stok</button></form></div>'; $rows=$conn->query("SELECT p.invoice,p.total,p.created_at,s.nama supplier FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id WHERE p.user_id=$uid ORDER BY p.id DESC LIMIT 30");echo '<div class="panel"><h3>Riwayat pembelian</h3>';while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["invoice"]).'</b><small>'.e($r["supplier"]??"Tanpa supplier").' · '.date("d/m/Y H:i",strtotime($r["created_at"])).'</small></div><b>'.rp($r["total"]).'</b></div>';echo '</div>';
}elseif($page==="owner_panel"){ requireOwner(); require "owner_panel.php";
}elseif($page==="staff"){ requireFeature("staff"); requireOwner(); $rows=$conn->query("SELECT id,nama,email,role,created_at FROM users WHERE owner_id=$uid AND role IN ('kasir','staff') ORDER BY id DESC");echo '<div class="twopan"><div class="panel"><h3>Tambah kasir</h3><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="staff"><label>Nama</label><input name="nama" required><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" minlength="8" required><button class="primary full">Buat Akun Kasir</button></form></div><div class="panel"><h3>Tim toko</h3>';while($r=$rows->fetch_assoc())echo '<div class="row"><div><b>'.e($r["nama"]).'</b><small>'.e($r["email"]).'</small></div><span class="tag">'.e($r["role"]).'</span></div>';echo '</div></div>';
}elseif($page==="advanced_reports"||$page==="analytics"){ $from=$_GET["from"]??date("Y-m-01");$to=$_GET["to"]??date("Y-m-d");$fromE=$conn->real_escape_string($from);$toE=$conn->real_escape_string($to);$top=$conn->query("SELECT si.nama_produk,SUM(si.qty) qty,SUM(si.subtotal) omzet,SUM(si.qty*(si.harga-p.harga_beli)) laba FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id WHERE s.user_id=$uid AND DATE(s.created_at) BETWEEN '$fromE' AND '$toE' GROUP BY si.product_id,si.nama_produk ORDER BY laba DESC LIMIT 10");$tot=$conn->query("SELECT COALESCE(SUM(si.qty*(si.harga-p.harga_beli)),0) laba,COALESCE(SUM(si.subtotal),0) omzet FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id WHERE s.user_id=$uid AND DATE(s.created_at) BETWEEN '$fromE' AND '$toE'")->fetch_assoc();$best=$conn->query("SELECT si.nama_produk,SUM(si.qty) qty FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.user_id=$uid AND DATE(s.created_at) BETWEEN '$fromE' AND '$toE' GROUP BY si.product_id,si.nama_produk ORDER BY qty DESC LIMIT 5");echo '<div class="panel"><form class="inlineform"><input type="hidden" name="p" value="advanced_reports"><label>Dari</label><input type="date" name="from" value="'.e($from).'" required><label>Sampai</label><input type="date" name="to" value="'.e($to).'" required><button class="primary">Terapkan Filter</button></form></div><div class="cards"><div><small>Total omzet</small><b>'.rp($tot["omzet"]).'</b></div><div><small>Laba kotor</small><b>'.rp($tot["laba"]).'</b></div><div><small>Margin</small><b>'.((float)$tot["omzet"]>0?number_format(((float)$tot["laba"]/(float)$tot["omzet"])*100,1):"0.0").'%</b></div></div><div class="panel"><h3>Produk paling laku</h3>';while($r=$best->fetch_assoc())echo '<div class="row"><b>'.e($r["nama_produk"]).'</b><span>'.$r["qty"].' unit</span></div>';echo '</div><div class="panel"><h3>Produk berdasarkan laba</h3>';while($r=$top->fetch_assoc())echo '<div class="row"><div><b>'.e($r["nama_produk"]).'</b><small>'.$r["qty"].' unit · omzet '.rp($r["omzet"]).'</small></div><b>'.rp($r["laba"]).'</b></div>';echo '</div>';
}elseif($page==="backup"){requireFeature("backup");echo '<div class="panel narrow"><h3>Backup Database PRO</h3><p>Unduh salinan SQL data toko Anda untuk keamanan dan migrasi.</p><a class="primary button" href="backup.php?download=1">↥ Download Backup SQL</a></div>';
}
elseif(in_array($page,["promos","returns","opname","receivables","shifts"],true)){renderPremiumPage($page);}elseif($page==="change_password"){
 echo '<div class="panel narrow password-card"><div class="auth-icon">🔐</div><h2>Ganti Password</h2><p class="muted">Untuk keamanan, buat password pribadi Anda sendiri sebelum melanjutkan.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"/><input type="hidden" name="act" value="change_password"/><label>Password saat ini</label><input type="password" name="current_password" autocomplete="current-password" required autofocus/><label>Password baru</label><input type="password" name="new_password" autocomplete="new-password" minlength="8" placeholder="Minimal 8 karakter" required/><label>Konfirmasi password baru</label><input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required/><small class="muted">Gunakan minimal 8 karakter dan jangan gunakan password yang mudah ditebak.</small><button class="primary full">Simpan Password Baru</button></form></div>';
}
elseif($page==="settings"){
 $r=$conn->query("SELECT * FROM settings WHERE user_id=$uid")->fetch_assoc()?:[];if(isset($_GET["password_changed"]))echo '<div class="alert good">Password berhasil diganti. Gunakan password baru saat login berikutnya.</div>';echo '<div class="panel password-card"><h3>🔐 Keamanan Akun</h3><p class="muted">Ganti password secara berkala dan jangan bagikan password kepada orang lain.</p><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="change_password"><label>Password saat ini</label><input type="password" name="current_password" autocomplete="current-password" required><label>Password baru</label><input type="password" name="new_password" autocomplete="new-password" minlength="8" placeholder="Minimal 8 karakter" required><label>Konfirmasi password baru</label><input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required><button class="primary">Ganti Password</button></form></div><div class="pro-banner"><div><span class="pro-badge">PRO</span><b>Personalisasi TokoFlow</b><small>Pilih warna aksen dan mode tampilan.</small></div><span class="pro-spark">✦</span></div><div class="panel narrow"><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="settings"><label>Nama usaha</label><input name="shop" value="'.e($r["nama_usaha"]??"Toko Saya").'"><label>Alamat</label><textarea name="addr">'.e($r["alamat"]??"").'</textarea><label>Telepon</label><input name="tel" value="'.e($r["telepon"]??"").'"><label>Footer struk</label><input name="foot" value="'.e($r["footer"]??"Terima kasih sudah berbelanja.").'"><label>Jenis toko</label><select name="jenis_toko"><option value="Toko" ' . (($r["jenis_toko"]??"Toko")==="Toko"?"selected":"") . ' >Toko Umum</option><option value="Minimarket" ' . (($r["jenis_toko"]??"Toko")==="Minimarket"?"selected":"") . ' >Minimarket</option><option value="Warung" ' . (($r["jenis_toko"]??"Toko")==="Warung"?"selected":"") . ' >Warung</option><option value="Cafe" ' . (($r["jenis_toko"]??"Toko")==="Cafe"?"selected":"") . ' >Cafe / Kedai</option></select><div class="theme-section"><h3>Tampilan PRO</h3><label>Mode</label><div class="segmented"><label><input type="radio" name="mode" value="light" '.(($r["mode"]??"light")==="light"?'checked':'').'> <span>☀️ Terang</span></label><label><input type="radio" name="mode" value="dark" '.(($r["mode"]??"light")==="dark"?'checked':'').'> <span>🌙 Gelap</span></label></div><label>Warna aksen</label><div class="swatches"><label class="swatch sw-indigo"><input type="radio" name="theme" value="indigo" '.(($r["theme"]??"indigo")==="indigo"?'checked':'').'><i></i><b>Indigo</b></label><label class="swatch sw-blue"><input type="radio" name="theme" value="blue" '.(($r["theme"]??"indigo")==="blue"?'checked':'').'><i></i><b>Biru</b></label><label class="swatch sw-emerald"><input type="radio" name="theme" value="emerald" '.(($r["theme"]??"indigo")==="emerald"?'checked':'').'><i></i><b>Hijau</b></label><label class="swatch sw-orange"><input type="radio" name="theme" value="orange" '.(($r["theme"]??"indigo")==="orange"?'checked':'').'><i></i><b>Oranye</b></label><label class="swatch sw-rose"><input type="radio" name="theme" value="rose" '.(($r["theme"]??"indigo")==="rose"?'checked':'').'><i></i><b>Rose</b></label><label class="swatch sw-violet"><input type="radio" name="theme" value="violet" '.(($r["theme"]??"indigo")==="violet"?'checked':'').'><i></i><b>Violet</b></label></div></div><div class="theme-actions"><button type="button" class="button" onclick="resetThemePreview()">Reset</button><button class="primary" type="submit">Simpan Tampilan</button></div><div class="save-hint">Perubahan warna dan mode langsung terlihat di layar. Klik <b>Simpan Tampilan</b> agar tersimpan untuk login berikutnya.</div></form></div>';
}
elseif($page==="pos"){
 $ps=$conn->query("SELECT id,kode,nama,harga_jual,stok,satuan FROM products WHERE user_id=$uid AND aktif=1 AND stok>0 ORDER BY nama");$arr=[];while($p=$ps->fetch_assoc())$arr[]=$p;
 echo '<div class="pos"><section class="panel pos-products-panel"><div class="pos-panel-head"><div><span class="section-kicker">POINT OF SALE</span><h3>Produk untuk transaksi</h3><small>Pilih produk atau scan barcode untuk menambah ke keranjang.</small></div><span class="pos-shortcut">F2 · Fokus pencarian</span></div><div class="pos-tools"><div class="pos-input"><span>⌕</span><input id="possearch" placeholder="Cari nama atau kode produk..." aria-label="Cari produk"></div><div class="pos-input"><span>▦</span><input id="barcode" placeholder="Scan / ketik barcode lalu Enter" autocomplete="off" aria-label="Barcode"></div></div><div class="productgrid" id="grid">';
 foreach($arr as $p)echo '<button type="button" class="prod" data-q="'.e(strtolower($p["nama"]." ".$p["kode"])).'" data-code="'.e($p["kode"]).'" onclick=\'add('.json_encode($p).')\'><b>'.e($p["nama"]).'</b><small>'.$p["stok"].' '.$p["satuan"].' · '.e($p["kode"]).'</small><strong>'.rp($p["harga_jual"]).'</strong></button>';
 echo '</div></section><section class="panel pos-cart-panel"><div class="pos-panel-head"><div><span class="section-kicker">CHECKOUT</span><h3>Keranjang <span id="cartcount" class="cartcount">0</span></h3><small>Periksa pelanggan, diskon, dan metode pembayaran sebelum menyimpan.</small></div><span class="checkout-live"><i></i> Siap transaksi</span></div><div id="cart"></div><div id="empty" class="empty">Belum ada barang.</div><form method="post" id="saleform"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="sale"><input type="hidden" name="cart" id="cartdata"><label>Pelanggan</label><input name="customer" value="Umum"><label>No. WhatsApp pelanggan <small>(opsional)</small></label><input name="customer_phone" data-customer-phone placeholder="08xxxxxxxxxx"><label>Diskon</label><input type="number" name="discount" id="disc" value="0" min="0" oninput="render()"><label>Kode promo</label><input name="promo_code" id="promo_code" placeholder="Contoh: HEMAT10"><details class="pos-accordion"><summary><span>Piutang & jatuh tempo</span><small>Opsional · buka jika pelanggan berutang</small></summary><div class="accordion-body"><label>Jatuh tempo piutang</label><input type="date" name="due_date" value="'.date("Y-m-d",strtotime("+4 days")).'"><p class="muted">Aktifkan metode <b>Piutang</b> jika pembayaran akan dicatat sebagai tagihan pelanggan.</p></div></details><label>Metode pembayaran</label><select name="method" id="method" onchange="render()"><option>Tunai</option><option>QRIS</option><option>Transfer</option><option>Debit</option><option>Piutang</option></select><div id="cashbox"><label>Uang diterima</label><input type="number" name="pay" id="pay" value="0" min="0" oninput="render()"></div><div id="noncashbox" class="payment-note">Untuk QRIS, Transfer, atau Debit, kasir mencatat pembayaran setelah transaksi dinyatakan berhasil di perangkat/payment gateway.</div><input type="hidden" name="payment_ref" id="payment_ref"><div class="total"><small>Total</small><b id="total">Rp 0</b><span id="change">Kembalian Rp 0</span></div><button type="submit" class="primary full" onclick="return validateSale()">Simpan & Cetak Struk</button></form></div></div>';
}
elseif($page==="receipt"){
 $id=(int)$_GET["id"];$q=$conn->prepare("SELECT * FROM sales WHERE id=? AND user_id=?");$q->bind_param("ii",$id,$uid);$q->execute();$s=$q->get_result()->fetch_assoc();$items=$conn->query("SELECT * FROM sale_items WHERE sale_id=$id");$set=$conn->query("SELECT * FROM settings WHERE user_id=$uid")->fetch_assoc();echo '<div class="receipt" data-receipt><div class="receipt-heading center"><span class="receipt-kicker">STRUK PEMBAYARAN</span><h2>'.e($set["nama_usaha"]??$shop).'</h2><small>'.nl2br(e($set["alamat"]??"")).'</small><hr><b>'.e($s["invoice"]).'</b><small>'.date("d/m/Y H:i",strtotime($s["created_at"])).'</small></div>';while($i=$items->fetch_assoc())echo '<div class="rrow"><span>'.e($i["nama_produk"]).' × '.$i["qty"].'</span><b>'.rp($i["subtotal"]).'</b></div>';echo '<hr><div class="rrow"><span>Subtotal</span><b>'.rp($s["subtotal"]).'</b></div><div class="rrow"><span>Diskon</span><b>-'.rp($s["diskon"]).'</b></div><div class="rrow"><strong>Total</strong><strong>'.rp($s["total"]).'</strong></div><div class="rrow"><span>'.e($s["metode"]).'</span><span>Bayar '.rp($s["bayar"]).'</span></div><div class="rrow change-row"><span>KEMBALIAN</span><b>'.rp($s["kembalian"]).'</b></div><div class="rrow"><span>Referensi</span><span>'.e($s["payment_ref"]??"-").'</span></div><p class="center">'.e($set["footer"]??"Terima kasih.").'</p><div class="twocol"><button class="primary full" data-print-receipt type="button">🖨 Cetak Struk</button><button class="button full" data-download-receipt type="button">↓ Simpan TXT</button></div><div class="twocol"><button class="button full" data-whatsapp-receipt type="button">↗ WhatsApp</button><a class="button full center" href="?p=pos">Transaksi Baru</a></div></div>';
}
else {echo '<div class="panel"><h3>Halaman belum tersedia</h3></div>';}
footerUI();$chartRows=[];$chartMax=1;$chartQ=$conn->query("SELECT DATE(created_at) d,COALESCE(SUM(total),0) total FROM sales WHERE user_id=$uid AND DATE(created_at)>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY d");while($cr=$chartQ->fetch_assoc()){$chartRows[$cr['d']]=(float)$cr['total'];$chartMax=max($chartMax,(float)$cr['total']);}$chartHtml='<div class="panel sales-chart"><div class="panelhead"><div><h3>Penjualan 7 Hari</h3><small class="muted">Omzet berdasarkan transaksi tersimpan</small></div><a href="?p=reports">Lihat laporan</a></div><div class="bars">';for($i=6;$i>=0;$i--){$day=date('Y-m-d',strtotime('-'.$i.' day'));$val=$chartRows[$day]??0;$height=max(4,round(($val/$chartMax)*100));$label=date('D',strtotime($day));$chartHtml.='<div class="bar-col"><span>'.rp($val).'</span><i style="height:'.$height.'%"></i><small>'.$label.'</small></div>';}$chartHtml.='</div></div>';

