<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantTable; // ⚠️ Make sure you have created this Model!
use Illuminate\Support\Facades\Auth;

class Stats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        if($user->role === 'super_admin')
            {
                return [
                Stat::make('Restaurants', Restaurant::count())
                ->description('Total Restaurants')
                ->color('success'),
                Stat::make('Users', User::count())
                ->description('Total Users')
                ->color('primary'),
                // Stat::make('Categories', Category::count())
                // ->description('Total Categories')
                // ->color('warning'),
                // Stat::make('Menu Items', MenuItem::count())
                // ->description('Total Menu Items')
                // ->color('danger'),

                ];
            }

        $myId = $user->restaurant_id;

        return [
            Stat::make('My Staff', User::where('restaurant_id', $myId)->count())
                ->description('Waiters & Chefs')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Categories', Category::where('restaurant_id', $myId)->count())
                ->description('Food Categories')
                ->color('gray'),

            Stat::make('Menu Items', MenuItem::where('restaurant_id', $myId)->count())
                ->description('Dishes Available')
                ->descriptionIcon('heroicon-m-cake')
                ->color('warning'),

            // ⚠️ If you haven't created the 'Table' model yet, remove this block
            Stat::make('Tables', RestaurantTable::where('restaurant_id', $myId)->count())
                ->description('Dining Tables')
                ->descriptionIcon('heroicon-m-table-cells')
                ->color('success'),
        ];
    }
}
