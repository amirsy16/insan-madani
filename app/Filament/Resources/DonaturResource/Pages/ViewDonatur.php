<?php

namespace App\Filament\Resources\DonaturResource\Pages;

use App\Filament\Resources\DonaturResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDonatur extends ViewRecord
{
    protected static string $resource = DonaturResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
            ,
        ];
    }

}
