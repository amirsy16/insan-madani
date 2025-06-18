<?php

namespace App\Filament\Resources\PengaturanHakAmilResource\Pages;

use App\Filament\Resources\PengaturanHakAmilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengaturanHakAmil extends EditRecord
{
    protected static string $resource = PengaturanHakAmilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
