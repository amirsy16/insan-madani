<?php

namespace App\Filament\Resources\ProgramPenyaluranResource\Pages;

use App\Filament\Resources\ProgramPenyaluranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProgramPenyaluran extends ViewRecord
{
    protected static string $resource = ProgramPenyaluranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

