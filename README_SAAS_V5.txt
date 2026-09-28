TokoFlow SaaS V5

Fitur baru:
- Developer Panel memiliki form Buat Akun Pelanggan.
- Sekali submit: akun owner, settings toko, kategori Umum, paket aktif, masa berlaku, dan license key dibuat dalam satu transaksi database.
- Password sementara dibuat acak dan disimpan hanya sebagai hash.
- License langsung terikat ke akun pelanggan melalui used_by.
- Setelah pembuatan, data akses ditampilkan sekali dan dapat disalin.
- Data bisnis tetap dipisahkan berdasarkan user_id pada tabel aplikasi yang sudah ada.

Cara uji:
1. Ekstrak folder TokoFlow ke C:\xampp\htdocs\TokoFlow.
2. Jalankan Apache + MySQL.
3. Login developer: developer@tokoflow.local / dev123456.
4. Buka Panel Developer.
5. Isi Buat Akun Pelanggan.
6. Catat email, password sementara, dan license key.
7. Logout, lalu login menggunakan akun pelanggan tersebut.

Catatan produksi:
- Hosting + HTTPS masih diperlukan agar dapat diakses dari internet.
- Payment gateway, email otomatis, rate limiting, backup terjadwal, dan pengelolaan perangkat belum diaktifkan.
