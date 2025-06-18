<?php

namespace App\Filament\Resources\PengaturanHakAmilResource\Pages;

use App\Filament\Resources\PengaturanHakAmilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengaturanHakAmils extends ListRecords
{
    protected static string $resource = PengaturanHakAmilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
