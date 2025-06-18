<?php

namespace App\Filament\Resources\FundraiserResource\Pages;

use App\Filament\Resources\FundraiserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFundraisers extends ListRecords
{
    protected static string $resource = FundraiserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
        ];
    }
}
