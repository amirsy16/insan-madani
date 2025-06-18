<?php

namespace App\Filament\Resources\PenggunaanHakAmilResource\Pages;

use App\Filament\Resources\PenggunaanHakAmilResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenggunaanHakAmil extends CreateRecord
{
    protected static string $resource = PenggunaanHakAmilResource::class;

    protected function getRedirectUrl(): string
{
return $this->getResource()::getUrl('index');
}
}
