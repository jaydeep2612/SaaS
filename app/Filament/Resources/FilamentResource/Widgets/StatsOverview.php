<?php

namespace App\Filament\Resources\FilamentResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantTables; // ⚠️ Make sure you have created this Model!
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
class StatsOverview extends Widget
{
    protected static string $view = 'filament.resources.filament-resource.widgets.stats-overview';

    protected static ?string $pollingInterval = '15s';

    // 2. Sort order: Keep this at the top of the dashboard
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();

        // 🟢 SCENARIO 1: SUPER ADMIN (Sees Global Totals)
        if ($user->role === 'super_admin') {
            return [
                Stat::make('Total Restaurants', Restaurant::count())
                    ->description('Active subscriptions')
                    ->descriptionIcon('heroicon-m-building-storefront')
                    ->color('success') // Green
                    ->chart([7, 3, 4, 5, 6, 3, 5, 3]), // Fake chart data for style

                Stat::make('Total Users', User::count())
                    ->description('Admins + Managers + Staff')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary'), // Blue

                Stat::make('Total Menu Items', MenuItem::count())
                    ->description('Dishes across all restaurants')
                    ->color('warning'), // Orange
            ];
        }

        // 🔵 SCENARIO 2: RESTAURANT MANAGER (Sees Only Their Data)
        // We assume the user has a 'restaurant_id'
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
            Stat::make('Tables', Table::where('restaurant_id', $myId)->count())
                ->description('Dining Tables')
                ->descriptionIcon('heroicon-m-table-cells')
                ->color('success'),
        ];
    }
}
