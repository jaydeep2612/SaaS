<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KitchenResource\Pages;
use App\Filament\Resources\KitchenResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!

class KitchenResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Kitchen Orders';
    protected static ?string $navigationGroup = 'Kitchen Management';
    protected static ?int $navigationSort = 2;
    public static function canCreate(): bool
{
   return false;
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

        public static function table(Table $table): Table
    {
        return $table
            // 1. Auto-Refresh every 5 seconds
            ->poll('5s')
            // 3. Grid Layout
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                // 🟢 Header
                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Table')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                    ->weight('bold')
                    ->description(fn (Order $record) => 'Order #' . $record->id . ' • ' . $record->created_at->diffForHumans()),

                // 🍔 Items List
                Tables\Columns\TextColumn::make('items.menuItem.name')
                    ->label('Items')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->color('primary'),

                // 🎨 Status Badge
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'placed', 'pending' => 'gray', // Handle both
                        'preparing' => 'warning',
                        'ready' => 'success',
                        default => 'gray',
                    }),
            ])
            // 4. THE BUTTONS (Updated Logic)
            ->actions([
                // Button A: "Start Cooking"
                Tables\Actions\Action::make('start_cooking')
                    ->label('Start Cooking')
                    ->icon('heroicon-m-fire')
                    ->color('warning')
                    ->button(),
                    // 👇 FIX: Handle case sensitivity (Placed vs placed)
                    
                // Button B: "Order Ready"
                Tables\Actions\Action::make('mark_ready')
                    ->label('Order Ready')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    // 👇 FIX: Handle case sensitivity
                
            ]);
            
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKitchens::route('/'),
            // 'create' => Pages\CreateKitchen::route('/create'),
            // 'edit' => Pages\EditKitchen::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()->role === 'super_admin') {
            return $query;
        }
        return $query->where('restaurant_id', auth()->user()->restaurant_id);
    }
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return in_array($user->role, ['admin', 'manager', 'chef', 'waiter']);
    }
}
