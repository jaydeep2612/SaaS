<?php

namespace App\Filament\Resources\MenuitemsResource\Pages;

use App\Filament\Resources\MenuitemsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenuitems extends EditRecord
{
    protected static string $resource = MenuitemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
