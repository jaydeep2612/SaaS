<?php

namespace App\Filament\Resources\RestaurantsResource\Pages;

use App\Filament\Resources\RestaurantsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestaurants extends EditRecord
{
    protected static string $resource = RestaurantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
