<?php
declare(strict_types=1);
require_once 'config.php';
if(!isset($_SESSION['uid'])){header('Location: index.php');exit;}
$uid=(int)$_SESSION['uid'];
$st=$conn->prepare("SELECT plan_code FROM users WHERE id=?");$st->bind_param('i',$uid);$st->execute();$u=$st->get_result()->fetch_assoc();$st->close();
$pi=planMeta($u['plan_code']??'basic');
if(!in_array('backup',$pi['features'],true)){header('Location: index.php?p=upgrade&locked=backup');exit;}
if(isset($_GET['download'])){
 header('Content-Type: application/sql; charset=utf-8');
 header('Content-Disposition: attachment; filename="tokoflow_backup_'.date('Ymd_His').'.sql"');
 $tables=['users','settings','categories','products','customers','suppliers','sales','sale_items','stock_logs','expenses','purchases','purchase_items','audit_logs','shifts','promos','receivables','receivable_payments','sales_returns','sales_return_items','stock_opnames','stock_opname_items','test_runs'];
 echo "-- TokoFlow backup\n-- User ID: {$uid}\n-- Generated: ".date('c')."\n\n";
 foreach($tables as $table){
  $where=in_array($table,['users','settings','categories','products','customers','suppliers','sales','stock_logs','expenses','purchases','audit_logs'],true)?" WHERE user_id=".$uid:'';
  if($table==='sale_items')$where=" WHERE sale_id IN (SELECT id FROM sales WHERE user_id=".$uid.")";
  if($table==='purchase_items')$where=" WHERE purchase_id IN (SELECT id FROM purchases WHERE user_id=".$uid.")";
  if($table==='receivable_payments')$where=" WHERE receivable_id IN (SELECT id FROM receivables WHERE user_id=".$uid.")";
  if($table==='sales_return_items')$where=" WHERE return_id IN (SELECT id FROM sales_returns WHERE user_id=".$uid.")";
  if($table==='stock_opname_items')$where=" WHERE opname_id IN (SELECT id FROM stock_opnames WHERE user_id=".$uid.")";
  $res=$conn->query("SELECT * FROM `$table`$where");
  if(!$res)continue;
  $cols=[]; $fields=$conn->query("SHOW COLUMNS FROM `$table`");while($f=$fields->fetch_assoc())$cols[]='`'.$f['Field'].'`';
  while($row=$res->fetch_assoc()){
   $vals=[];foreach($cols as $c){$key=trim($c,'`');$vals[]=$row[$key]===null?'NULL':"'".$conn->real_escape_string((string)$row[$key])."'";}
   echo "INSERT INTO `$table` (".implode(',',$cols).") VALUES (".implode(',',$vals).");\n";
  }
 }
 exit;
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Backup · TokoFlow</title><link rel="stylesheet" href="assets/style.css"></head><body><main style="margin:0 auto;max-width:900px"><div class="panel" style="margin-top:60px"><h2>Backup Database</h2><p>Backup ini berisi data toko milik akun yang sedang masuk. Simpan file SQL di lokasi aman.</p><a class="primary button" href="backup.php?download=1">Download Backup SQL</a><br><br><a class="button" href="index.php?p=upgrade">Kembali</a></div></main></body></html>
