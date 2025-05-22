<?php

namespace App\Filament\Resources\DonasiResource\Pages;

use App\Filament\Resources\DonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDonasi extends ViewRecord
{
    protected static string $resource = DonasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
