<?php

namespace App\Filament\Resources\InvoiceDonasiResource\Pages;

use App\Filament\Resources\InvoiceDonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceDonasi extends ViewRecord
{
    protected static string $resource = InvoiceDonasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
