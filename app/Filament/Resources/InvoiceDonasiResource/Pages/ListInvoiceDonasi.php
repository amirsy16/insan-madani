<?php

namespace App\Filament\Resources\InvoiceDonasiResource\Pages;

use App\Filament\Resources\InvoiceDonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceDonasi extends ListRecords
{
    protected static string $resource = InvoiceDonasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Disabled karena invoice dibuat otomatis
        ];
    }
}
