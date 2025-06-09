# Contoh Data Import CSV

Folder ini berisi contoh data CSV untuk fitur import di sistem Madani.

## File Contoh

### 1. donatur_import_example.csv
Contoh data untuk import donatur dengan kolom:
- `kode_donatur`: Kode unik donatur (wajib)
- `gender`: Jenis kelamin (L/P) (wajib)
- `nama`: Nama lengkap donatur (wajib)
- `province`: Nama provinsi
- `city_id`: ID kota/kabupaten (angka)
- `district`: Nama kecamatan
- `village`: Nama kelurahan/desa
- `alamat_detail`: Alamat detail (nomor, nama jalan)
- `alamat_lengkap`: Alamat lengkap gabungan
- `nomor_hp`: Nomor handphone
- `email`: Alamat email
- `pekerjaan`: Jenis pekerjaan

### 2. fundraiser_import_example.csv
Contoh data untuk import fundraiser dengan kolom:
- `nama_fundraiser`: Nama lengkap fundraiser (wajib)
- `nomor_identitas`: Nomor KTP/identitas (unik)
- `nomor_hp`: Nomor handphone (unik)
- `alamat`: Alamat lengkap
- `user_email`: Email user yang terkait (harus sudah ada di sistem)
- `aktif`: Status aktif (true/false) (wajib)

### 3. donasi_import_example.csv
Contoh data untuk import donasi dengan kolom:
- `donatur_identifier`: Identifikasi donatur (kode/email/HP) - wajib jika bukan hamba Allah
- `atas_nama_hamba_allah`: Donasi anonim (true/false)
- `jenis_donasi_id`: Nama jenis donasi (wajib) - harus sesuai dengan data master
- `metode_pembayaran_id`: Nama metode pembayaran - harus sesuai dengan data master
- `fundraiser_identifier`: Identifikasi fundraiser (nama/nomor identitas)
- `jumlah`: Jumlah donasi dalam rupiah (wajib)
- `keterangan_infak_khusus`: Keterangan khusus untuk infaq
- `infaq_terikat_option_id`: Nama kategori infaq terikat
- `deskripsi_barang`: Deskripsi barang (untuk donasi barang)
- `perkiraan_nilai_barang`: Nilai estimasi barang (angka)
- `bukti_pembayaran`: URL/path bukti pembayaran
- `catatan_donatur`: Catatan dari donatur
- `tanggal_donasi`: Tanggal donasi (YYYY-MM-DD)
- `nomor_transaksi_unik`: Nomor transaksi unik
- `status_konfirmasi`: Status (pending/terkonfirmasi/ditolak)
- `catatan_konfirmasi`: Catatan konfirmasi admin

## Cara Menggunakan

1. Download template kosong melalui fitur import di admin panel Filament
2. Gunakan file contoh ini sebagai referensi format data
3. Sesuaikan data dengan kebutuhan Anda
4. Pastikan data master (jenis donasi, metode pembayaran, dll) sudah ada di sistem
5. Upload file CSV melalui fitur import

## Catatan Penting

- Format tanggal menggunakan YYYY-MM-DD
- Nilai boolean menggunakan true/false
- Pastikan email user sudah terdaftar sebelum import fundraiser
- Donatur harus sudah ada sebelum import donasi (kecuali donasi anonim)
- Semua field yang bertanda (wajib) harus diisi
