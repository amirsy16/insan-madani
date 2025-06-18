# Top Donatur Widget - Dokumentasi Fitur

## 🎯 Fitur Utama yang Telah Ditambahkan

### 1. **Filter Lengkap**
- **Jumlah Top Donatur**: 5, 10, 15, 20, 25, 50, 100
- **Periode Waktu**: 
  - Keseluruhan, Tahun ini/lalu, Bulan ini/lalu
  - 3/6/12 bulan terakhir, Custom date range
- **Pengurutan**: Total donasi, Frekuensi, Rata-rata, Tanggal terakhir (asc/desc)
- **Jenis Donasi**: Filter berdasarkan jenis donasi aktif
- **Provinsi**: Filter berdasarkan asal donatur
- **Kategori Donatur**: Platinum, Gold, Silver, Bronze, Regular
- **Minimal Donasi**: Input custom minimal total donasi
- **Minimal Frekuensi**: Input custom minimal frekuensi donasi
- **Konsistensi**: Sangat Tinggi, Tinggi, Sedang, Rendah, Sangat Rendah
- **Status Keaktifan**: Aktif (1/3/6/12 bulan), Tidak aktif

### 2. **Kolom Informasi Lengkap**
- **Ranking**: Badge dengan warna berbeda (Gold, Silver, Bronze)
- **Nama Donatur**: Dengan nomor HP dan tooltip email
- **Total Donasi**: Format currency dengan warna hijau
- **Frekuensi**: Badge dengan kategori warna berdasarkan tingkat aktivitas
- **Rata-rata Donasi**: Tersembunyi secara default, bisa ditampilkan
- **Tanggal Pertama/Terakhir**: Tracking timeline donasi
- **Kategori Donatur**: Badge dengan tingkatan (Platinum/Gold/Silver/Bronze/Regular)
- **Provinsi**: Informasi geografis
- **Jenis Terfavorit**: Jenis donasi yang paling sering dipilih
- **Durasi Aktif**: Berapa lama menjadi donatur aktif
- **Konsistensi**: Seberapa konsisten dalam berdonasi
- **Potensi Upgrade**: Analisis potensi naik kategori

### 3. **Actions & Interaksi**
- **Detail**: Modal lengkap dengan statistik mendalam donatur
- **WhatsApp**: Direct link ke chat WhatsApp (jika ada nomor)
- **Export**: Tombol untuk export data (siap untuk implementasi)
- **Refresh**: Update data terbaru
- **Analisis**: Modal dengan insights dan rekomendasi

### 4. **Analisis & Insights**
- **Statistik Real-time**: Total donatur, kontribusi, rata-rata
- **Distribusi Kategori**: Visualisasi pembagian donatur per kategori
- **Rekomendasi Strategis**: Tips untuk fundraising
- **Tips Penggunaan**: Panduan menggunakan filter

### 5. **User Experience**
- **Searchable**: Pencarian nama donatur
- **Sortable**: Semua kolom utama bisa diurutkan
- **Toggleable Columns**: Tampilkan/sembunyikan kolom sesuai kebutuhan
- **Responsive**: Tampilan adaptif untuk mobile/desktop
- **Tooltips**: Informasi tambahan saat hover
- **Color Coding**: Sistem warna konsisten untuk kategori/status

## 🔧 Penggunaan Optimal

### Untuk Fundraiser:
- Gunakan filter **Kategori Donatur** untuk targeting kampanye
- Manfaatkan kolom **Potensi Upgrade** untuk strategi upgrading
- Pantau **Status Keaktifan** untuk program reaktivasi

### Untuk Management:
- Analisis **Konsistensi** donatur untuk program loyalitas
- Review **Distribusi Geografis** untuk ekspansi wilayah
- Gunakan **Analytics** untuk insights strategis

### Untuk Tim Komunikasi:
- Akses **Detail** donatur untuk personalisasi komunikasi
- Gunakan tombol **WhatsApp** untuk komunikasi langsung
- Manfaatkan data **Jenis Terfavorit** untuk konten yang relevan

## 🚀 Implementasi Tambahan yang Disarankan

1. **Export Function**: Implementasi export ke Excel/CSV menggunakan Laravel Excel
2. **Email Integration**: Tombol kirim email langsung dari widget
3. **Notification System**: Alert untuk donatur yang perlu follow-up
4. **Chart Integration**: Grafik trend donasi per kategori
5. **Automated Segmentation**: Otomatis grouping berdasarkan behavior

## 🎨 Customization Options

Widget ini dapat disesuaikan dengan:
- Mengubah kategori donatur (nilai threshold)
- Menambah/mengurangi kolom sesuai kebutuhan
- Menyesuaikan warna tema organisasi
- Menambah filter custom sesuai kebutuhan spesifik
