<?php

namespace App\Filament\Resources\RestaurantTablesResource\Pages;

use App\Filament\Resources\RestaurantTablesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantTables extends EditRecord
{
    protected static string $resource = RestaurantTablesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
