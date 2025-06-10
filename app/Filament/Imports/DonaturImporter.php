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
                ->example('Ahmad Budi Santoso'),
            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(['required', 'in:male,female'])
                ->example('male'),
            ImportColumn::make('nomor_hp')
                ->rules(['nullable', 'max:255'])
                ->example('6281234567890'),
            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255'])
                ->example('ahmad.budi@gmail.com'),
            ImportColumn::make('alamat_detail')
                ->rules(['nullable'])
                ->example('Jl. Merdeka No. 123, RT 02/RW 05'),
            ImportColumn::make('pekerjaan_id')
                ->label('Pekerjaan')
                ->relationship('pekerjaan', 'nama')
                ->rules(['nullable', 'exists:pekerjaans,nama'])
                ->example('Karyawan Swasta'),
        ];
    }

    public function resolveRecord(): ?Donatur
    {
        return new Donatur();
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
