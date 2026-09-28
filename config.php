<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE) session_start();
$host="127.0.0.1"; $user="root"; $pass=""; $db="tokoflow";
$conn=new mysqli($host,$user,$pass);
if($conn->connect_error) die("MySQL gagal: ".$conn->connect_error);
$conn->set_charset("utf8mb4");
$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db);
$sqls=[
"CREATE TABLE IF NOT EXISTS users(id INT AUTO_INCREMENT PRIMARY KEY,nama VARCHAR(100) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,role VARCHAR(20) DEFAULT 'owner',owner_id INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS categories(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,nama VARCHAR(100) NOT NULL,UNIQUE KEY uq_cat(user_id,nama),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS products(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,category_id INT NULL,kode VARCHAR(60) NOT NULL,nama VARCHAR(150) NOT NULL,harga_beli DECIMAL(15,2) DEFAULT 0,harga_jual DECIMAL(15,2) DEFAULT 0,stok INT DEFAULT 0,stok_min INT DEFAULT 5,satuan VARCHAR(30) DEFAULT 'pcs',aktif TINYINT DEFAULT 1,UNIQUE KEY uq_code(user_id,kode),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL)",
"CREATE TABLE IF NOT EXISTS sales(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,invoice VARCHAR(60) UNIQUE NOT NULL,pelanggan VARCHAR(100) DEFAULT 'Umum',subtotal DECIMAL(15,2) DEFAULT 0,diskon DECIMAL(15,2) DEFAULT 0,total DECIMAL(15,2) DEFAULT 0,bayar DECIMAL(15,2) DEFAULT 0,kembalian DECIMAL(15,2) DEFAULT 0,metode VARCHAR(30) DEFAULT 'Tunai',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS sale_items(id INT AUTO_INCREMENT PRIMARY KEY,sale_id INT NOT NULL,product_id INT NOT NULL,nama_produk VARCHAR(150),qty INT,harga DECIMAL(15,2),subtotal DECIMAL(15,2),FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT)",
"CREATE TABLE IF NOT EXISTS stock_logs(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,product_id INT NOT NULL,jenis VARCHAR(20),qty INT,stok_sebelum INT,stok_sesudah INT,catatan VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS expenses(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,keterangan VARCHAR(200),jumlah DECIMAL(15,2),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS settings(user_id INT PRIMARY KEY,nama_usaha VARCHAR(150) DEFAULT 'Toko Saya',alamat TEXT,telepon VARCHAR(50),footer VARCHAR(200),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS customers(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,nama VARCHAR(150) NOT NULL,telepon VARCHAR(50) DEFAULT '',email VARCHAR(150) DEFAULT '',alamat TEXT,points INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_customer_email(user_id,email),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS suppliers(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,nama VARCHAR(150) NOT NULL,telepon VARCHAR(50) DEFAULT '',email VARCHAR(150) DEFAULT '',alamat TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS purchases(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,supplier_id INT NULL,invoice VARCHAR(60) NOT NULL,total DECIMAL(15,2) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL)",
"CREATE TABLE IF NOT EXISTS purchase_items(id INT AUTO_INCREMENT PRIMARY KEY,purchase_id INT NOT NULL,product_id INT NOT NULL,qty INT NOT NULL,harga DECIMAL(15,2) DEFAULT 0,subtotal DECIMAL(15,2) DEFAULT 0,FOREIGN KEY(purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT)",
"CREATE TABLE IF NOT EXISTS audit_logs(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,aksi VARCHAR(100),detail VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS subscription_plans(code VARCHAR(30) PRIMARY KEY,nama VARCHAR(50),harga DECIMAL(15,2),days INT DEFAULT 30)",
"CREATE TABLE IF NOT EXISTS premium_orders(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,plan_code VARCHAR(30) NOT NULL,amount DECIMAL(15,2) NOT NULL,method VARCHAR(30) DEFAULT 'manual',status VARCHAR(30) DEFAULT 'pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS licenses(id INT AUTO_INCREMENT PRIMARY KEY,code VARCHAR(100) UNIQUE NOT NULL,plan_code VARCHAR(30) NOT NULL,days INT DEFAULT 30,used_by INT NULL,used_at DATETIME NULL,FOREIGN KEY(used_by) REFERENCES users(id) ON DELETE SET NULL)",
"CREATE TABLE IF NOT EXISTS promos(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,kode VARCHAR(50) NOT NULL,jenis VARCHAR(20) NOT NULL DEFAULT 'nominal',nilai DECIMAL(15,2) NOT NULL DEFAULT 0,min_total DECIMAL(15,2) NOT NULL DEFAULT 0,mulai DATE NOT NULL,berakhir DATE NOT NULL,aktif TINYINT DEFAULT 1,UNIQUE KEY uq_promo(user_id,kode),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS shifts(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,kasir VARCHAR(100),mulai TIMESTAMP DEFAULT CURRENT_TIMESTAMP,selesai DATETIME NULL,modal_awal DECIMAL(15,2) DEFAULT 0,kas_akhir DECIMAL(15,2) DEFAULT 0,kas_diharapkan DECIMAL(15,2) DEFAULT 0,selisih DECIMAL(15,2) DEFAULT 0,status VARCHAR(20) DEFAULT 'open',FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS stock_opnames(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,status VARCHAR(20) DEFAULT 'selesai',catatan VARCHAR(255) DEFAULT '',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS stock_opname_items(id INT AUTO_INCREMENT PRIMARY KEY,opname_id INT NOT NULL,product_id INT NOT NULL,stok_sistem INT NOT NULL,stok_fisik INT NOT NULL,selisih INT NOT NULL,FOREIGN KEY(opname_id) REFERENCES stock_opnames(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT)",
"CREATE TABLE IF NOT EXISTS sales_returns(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,sale_id INT NOT NULL,invoice VARCHAR(60),alasan VARCHAR(255) DEFAULT '',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS sales_return_items(id INT AUTO_INCREMENT PRIMARY KEY,return_id INT NOT NULL,sale_item_id INT NOT NULL,product_id INT NOT NULL,qty INT NOT NULL,refund DECIMAL(15,2) DEFAULT 0,FOREIGN KEY(return_id) REFERENCES sales_returns(id) ON DELETE CASCADE,FOREIGN KEY(sale_item_id) REFERENCES sale_items(id) ON DELETE RESTRICT,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT)",
"CREATE TABLE IF NOT EXISTS receivables(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,sale_id INT NULL,invoice VARCHAR(60),customer_name VARCHAR(150),total DECIMAL(15,2) DEFAULT 0,paid DECIMAL(15,2) DEFAULT 0,due_date DATE,status VARCHAR(20) DEFAULT 'belum_lunas',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE SET NULL)",
"CREATE TABLE IF NOT EXISTS receivable_payments(id INT AUTO_INCREMENT PRIMARY KEY,receivable_id INT NOT NULL,jumlah DECIMAL(15,2) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(receivable_id) REFERENCES receivables(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS gateway_settings(user_id INT PRIMARY KEY,provider VARCHAR(30) DEFAULT 'manual',public_key VARCHAR(255) DEFAULT '',secret_key VARCHAR(255) DEFAULT '',webhook_token VARCHAR(255) DEFAULT '',updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS test_runs(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,run_code VARCHAR(80) UNIQUE NOT NULL,status VARCHAR(20) DEFAULT 'passed',steps_json LONGTEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)",
"CREATE TABLE IF NOT EXISTS license_events(id INT AUTO_INCREMENT PRIMARY KEY,license_id INT NULL,admin_id INT NOT NULL,action VARCHAR(40) NOT NULL,detail VARCHAR(255) DEFAULT '',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(license_id) REFERENCES licenses(id) ON DELETE SET NULL,FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE CASCADE)"

];
foreach($sqls as $s)$conn->query($s);
// V7.1 account security: force developer-created credentials to be changed on first login.
@$conn->query("ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0");
// Upgrade existing installations without destroying data.
if(!$conn->query("SHOW COLUMNS FROM sales LIKE 'payment_ref'")->num_rows)$conn->query("ALTER TABLE sales ADD COLUMN payment_ref VARCHAR(150) DEFAULT ''");
if(!$conn->query("SHOW COLUMNS FROM settings LIKE 'theme'")->num_rows)$conn->query("ALTER TABLE settings ADD COLUMN theme VARCHAR(30) DEFAULT 'indigo'");
if(!$conn->query("SHOW COLUMNS FROM settings LIKE 'mode'")->num_rows)$conn->query("ALTER TABLE settings ADD COLUMN mode VARCHAR(10) DEFAULT 'light'");
if(!$conn->query("SHOW COLUMNS FROM settings LIKE 'jenis_toko'")->num_rows)$conn->query("ALTER TABLE settings ADD COLUMN jenis_toko VARCHAR(30) DEFAULT 'Toko'");
if(!$conn->query("SHOW COLUMNS FROM users LIKE 'owner_id'")->num_rows)$conn->query("ALTER TABLE users ADD COLUMN owner_id INT NULL AFTER role");
if(!$conn->query("SHOW INDEX FROM users WHERE Key_name='idx_users_owner'")->num_rows)$conn->query("ALTER TABLE users ADD INDEX idx_users_owner(owner_id)");
if(!$conn->query("SHOW COLUMNS FROM users LIKE 'plan_code'")->num_rows)$conn->query("ALTER TABLE users ADD COLUMN plan_code VARCHAR(30) DEFAULT 'basic'");
if(!$conn->query("SHOW COLUMNS FROM users LIKE 'plan_status'")->num_rows)$conn->query("ALTER TABLE users ADD COLUMN plan_status VARCHAR(20) DEFAULT 'active'");
if(!$conn->query("SHOW COLUMNS FROM users LIKE 'plan_expires_at'")->num_rows)$conn->query("ALTER TABLE users ADD COLUMN plan_expires_at DATETIME NULL");
// TokoFlow V6: nonaktifkan seluruh masa kedaluwarsa. Semua akun menggunakan lisensi unlimited.
$conn->query("UPDATE users SET plan_expires_at=NULL WHERE plan_expires_at IS NOT NULL");
$conn->query("UPDATE users SET owner_id=id WHERE owner_id IS NULL AND role='owner'");
$conn->query("UPDATE subscription_plans SET days=0");
$conn->query("UPDATE licenses SET days=0");
// Upgrade role column so the local developer account can use a dedicated role.
$roleCol=$conn->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch_assoc();
if($roleCol && stripos((string)$roleCol['Type'],'enum')===0){$conn->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'owner'");}



$planSeed=[
 ['basic','Basic',49000,0],['pro','Pro',149000,0],['business','Business',299000,0]
];
foreach($planSeed as $pl){$st=$conn->prepare("INSERT IGNORE INTO subscription_plans(code,nama,harga,days) VALUES(?,?,?,?)");$st->bind_param("ssdi",$pl[0],$pl[1],$pl[2],$pl[3]);$st->execute();$st->close();}
$conn->query("UPDATE subscription_plans SET harga=0 WHERE code='basic'");
$licenseSeed=[['TOKOFLOW-PRO-DEMO','pro',0],['TOKOFLOW-BUSINESS-DEMO','business',0]];
foreach($licenseSeed as $lc){$st=$conn->prepare("INSERT IGNORE INTO licenses(code,plan_code,days) VALUES(?,?,?)");$st->bind_param("ssi",$lc[0],$lc[1],$lc[2]);$st->execute();$st->close();}

if((int)$conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()["c"]===0){
 $n="Pemilik Usaha";$e="admin@tokoflow.local";$p=password_hash("admin123",PASSWORD_DEFAULT);
 $st=$conn->prepare("INSERT INTO users(nama,email,password,plan_code,plan_status,plan_expires_at) VALUES(?,?,?,'business','active',NULL)");$st->bind_param("sss",$n,$e,$p);$st->execute();$uid=$st->insert_id;$st->close();
 $st=$conn->prepare("INSERT INTO settings(user_id,nama_usaha,footer) VALUES(?,?,?)");$shop="TokoFlow Demo";$f="Terima kasih sudah berbelanja."; $st->bind_param("iss",$uid,$shop,$f);$st->execute();$st->close();
 $cat="Umum";$st=$conn->prepare("INSERT INTO categories(user_id,nama) VALUES(?,?)");$st->bind_param("is",$uid,$cat);$st->execute();$cid=$st->insert_id;$st->close();
 $arr=[["P001","Air Mineral",2500,4000,30],["P002","Kopi Sachet",1500,3000,25],["P003","Roti Cokelat",3500,6000,20]];
 $st=$conn->prepare("INSERT INTO products(user_id,category_id,kode,nama,harga_beli,harga_jual,stok) VALUES(?,?,?,?,?,?,?)");
 foreach($arr as $x){$st->bind_param("iissddi",$uid,$cid,$x[0],$x[1],$x[2],$x[3],$x[4]);$st->execute();}$st->close();
}
// Local developer account: full feature access for the application owner/developer.
$devEmail='developer@tokoflow.local';
$devPass='dev123456';
$devCheck=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$devCheck->bind_param("s",$devEmail);$devCheck->execute();$devExists=$devCheck->get_result()->fetch_assoc();$devCheck->close();
if(!$devExists){
  $devName='TokoFlow Developer';$devHash=password_hash($devPass,PASSWORD_DEFAULT);
  $st=$conn->prepare("INSERT INTO users(nama,email,password,role,plan_code,plan_status,plan_expires_at) VALUES(?,?,?,'developer','business','active',NULL)");
  $st->bind_param("sss",$devName,$devEmail,$devHash);$st->execute();$devUid=$st->insert_id;$st->close();
  $st=$conn->prepare("INSERT IGNORE INTO settings(user_id,nama_usaha,footer) VALUES(?,?,?)");$devShop='TokoFlow Developer';$devFooter='Developer Mode · TokoFlow';$st->bind_param("iss",$devUid,$devShop,$devFooter);$st->execute();$st->close();
}
// Seed katalog demo untuk akun developer agar semua modul mudah diuji.
$devRow=$conn->query("SELECT id FROM users WHERE email='developer@tokoflow.local' LIMIT 1")->fetch_assoc();
if($devRow){
  $devUid=(int)$devRow['id'];
  $catStmt=$conn->prepare("SELECT id FROM categories WHERE user_id=? AND nama='Minuman' LIMIT 1");$catStmt->bind_param("i",$devUid);$catStmt->execute();$catRow=$catStmt->get_result()->fetch_assoc();$catStmt->close();
  if(!$catRow){$catName='Minuman';$catStmt=$conn->prepare("INSERT INTO categories(user_id,nama) VALUES(?,?)");$catStmt->bind_param("is",$devUid,$catName);$catStmt->execute();$devCat=$catStmt->insert_id;$catStmt->close();}else{$devCat=(int)$catRow['id'];}
  $seedProducts=[['D001','Air Mineral 600ml',2500,4000,45,5],['D002','Kopi Susu Botol',7000,12000,28,5],['D003','Teh Botol',3500,6000,31,5],['D004','Roti Cokelat',4000,7500,22,5],['D005','Mie Instan',2500,4000,40,8],['D006','Gula 1kg',13000,16500,3,5],['D007','Minyak 1L',15000,19000,1,5],['D008','Biskuit',6000,9500,18,5]];
  $checkProd=$conn->prepare("SELECT id FROM products WHERE user_id=? AND kode=? LIMIT 1");
  $insProd=$conn->prepare("INSERT INTO products(user_id,category_id,kode,nama,harga_beli,harga_jual,stok,stok_min) VALUES(?,?,?,?,?,?,?,?)");
  foreach($seedProducts as $sp){$checkProd->bind_param("is",$devUid,$sp[0]);$checkProd->execute();if(!$checkProd->get_result()->fetch_assoc()){$insProd->bind_param("iissddii",$devUid,$devCat,$sp[0],$sp[1],$sp[2],$sp[3],$sp[4],$sp[5]);$insProd->execute();}}
  $checkProd->close();$insProd->close();
  // Contoh transaksi agar Dashboard, Riwayat, dan Laporan langsung berisi data uji.
  $saleCount=(int)$conn->query("SELECT COUNT(*) c FROM sales WHERE user_id=$devUid")->fetch_assoc()['c'];
  if($saleCount===0){
    $demoSales=[['DEMO-001','Pelanggan Demo',18000,0,18000,20000,2000,[['D002',1,12000],['D003',1,6000]]],['DEMO-002','Pelanggan Umum',15500,500,15000,15000,0,[['D004',1,7500],['D005',1,4000],['D008',1,4000]]]];
    foreach($demoSales as $ds){$st=$conn->prepare("INSERT INTO sales(user_id,invoice,pelanggan,subtotal,diskon,total,bayar,kembalian,metode) VALUES(?,?,?,?,?,?,?,?, 'Tunai')");$st->bind_param('issddddd', $devUid,$ds[0],$ds[1],$ds[2],$ds[3],$ds[4],$ds[5],$ds[6]);$st->execute();$saleId=$st->insert_id;$st->close();foreach($ds[7] as $line){$code=$line[0];$qty=$line[1];$price=$line[2];$ps=$conn->prepare("SELECT id,nama FROM products WHERE user_id=? AND kode=? LIMIT 1");$ps->bind_param('is',$devUid,$code);$ps->execute();$pr=$ps->get_result()->fetch_assoc();$ps->close();if($pr){$sub=$qty*$price;$it=$conn->prepare("INSERT INTO sale_items(sale_id,product_id,nama_produk,qty,harga,subtotal) VALUES(?,?,?,?,?,?)");$it->bind_param('iisidd',$saleId,$pr['id'],$pr['nama'],$qty,$price,$sub);$it->execute();$it->close();}}}
  }
  $demoCustomer='Pelanggan Demo';$demoPhone='081234567890';$demoEmail='pelanggan.demo@tokoflow.local';$st=$conn->prepare("INSERT IGNORE INTO customers(user_id,nama,telepon,email) VALUES(?,?,?,?)");$st->bind_param("isss",$devUid,$demoCustomer,$demoPhone,$demoEmail);$st->execute();$st->close();
  $demoSupplier='Supplier Demo';$st=$conn->prepare("INSERT IGNORE INTO suppliers(user_id,nama,telepon,email) VALUES(?,?,?,?)");$st->bind_param("isss",$devUid,$demoSupplier,$demoPhone,$demoEmail);$st->execute();$st->close();
  // Developer test fixtures for the Real Store Test Center.
  $openShift=(int)$conn->query("SELECT COUNT(*) c FROM shifts WHERE user_id=$devUid AND status='open'")->fetch_assoc()['c'];
  if($openShift===0){$kas='Developer Cashier';$modal=500000;$st=$conn->prepare("INSERT INTO shifts(user_id,kasir,modal_awal,status) VALUES(?,?,?,'open')");$st->bind_param('isd',$devUid,$kas,$modal);$st->execute();$st->close();}
  $promoCount=(int)$conn->query("SELECT COUNT(*) c FROM promos WHERE user_id=$devUid AND kode='HEMAT10'")->fetch_assoc()['c'];
  if($promoCount===0){$kode='HEMAT10';$jenis='persen';$nilai=10.0;$min=20000.0;$mulai=date('Y-m-d');$akhir=date('Y-m-d',strtotime('+30 days'));$st=$conn->prepare("INSERT INTO promos(user_id,kode,jenis,nilai,min_total,mulai,berakhir,aktif) VALUES(?,?,?,?,?,?,?,1)");$st->bind_param('issddss',$devUid,$kode,$jenis,$nilai,$min,$mulai,$akhir);$st->execute();$st->close();}
  if(!$conn->query("SELECT id FROM test_runs WHERE user_id=$devUid LIMIT 1")->fetch_assoc()){$steps=json_encode(['09:00 Buka toko','09:05 Buka shift','09:10 Barang datang','09:15 Input pembelian','09:20 Barang masuk stok','09:30 Pelanggan membeli','09:35 Pembayaran QRIS','09:40 Cetak struk','10:00 Retur barang','12:00 Cek stok','15:00 Pengeluaran','20:00 Tutup shift','20:05 Lihat laporan','20:10 Backup database']);$code='READY-'.date('Ymd');$status='ready';$st=$conn->prepare("INSERT INTO test_runs(user_id,run_code,status,steps_json) VALUES(?,?,?,?)");$st->bind_param('isss',$devUid,$code,$status,$steps);$st->execute();$st->close();}
}

function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}
function rp($v){return "Rp ".number_format((float)$v,0,",",".");}
function go($u){header("Location: $u");exit;}
function csrf(){if(empty($_SESSION["csrf"]))$_SESSION["csrf"]=bin2hex(random_bytes(20));return $_SESSION["csrf"];}
function check_csrf(){if(!hash_equals($_SESSION["csrf"]??"",$_POST["csrf"]??""))die("Token keamanan tidak valid.");}

