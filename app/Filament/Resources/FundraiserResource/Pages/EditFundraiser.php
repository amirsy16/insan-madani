<?php

namespace App\Filament\Resources\FundraiserResource\Pages;

use App\Filament\Resources\FundraiserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFundraiser extends EditRecord
{
    protected static string $resource = FundraiserResource::class;

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
