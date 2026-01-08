<?php

namespace App\Filament\Resources\KitchenResource\Pages;

use App\Filament\Resources\KitchenResource;
use Filament\Resources\Pages\ListRecords;

class ListKitchens extends ListRecords
{
    protected static string $resource = KitchenResource::class;

    // 👇 Hides the "New Kitchen Resource" button at the top right
    protected function getHeaderActions(): array
    {
        return [];
    }
}