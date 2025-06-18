# Top Donatur Widget - Blade Views

## 📁 Struktur Folder Blade Views

```
resources/views/filament/widgets/top-donatur/
├── detail-modal.blade.php      # Modal detail informasi donatur
├── analytics-modal.blade.php   # Modal analisis dan insights
└── README.md                   # Dokumentasi ini
```

## 🎯 Deskripsi Setiap Blade View

### 1. **detail-modal.blade.php**
- **Fungsi**: Menampilkan informasi lengkap donatur dalam modal popup
- **Data Input**: `$record` (object donatur dengan relasi)
- **Fitur**:
  - Header info dengan nama, kontak, dan kategori donatur
  - Statistik utama (total donasi, frekuensi, rata-rata)
  - Informasi donasi (tanggal pertama/terakhir, jenis terfavorit)
  - Informasi lokasi (provinsi)
  - Durasi keaktifan dengan kalkulasi otomatis

### 2. **analytics-modal.blade.php**
- **Fungsi**: Menampilkan analisis mendalam dan insights strategis
- **Data Input**: 
  - `$totalDonatur` - Total donatur aktif
  - `$totalKontribusi` - Total kontribusi keseluruhan
  - `$topCount` - Jumlah top donatur yang ditampilkan
- **Fitur**:
  - Ringkasan statistik keseluruhan
  - Distribusi kategori donatur
  - Insights & rekomendasi strategis
  - Tips penggunaan filter

## 🔧 Penggunaan di Widget

### Detail Modal
```php
->modalContent(function ($record) {
    return view('filament.widgets.top-donatur.detail-modal', ['record' => $record]);
})
```

### Analytics Modal
```php
->modalContent(function () {
    return view('filament.widgets.top-donatur.analytics-modal', [
        'totalDonatur' => $this->totalDonaturAktif,
        'totalKontribusi' => $this->totalKontribusiKeseluruhan,
        'topCount' => $this->topCount
    ]);
})
```

## 🎨 Customization

Setiap blade view dapat disesuaikan dengan:
- Mengubah layout dan styling
- Menambah/mengurangi informasi yang ditampilkan
- Menyesuaikan warna tema
- Menambahkan komponen interaktif tambahan

## 📱 Responsive Design

Semua blade views sudah menggunakan:
- Grid system yang responsive (md:grid-cols-2, md:grid-cols-3)
- Dark mode support
- Mobile-friendly layout
- Tailwind CSS classes untuk konsistensi styling
