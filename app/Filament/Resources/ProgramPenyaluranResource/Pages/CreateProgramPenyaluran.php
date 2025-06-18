<?php

namespace App\Filament\Resources\ProgramPenyaluranResource\Pages;

use App\Filament\Resources\ProgramPenyaluranResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProgramPenyaluran extends CreateRecord
{
    protected static string $resource = ProgramPenyaluranResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
