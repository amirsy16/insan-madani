<?php

namespace App\Filament\Imports;

use App\Models\Fundraiser;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class FundraiserImporter extends Importer
{
    protected static ?string $model = Fundraiser::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_fundraiser')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('ZUL'),
            ImportColumn::make('nomor_identitas')
                ->rules(['nullable', 'max:255'])
                ->example('3201234567890123'),
            ImportColumn::make('nomor_hp')
                ->rules(['nullable', 'max:255'])
                ->example('081234567890'),
            ImportColumn::make('alamat')
                ->rules(['nullable'])
                ->example('Jl. Merdeka No. 123'),
            ImportColumn::make('aktif')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean'])
                ->example('true'),
        ];
    }

    /**
     * Logic anti-duplikat fundraiser:
     * 
     * 1. PRIORITAS NAMA: Jika nama_fundraiser sama persis → update data existing
     * 2. PRIORITAS HP: Jika nama berbeda tapi nomor_hp sama → update data existing
     * 3. BUAT BARU: Jika tidak ada yang sama → create fundraiser baru
     * 
     * Contoh:
     * - Import "ZULI" + HP "081234567890" → Jika "ZULI" sudah ada, update data
     * - Import "ADI" + HP "081234567890" → Jika HP sama tapi nama beda, update nama jadi "ADI"
     * - Import "BUDI" + HP "082222222222" → Jika benar-benar baru, create fundraiser baru
     */

    public function resolveRecord(): ?Fundraiser
    {
        // Prioritas 1: Cari berdasarkan nama yang sama persis
        if (isset($this->data['nama_fundraiser']) && !empty($this->data['nama_fundraiser'])) {
            $fundraiser = Fundraiser::where('nama_fundraiser', $this->data['nama_fundraiser'])->first();
            if ($fundraiser) {
                // Update data jika ada perubahan
                $fundraiser->update([
                    'nomor_identitas' => $this->data['nomor_identitas'] ?? $fundraiser->nomor_identitas,
                    'nomor_hp' => $this->data['nomor_hp'] ?? $fundraiser->nomor_hp,
                    'alamat' => $this->data['alamat'] ?? $fundraiser->alamat,
                    'aktif' => $this->data['aktif'] ?? $fundraiser->aktif,
                ]);
                return $fundraiser;
            }
        }

        // Prioritas 2: Jika nama tidak sama, coba cari berdasarkan nomor HP
        if (isset($this->data['nomor_hp']) && !empty($this->data['nomor_hp'])) {
            $fundraiser = Fundraiser::where('nomor_hp', $this->data['nomor_hp'])->first();
            if ($fundraiser) {
                // Update data jika ada perubahan
                $fundraiser->update([
                    'nama_fundraiser' => $this->data['nama_fundraiser'] ?? $fundraiser->nama_fundraiser,
                    'nomor_identitas' => $this->data['nomor_identitas'] ?? $fundraiser->nomor_identitas,
                    'alamat' => $this->data['alamat'] ?? $fundraiser->alamat,
                    'aktif' => $this->data['aktif'] ?? $fundraiser->aktif,
                ]);
                return $fundraiser;
            }
        }

        // Jika tidak ditemukan, buat record baru
        return new Fundraiser();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor fundraiser Anda telah selesai dan ' . number_format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' telah diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diimpor.';
        }

        return $body;
    }
}
