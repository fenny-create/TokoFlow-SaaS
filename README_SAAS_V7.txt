TOKOFLOW V7 - PRODUCTION READY

Perubahan utama:
1. Security headers dasar dan .htaccess untuk memblokir file sensitif.
2. Multi-user tenant dasar: akun kasir/staff memiliki owner_id sehingga data mengikuti toko pemilik.
3. Daftar tim sekarang hanya menampilkan kasir/staff dari toko aktif.
4. Kasir/staff tidak dapat membuka Pengaturan, Backup, Audit Log, Kelola Paket, atau Panel Developer.
5. UI dipoles untuk mobile dan desktop, dengan cache stylesheet V7.
6. Mode unlimited tetap dipertahankan: tidak ada masa kedaluwarsa paket.

PENTING HOSTING:
- Gunakan PHP 8.1+ dan MySQL/MariaDB yang sesuai.
- Aktifkan HTTPS.
- Ganti kredensial database pada config.php.
- Jangan membuka akses publik ke phpMyAdmin.
- Buat backup database terjadwal.
- Uji login, kasir, stok, laporan, cetak struk, akun owner dan kasir sebelum produksi.


V7.1 - Ganti Password
- Akun yang dibuat Developer memakai password sementara hanya untuk login pertama.
- Setelah login pertama, pelanggan/kasir otomatis diarahkan ke halaman Ganti Password.
- Password baru minimal 8 karakter.
- Setelah berhasil, flag must_change_password dimatikan.
- Semua pengguna juga dapat mengganti password dari Pengaturan > Keamanan Akun.
