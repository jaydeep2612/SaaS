<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KitchenResource\Pages;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; 

class KitchenResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Kitchen Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-fire';

    /**
     * Optimized Query:
     * 1. Loads relationships (Category & Items) to prevent "N+1" performance issues.
     * 2. Filters by Status (Only Placed & Preparing).
     * 3. Filters by Restaurant (Security).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // 1. Filter Status & Eager Load Data
        $query->whereIn('status', ['placed', 'preparing'])
              ->with(['items.menuItem.category']) // 👈 Pre-load category data
              ->orderBy('created_at', 'asc');

        // 2. Security Logic
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0'); // Show nothing if guest
        }

        if ($user->role === 'super_admin') {
            return $query; // Super Admin sees all
        }

        // Chef/Manager sees only their restaurant
        return $query->where('restaurant_id', $user->restaurant_id);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Auto-refresh every 5 seconds
            ->poll('5s')
            
            // Grid Layout: 1 card on mobile, 2 on tablet, 3 on desktop
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // --- CARD HEADER: Table & Time ---
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('table_id')
                            ->formatStateUsing(fn ($state) => "Table {$state}")
                            ->weight('bold')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->color('primary'),

                        Tables\Columns\TextColumn::make('created_at')
                            ->since()
                            ->color('gray')
                            ->alignRight(),
                    ]),

                    // --- CUSTOMER NAME ---
                    Tables\Columns\TextColumn::make('customer_name')
                        ->icon('heroicon-m-user')
                        ->weight('medium')
                        ->color('gray'),

                    // --- ORDER ITEMS (Qty + Name + Category) ---
                    // --- ORDER ITEMS (Guaranteed New Lines) ---
                    Tables\Columns\TextColumn::make('order_summary')
                        ->label('Order Items')
                        ->state(function (Order $record) {
                            // 1. Loop through items
                            return $record->items->map(function ($item) {
                                $qty = $item->quantity;
                                $name = $item->menuItem->name ?? 'Unknown';
                                $category = $item->menuItem->category->name ?? '-';
                                
                                // 2. Create the bullet line manually
                                // Example: "• 2x Burger (Main)"
                                return "&bull; <strong>{$qty}x {$name}</strong> <span class='text-gray-500 text-xs'>({$category})</span>";
                            })->join('<br>'); // 3. Join with an HTML break tag
                        })
                        ->html() // 👈 THIS IS THE KEY. It enables the HTML tags above.
                        ->color('gray'),

                    // --- STATUS BADGE ---
                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'placed' => 'danger',
                            'preparing' => 'warning',
                            'ready' => 'success',
                            default => 'gray',
                        }),
                ])->space(3),
            ])
            ->actions([
                // Button: Start Cooking
                Tables\Actions\Action::make('start_cooking')
                    ->label('Cook')
                    ->icon('heroicon-o-fire')
                    ->color('warning')
                    ->button()
                    ->visible(fn (Order $record) => $record->status === 'placed')
                    ->action(fn (Order $record) => $record->update(['status' => 'preparing'])),

                // Button: Order Ready
                Tables\Actions\Action::make('mark_ready')
                    ->label('Ready')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (Order $record) => $record->status === 'preparing')
                    ->action(fn (Order $record) => $record->update(['status' => 'ready'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKitchens::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; 
    }
}