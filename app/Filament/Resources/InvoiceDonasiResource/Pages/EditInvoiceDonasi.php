<?php

namespace App\Filament\Resources\InvoiceDonasiResource\Pages;

use App\Filament\Resources\InvoiceDonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceDonasi extends EditRecord
{
    protected static string $resource = InvoiceDonasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
