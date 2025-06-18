<?php

namespace App\Filament\Resources\InvoiceDonasiResource\Pages;

use App\Filament\Resources\InvoiceDonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceDonasi extends CreateRecord
{
    protected static string $resource = InvoiceDonasiResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
}
