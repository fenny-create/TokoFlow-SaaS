TokoFlow V27 - UI Prompt Pack

Implementasi desain:
1. Kasir/POS modern dark mode: layout 60:40, product search + barcode, product grid, cart checkout, accordion piutang, CTA bayar/simpan.
2. Audit Log: clean data-dense table, role badge, CREATE/UPDATE/DELETE pills, expandable JSON detail.
3. Shift Kasir: modal rekonsiliasi dengan Modal Awal, penjualan tunai/non-tunai, kas aktual vs expected, indikator selisih, catatan wajib.
4. Dashboard: 4 KPI Omzet, Transaksi, Laba Kotor, Piutang + tren dibanding kemarin dan area chart.

Catatan:
- Tidak mengubah struktur database yang sudah ada.
- Catatan selisih shift disimpan di Audit Log agar tidak perlu migrasi schema.
- Payment gateway tetap mengikuti konfigurasi provider yang ada; perubahan ini fokus pada UI/UX dan alur tampilan.
