TokoFlow SaaS V8 - 4 Role RBAC

ROLE:
1. developer = panel SaaS rahasia, lisensi, pelanggan, server/test.
2. owner = pemilik toko, bisnis, laporan, backup, pengaturan, pegawai.
3. admin = operasional: produk, gudang, supplier, pelanggan, promo.
4. kasir = transaksi, shift, riwayat transaksi sendiri.

KEAMANAN:
- Akses halaman dan POST action dicek server-side.
- URL yang tidak sesuai role menghasilkan HTTP 403.
- developer_panel.php dan owner_panel.php tidak dapat dibuka role lain.
- Akun legacy role staff otomatis dinormalisasi menjadi kasir.
- Sales menyimpan created_by agar kasir hanya melihat transaksi miliknya sendiri.
- Database migration dijalankan otomatis oleh config.php tanpa menghapus data lama.

INSTALASI XAMPP:
1. Extract ZIP ke htdocs/TokoFlow.
2. Pastikan Apache + MySQL aktif.
3. Buka http://localhost/TokoFlow/.
4. Database tokoflow dibuat otomatis.

CATATAN:
- Developer pertama/akun developer harus dibuat melalui database atau mekanisme provisioning yang memang hanya Anda kontrol.
- Jangan membagikan developer credentials kepada owner pelanggan.
