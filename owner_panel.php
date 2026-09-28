<?php
if (!isset($conn,$uid,$me)) { exit('Akses tidak valid.'); }
if (($me['role'] ?? '') !== 'owner') { go('?p=dashboard&denied=owner'); }
$ownerId=(int)$uid;
$totalKasir=0;
$q=$conn->query("SELECT COUNT(*) n FROM users WHERE owner_id={$ownerId} AND role IN ('kasir','staff')");
if($q){ $totalKasir=(int)($q->fetch_assoc()['n']??0); }
$activeKasir=0;
$q=$conn->query("SELECT COUNT(*) n FROM users WHERE owner_id={$ownerId} AND role IN ('kasir','staff') AND plan_status='active'");
if($q){ $activeKasir=(int)($q->fetch_assoc()['n']??0); }
$rows=$conn->query("SELECT id,nama,email,role,must_change_password,created_at FROM users WHERE owner_id={$ownerId} AND role IN ('kasir','staff') ORDER BY id DESC");
?>
<div class="owner-panel-wrap">
  <div class="owner-hero"><div><span class="section-kicker">OWNER CONTROL CENTER</span><h1>Panel Owner</h1><p>Kelola akun kasir dan akses tim toko dari satu tempat.</p></div><div class="owner-hero-badge">👑 Owner</div></div>
  <?php if(isset($_GET['created'])): ?><div class="alert good">Akun kasir berhasil dibuat.</div><?php endif; ?>
  <?php if(isset($_GET['reset'])): ?><div class="alert good">Password kasir berhasil direset. Kasir wajib mengganti password tersebut saat login berikutnya.</div><?php endif; ?>
  <?php if(isset($_GET['deleted'])): ?><div class="alert good">Akun kasir berhasil dihapus dan tidak dapat login lagi.</div><?php endif; ?>
  <div class="owner-stat-grid">
    <div class="owner-stat"><span>👥</span><div><small>Total Kasir</small><b><?= $totalKasir ?></b></div></div>
    <div class="owner-stat"><span>✓</span><div><small>Akun Aktif</small><b><?= $activeKasir ?></b></div></div>
    <div class="owner-stat"><span>🏪</span><div><small>Akses Toko</small><b>1 Owner</b></div></div>
  </div>
  <div class="owner-grid">
    <section class="panel owner-create-card">
      <div class="owner-card-head"><div><span class="section-kicker">AKUN TIM</span><h2>Tambah Kasir</h2><p>Buat akun kasir baru untuk toko Anda.</p></div><span class="owner-icon">＋</span></div>
      <form method="post" class="owner-form"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="act" value="staff"><label>Nama kasir</label><input name="nama" placeholder="Contoh: Andi" required minlength="2"><label>Email login</label><input type="email" name="email" placeholder="kasir@toko.com" required><label>Password sementara</label><input type="password" name="password" placeholder="Minimal 8 karakter" minlength="8" required><button class="primary full owner-create-btn" type="submit">＋ Buat Akun Kasir</button><small class="muted">Kasir wajib mengganti password saat login pertama.</small></form>
    </section>
    <section class="panel owner-team-card">
      <div class="owner-card-head"><div><span class="section-kicker">TIM TOKO</span><h2>Daftar Kasir</h2><p>Kelola akun kasir toko Anda.</p></div><span class="owner-icon">👥</span></div>
      <div class="owner-team-list">
      <?php if(!$rows || $rows->num_rows===0): ?><div class="owner-empty"><div>👤</div><b>Belum ada kasir</b><small>Buat akun kasir pertama dari form di sebelah.</small></div>
      <?php else: while($r=$rows->fetch_assoc()): ?>
        <div class="owner-team-row owner-team-row-actions">
          <div class="owner-avatar"><?= e(strtoupper(substr($r['nama'],0,1))) ?></div>
          <div class="owner-team-info"><b><?= e($r['nama']) ?></b><small><?= e($r['email']) ?></small></div>
          <div class="owner-team-status"><span class="tag">Kasir</span><?php if((int)$r['must_change_password']===1): ?><small>Ganti password</small><?php else: ?><small>Siap digunakan</small><?php endif; ?></div>
          <div class="owner-team-actions">
            <form method="post" onsubmit="return resetCashier(<?= (int)$r['id'] ?>)"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="act" value="reset_cashier_password"><input type="hidden" name="cashier_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="new_password" id="pw-<?= (int)$r['id'] ?>"><button class="button" type="submit">Reset Password</button></form>
            <form method="post" onsubmit="return confirm('Hapus akun kasir <?= e(addslashes($r['nama'])) ?>? Akun ini tidak bisa login lagi.');"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="act" value="delete_cashier"><input type="hidden" name="cashier_id" value="<?= (int)$r['id'] ?>"><button class="button danger" type="submit">Hapus</button></form>
          </div>
        </div>
      <?php endwhile; endif; ?>
      </div>
    </section>
  </div>
  <section class="panel owner-permission-card"><div class="owner-card-head"><div><span class="section-kicker">HAK AKSES</span><h2>Peran Kasir</h2><p>Kasir hanya mendapatkan fitur operasional yang dibutuhkan.</p></div></div><div class="owner-permissions"><div><b>✓ Transaksi</b><small>Kasir dan checkout</small></div><div><b>✓ Produk</b><small>Melihat produk saat transaksi</small></div><div><b>✓ Struk</b><small>Mencetak bukti transaksi</small></div><div><b>✓ Shift</b><small>Buka dan tutup shift</small></div><div class="locked"><b>🔒 Pengaturan</b><small>Khusus Owner</small></div><div class="locked"><b>🔒 Data sensitif</b><small>Khusus Owner</small></div></div></section>
</div>
<script>
function resetCashier(id){
  const pw=prompt('Masukkan password baru untuk kasir (minimal 8 karakter):');
  if(pw===null)return false;
  if(pw.length<8){alert('Password minimal 8 karakter.');return false;}
  const pw2=prompt('Ulangi password baru:');
  if(pw2===null || pw!==pw2){alert('Konfirmasi password tidak sama.');return false;}
  document.getElementById('pw-'+id).value=pw;
  return true;
}
</script>
