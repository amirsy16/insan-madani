<?php

namespace App\Filament\Resources\FundraiserResource\Pages;

use App\Filament\Resources\FundraiserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFundraiser extends CreateRecord
{
    protected static string $resource = FundraiserResource::class;

 protected function getRedirectUrl(): string
{
return $this->getResource()::getUrl('index');
}

}

