<?php

namespace App\Filament\Resources\PenggunaanHakAmilResource\Pages;

use App\Filament\Resources\PenggunaanHakAmilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenggunaanHakAmils extends ListRecords
{
    protected static string $resource = PenggunaanHakAmilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
