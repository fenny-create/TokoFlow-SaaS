<?php
declare(strict_types=1);
require_once 'config.php';
if(!isset($_SESSION['uid'])){header('Location: index.php');exit;}
$uid=(int)$_SESSION['uid'];
$u=$conn->query("SELECT nama,email,role,plan_code FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
$settings=$conn->query("SELECT theme,mode FROM settings WHERE user_id=$uid LIMIT 1")->fetch_assoc()?:[];
$theme=h($settings['theme']??'indigo');$mode=h($settings['mode']??'dark');
if(($u['role']??'')!=='developer' && !in_array('audit',planMeta($u['plan_code']??'basic')['features'],true)){header('Location: index.php?p=upgrade&locked=audit');exit;}
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$rows=$conn->query("SELECT a.id,a.created_at,a.aksi,a.detail,u.nama,u.role FROM audit_logs a JOIN users u ON u.id=a.user_id WHERE a.user_id=$uid ORDER BY a.id DESC LIMIT 200");
function auditType(string $aksi): string {
  $a=strtolower($aksi);
  if(str_contains($a,'hapus')||str_contains($a,'delete')||str_contains($a,'retur')) return 'DELETE';
  if(str_contains($a,'update')||str_contains($a,'ubah')||str_contains($a,'pengaturan')||str_contains($a,'tutup')||str_contains($a,'buka')) return 'UPDATE';
  return 'CREATE';
}
function auditJson(array $r): string {
  return json_encode(['time'=>$r['created_at'],'user'=>$r['nama'],'role'=>$r['role'],'activity'=>$r['aksi'],'detail'=>$r['detail']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Audit Log · TokoFlow</title><link rel="stylesheet" href="assets/style.css"><script defer src="assets/app.js"></script></head>
<body class="theme-<?=$theme?> mode-<?=$mode?> audit-page"><aside><div class="brand"><span>T</span><div><b>TokoFlow</b><small>POS & Manajemen Toko</small></div></div><nav><div class="nav-group-title">SISTEM</div><a href="index.php?p=dashboard"><span class="navicon">⌂</span>Dashboard</a><a class="on" href="index.php?p=audit_log"><span class="navicon">⌁</span>Audit Log</a><a href="index.php?p=settings"><span class="navicon">⚙</span>Pengaturan</a></nav><div class="sideuser"><b><?=h($u['nama']??'User')?></b><small><?=h($u['role']??'')?></small><span class="plan-mini"><?=h(strtoupper($u['plan_code']??'basic'))?></span><a href="index.php?p=dashboard">Kembali ke aplikasi</a><a href="index.php?logout=1">Keluar</a></div></aside>
<main><header><div><small><?=date('d M Y')?></small><h2>Audit Log</h2></div><div class="audit-actions"><a class="button" href="index.php?p=dashboard">← Dashboard</a></div></header>
<section class="pro-banner"><div><span class="pro-badge">SYSTEM</span><b>Jejak aktivitas toko</b><small>Catatan perubahan, transaksi, stok, pengaturan, dan aktivitas akun yang tersimpan di database.</small></div><span class="pro-spark">⌁</span></section>
<section class="panel audit-panel"><div class="panelhead"><div><h3>Aktivitas Terbaru</h3><small class="muted">Klik baris untuk membuka detail perubahan.</small></div><span class="tag"><?=mysqli_num_rows($rows)?> catatan</span></div><div class="tablewrap"><table class="audit-table"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Jenis Aktivitas</th><th>Detail</th></tr></thead><tbody><?php if(mysqli_num_rows($rows)):while($r=$rows->fetch_assoc()):$type=auditType($r['aksi']);$json=auditJson($r);?><tr class="audit-row" tabindex="0" onclick="toggleAuditRow(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleAuditRow(this)}"><td><b><?=h(date('d/m/Y H:i:s',strtotime($r['created_at'])))?></b><small><?=h(date('D',strtotime($r['created_at'])))?></small></td><td><div class="audit-user"><span class="avatar"><?=h(strtoupper(substr($r['nama'],0,1)))?></span><div><b><?=h($r['nama'])?></b><span class="role-badge"><?=h(strtoupper($r['role']))?></span></div></div></td><td><span class="audit-pill audit-<?=strtolower($type)?>"><?=$type?></span><small class="audit-action"><?=h($r['aksi'])?></small></td><td><span class="audit-detail-preview"><?=h($r['detail']?:'Tidak ada detail tambahan.')?></span><span class="audit-expand">⌄</span></td></tr><tr class="audit-detail-row"><td colspan="4"><div class="audit-detail-box"><div><b>Detail perubahan</b><span>JSON / event payload</span></div><pre><?=h($json)?></pre></div></td></tr><?php endwhile;else:?><tr><td colspan="4"><div class="empty">Belum ada aktivitas yang tercatat.</div></td></tr><?php endif;?></tbody></table></div></section>
</main></body></html>
