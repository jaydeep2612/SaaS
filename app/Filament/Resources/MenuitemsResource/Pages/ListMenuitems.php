<?php

namespace App\Filament\Resources\MenuitemsResource\Pages;

use App\Filament\Resources\MenuitemsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenuitems extends ListRecords
{
    protected static string $resource = MenuitemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
