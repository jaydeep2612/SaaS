<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Get the logged-in user
        $user = Auth::user();

        // 2. If I am NOT a Super Admin, force the restaurant_id to be MY restaurant_id
        if ($user->role !== 'super_admin') {
            $data['restaurant_id'] = $user->restaurant_id;
        }

        // 3. Return the modified data to be saved
        return $data;
    }
}
