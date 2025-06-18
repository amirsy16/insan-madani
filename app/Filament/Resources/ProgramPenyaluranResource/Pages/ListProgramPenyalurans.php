<?php

namespace App\Filament\Resources\ProgramPenyaluranResource\Pages;

use App\Filament\Resources\ProgramPenyaluranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProgramPenyalurans extends ListRecords
{
    protected static string $resource = ProgramPenyaluranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