function planMeta($code){$m=[
 'basic'=>['name'=>'Basic','price'=>0,'features'=>['pos','products','stock','categories','expenses','reports','receipt','customers','suppliers','purchases']],
 'pro'=>['name'=>'Pro','price'=>149000,'features'=>['pos','products','stock','categories','expenses','reports','receipt','customers','suppliers','purchases','staff','advanced_reports','backup','custom_theme','promos','returns','stock_opname','receivables','shifts','audit','analytics']],
 'business'=>['name'=>'Business','price'=>299000,'features'=>['pos','products','stock','categories','expenses','reports','receipt','customers','suppliers','purchases','staff','advanced_reports','backup','custom_theme','promos','returns','stock_opname','receivables','shifts','audit','analytics','payment_gateway','priority']]
];return $m[$code]??$m['basic'];}
function featureLabel($f){$m=['customers'=>'Pelanggan & CRM','suppliers'=>'Supplier','purchases'=>'Pembelian & Restock','staff'=>'Multi Kasir & Hak Akses','advanced_reports'=>'Laporan Laba & Produk','backup'=>'Backup Database','custom_theme'=>'Tema & Branding PRO','audit'=>'Audit Log','loyalty'=>'Program Loyalti','multi_outlet'=>'Multi Outlet','analytics'=>'Analitik Bisnis','priority'=>'Prioritas Dukungan','promos'=>'Promo & Diskon Otomatis','returns'=>'Retur Penjualan & Pembelian','stock_opname'=>'Stock Opname & Koreksi','receivables'=>'Piutang Pelanggan','shifts'=>'Shift Kasir & Tutup Kas','payment_gateway'=>'Payment Gateway'];return $m[$f]??ucwords(str_replace('_',' ',$f));}
