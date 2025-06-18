<?php

namespace App\Filament\Resources\ProgramPenyaluranResource\Pages;

use App\Filament\Resources\ProgramPenyaluranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgramPenyaluran extends EditRecord
{
    protected static string $resource = ProgramPenyaluranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
