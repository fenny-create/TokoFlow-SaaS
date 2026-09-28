TOKOFLOW PROFESSIONAL V16
==========================

Basis: TokoFlow Professional POS V13, ditingkatkan tanpa membuang modul premium.

AKUN DEVELOPER LOKAL
---------------------
Email    : developer@tokoflow.local
Password : dev123456

Akun developer memiliki akses penuh dan menu "Uji Toko Nyata".

AKUN DEMO PEMILIK
-----------------
Email    : admin@tokoflow.local
Password : admin123

Fitur Basic tidak mengunci operasi inti toko: POS, produk, stok, kategori,
pengeluaran, laporan dasar, pelanggan, supplier, pembelian/restock, dan struk.

PREMIUM
-------
Pro/Business berisi fitur lanjutan seperti multi kasir, shift, promo, retur,
piutang, stock opname, backup, audit log, analitik, dan payment gateway.

UJI TOKO NYATA
--------------
Masuk sebagai developer lalu buka:
http://localhost/TokoFlow/index.php?p=store_test

Atau klik menu Developer > Uji Toko Nyata.

Pengujian otomatis menjalankan skenario:
09:00 buka toko
09:05 buka shift
09:10 barang datang
09:15 input pembelian
09:20 stok bertambah
09:30 pelanggan membeli
09:35 pembayaran QRIS DEMO
09:40 cetak struk
10:00 retur
12:00 cek stok
15:00 pengeluaran
20:00 tutup shift
20:05 laporan
20:10 backup SQL

QRIS pada pengujian adalah simulasi lokal dan tidak memotong saldo nyata.
Setiap run menambahkan data transaksi bertanda TEST ke akun developer.

INSTALASI
---------
1. Backup folder/database lama jika masih dibutuhkan.
2. Ekstrak folder TokoFlow ke C:\xampp\htdocs\TokoFlow
3. Jalankan Apache dan MySQL dari XAMPP.
4. Buka http://localhost/TokoFlow/
5. Database tokoflow dibuat/di-upgrade otomatis.
6. Jangan menghapus database tokoflow jika ingin mempertahankan data lama.

KEAMANAN LOKAL
--------------
Backup hasil pengujian disimpan di storage/backups dan dilindungi .htaccess.
Jangan memakai kredensial demo ini pada server publik.

PEMERIKSAAN
-----------
Seluruh file PHP V16 sudah lolos php -l pada saat paket dibuat.
ZIP juga sudah diuji dengan unzip -t.
Catatan: pengujian runtime MySQL tidak dapat dijalankan di lingkungan build ini
karena server MySQL XAMPP tidak tersedia di sini. Gunakan menu Uji Toko Nyata
pada mesin XAMPP untuk pengujian database end-to-end.
