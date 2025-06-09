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
                ->example('Ahmad Syahrul'),
            ImportColumn::make('nomor_hp')
                ->rules(['nullable', 'max:255'])
                ->example('081234567890'),
            ImportColumn::make('aktif')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean'])
                ->example('true'),
        ];
    }

    public function resolveRecord(): ?Fundraiser
    {
        // Try to find by 'nomor_hp'
        if (isset($this->data['nomor_hp']) && !empty($this->data['nomor_hp'])) {
            $fundraiser = Fundraiser::where('nomor_hp', $this->data['nomor_hp'])->first();
            if ($fundraiser) {
                return $fundraiser;
            }
        }

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
