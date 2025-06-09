# 📊 Panduan dan Template Import Data

## 📁 File yang Tersedia

### 📖 Dokumentasi
- **`Panduan_Import_Data_Lengkap.html`** - Panduan lengkap step-by-step dalam format HTML yang mudah dibuka di browser dan bisa dikopi ke Microsoft Word

### 📋 Template CSV
- **`template_import_donatur.csv`** - Template untuk import data donatur (7 kolom)
- **`template_import_fundraiser.csv`** - Template untuk import data fundraiser (3 kolom) 
- **`template_import_donasi.csv`** - Template untuk import data donasi (5 kolom)

## 🚀 Cara Menggunakan

### 1. Baca Panduan Terlebih Dahulu
- Buka file `Panduan_Import_Data_Lengkap.html` di browser
- Atau buka dengan aplikasi text editor untuk melihat kode HTML
- Panduan berisi penjelasan detail setiap kolom, format yang benar, dan contoh-contoh

### 2. Download Template
- Gunakan template CSV yang sudah disediakan
- Edit sesuai dengan data Anda
- Pastikan mengikuti format yang sudah ditentukan

### 3. Urutan Import
⚠️ **PENTING**: Import data dalam urutan yang benar!

1. **Donatur** - Import terlebih dahulu
2. **Fundraiser** - Import setelah donatur
3. **Donasi** - Import terakhir (karena membutuhkan data donatur yang sudah ada)

## ✅ Fitur yang Sudah Disederhanakan

### Donatur (7 kolom dari 15+ kolom)
- nama ✅
- gender ✅  
- nomor_hp ✅
- email ✅
- alamat ✅
- pekerjaan ✅
- aktif ✅

### Fundraiser (3 kolom dari 6 kolom)
- nama_fundraiser ✅
- nomor_hp ✅
- aktif ✅

### Donasi (5 kolom dari 15+ kolom)
- donatur ✅ (kode donatur/email/nomor HP)
- jenisDonasi ✅
- metodePembayaran ✅
- jumlah ✅
- tanggal_donasi ✅

## 🔧 Perbaikan yang Sudah Dilakukan

1. ✅ **Fixed validation errors** - Aturan validasi sudah diperbaiki
2. ✅ **Simplified columns** - Hanya kolom essential yang wajib diisi
3. ✅ **Enhanced resolveRecord** - Logic pencarian data sudah diperbaiki
4. ✅ **Added examples** - Setiap kolom dilengkapi contoh berdasarkan data seeder
5. ✅ **Fixed typos** - Nama field yang salah ketik sudah diperbaiki

## 💡 Tips Sukses Import

- 🔍 **Test dulu dengan data kecil** (2-3 baris)
- 💾 **Backup data** sebelum import besar
- 📝 **Ikuti format** yang sudah ditentukan
- ⚠️ **Cek encoding** - Gunakan UTF-8
- 📊 **Import bertahap** - Jangan sekaligus ribuan data

## 📞 Support

Jika ada masalah atau error saat import:
1. Periksa format data sesuai panduan
2. Pastikan master data (jenis donasi, metode pembayaran) sudah tersedia
3. Test dengan data kecil terlebih dahulu
4. Hubungi administrator sistem

---
*File ini dibuat untuk memudahkan proses import data donatur, fundraiser, dan donasi*
