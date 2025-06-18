<?php

namespace App\Filament\Imports;

use App\Models\Donatur;
use App\Models\Pekerjaan;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class DonaturImporter extends Importer
{
    protected static ?string $model = Donatur::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Bapak Suhadi'),
            ImportColumn::make('nomor_hp')
                // ->rules(['nullable', 'max:255'])
                ->example('81274755000'),
            ImportColumn::make('alamat_detail')
                // ->rules(['nullable'])
                ->example('RT. 13 Tangkit'),
            ImportColumn::make('gender')
                ->label('Jenis Kelamin')
                ->rules(['required', 'in:male,female,organization'])
                ->example('male')
                ->helperText('Pilih: male (pria), female (wanita), organization (organisasi/perusahaan)'),
            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255'])
                ->example('suhadi@gmail.com'),
            ImportColumn::make('pekerjaan_id')
                ->label('Pekerjaan')
                ->rules(['nullable'])
                ->example('Karyawan Swasta'),
        ];
    }

    public function resolveRecord(): ?Donatur
    {
        // Ambil nama langsung tanpa deteksi gender atau pembersihan prefix
        $nama = trim($this->data['nama'] ?? '');
        
        // Ambil gender dari input atau default 'male'
        $gender = $this->data['gender'] ?? 'male';
        
        // Prioritas 1: Cari berdasarkan NAMA DAN NOMOR HP (keduanya harus cocok)
        if (!empty($nama) && isset($this->data['nomor_hp']) && !empty($this->data['nomor_hp'])) {
            $donatur = Donatur::where('nama', $nama)
                             ->where('nomor_hp', $this->data['nomor_hp'])
                             ->first();
            if ($donatur) {
                // Update data jika ada perubahan
                $donatur->update([
                    'alamat_detail' => $this->data['alamat_detail'] ?? $donatur->alamat_detail,
                    'gender' => $gender,
                    'email' => $this->data['email'] ?? $donatur->email,
                    'pekerjaan_id' => $this->resolvePekerjaan($this->data['pekerjaan_id'] ?? null) ?? $donatur->pekerjaan_id,
                ]);
                return $donatur;
            }
        }
        
        // Prioritas 2: Cari berdasarkan nama saja (jika nomor HP kosong)
        if (!empty($nama)) {
            $donatur = Donatur::where('nama', $nama)->first();
            if ($donatur) {
                // Update data jika ada perubahan
                $donatur->update([
                    'nomor_hp' => $this->data['nomor_hp'] ?? $donatur->nomor_hp,
                    'alamat_detail' => $this->data['alamat_detail'] ?? $donatur->alamat_detail,
                    'gender' => $gender,
                    'email' => $this->data['email'] ?? $donatur->email,
                    'pekerjaan_id' => $this->resolvePekerjaan($this->data['pekerjaan_id'] ?? null) ?? $donatur->pekerjaan_id,
                ]);
                return $donatur;
            }
        }
        
        // Jika tidak ditemukan, buat record baru
        $donatur = new Donatur();
        $donatur->nama = $nama;
        $donatur->gender = $gender;
        $donatur->pekerjaan_id = $this->resolvePekerjaan($this->data['pekerjaan_id'] ?? null);
        
        return $donatur;
    }
    
    /**
     * Resolve pekerjaan berdasarkan nama
     */
    private function resolvePekerjaan(?string $pekerjaanNama): ?int
    {
        if (!$pekerjaanNama) {
            return null;
        }
        
        $pekerjaan = Pekerjaan::where('nama', 'like', '%' . $pekerjaanNama . '%')->first();
        return $pekerjaan?->id;
    }
    
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import donatur Anda telah selesai dan ' . number_format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diimpor. Silakan unduh file CSV kegagalan untuk melihat detail error.';
        }

        return $body;
    }
}

/**
 * DONATUR IMPORTER - SIMPLIFIED VERSION
 * =====================================
 * 
 * Logika import donatur yang sudah disederhanakan:
 * 
 * 1. PRIORITAS PENCARIAN:
 *    - Cari berdasarkan NAMA + NOMOR HP (keduanya harus cocok)
 *    - Jika tidak ada, cari berdasarkan NAMA saja
 *    - Jika tidak ada, buat donatur baru
 * 
 * 2. TIDAK ADA DETEKSI GENDER OTOMATIS:
 *    - Nama disimpan apa adanya tanpa pembersihan prefix
 *    - Gender diambil dari kolom gender di CSV atau default 'male'
 * 
 * 3. NAMA BERBEDA + HP SAMA = DONATUR BERBEDA:
 *    - "Putri Yana" + "081234567890" = Donatur A
 *    - "Faras Novia" + "081234567890" = Donatur B (terpisah)
 * 
 * Contoh nama yang bisa diimport tanpa masalah:
 * - "IM Oka Mahaendra" → disimpan apa adanya
 * - "Dr. Siti Aminah" → disimpan apa adanya
 * - "Bapak Ahmad" → disimpan apa adanya
 */
