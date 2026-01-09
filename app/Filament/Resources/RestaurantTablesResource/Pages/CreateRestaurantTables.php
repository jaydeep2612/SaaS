<?php

namespace App\Filament\Resources\RestaurantTablesResource\Pages;

use App\Filament\Resources\RestaurantTablesResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Auth;

class CreateRestaurantTables extends CreateRecord
{
    protected static string $resource = RestaurantTablesResource::class;

    protected function handleRecordCreation(array $data): RestaurantTable
    {
        $restaurantId = auth()->user()->role === 'super_admin'
            ? $data['restaurant_id']
            : Auth::user()->restaurant_id;

        $totalTables = (int) $data['total_tables'];

        // Find last table number to avoid duplicates
        $lastTableNumber = RestaurantTable::where('restaurant_id', $restaurantId)
            ->max('table_number') ?? 0;

        // Create tables
        for ($i = 1; $i <= $totalTables; $i++) {
            RestaurantTable::create([
                'restaurant_id' => $restaurantId,
                'table_number' => $lastTableNumber + $i,
            ]);
        }

        // Return last created record (Filament requires this)
        return RestaurantTable::where('restaurant_id', $restaurantId)
            ->latest('id')
            ->first();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
