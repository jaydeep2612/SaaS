<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantTablesResource\Pages;
use App\Filament\Resources\RestaurantTablesResource\RelationManagers;
use App\Models\RestaurantTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this!

class RestaurantTablesResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

        // Restaurant (Super Admin only)
        Forms\Components\Select::make('restaurant_id')
            ->relationship('restaurant', 'name')
            ->visible(fn () => auth()->user()->role === 'super_admin')
            ->required(fn () => auth()->user()->role === 'super_admin'),

        // TOTAL TABLES INPUT
        Forms\Components\TextInput::make('total_tables')
            ->label('Total Tables')
            ->numeric()
            ->minValue(1)
            ->required(),
    ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('table_number')
                    ->searchable()
                    ->sortable(),
                tables\Columns\TextColumn::make('id')
                    ->label('Table ID')
                    ->sortable()
                    ->searchable(),
                    

                // Tables\Columns\TextColumn::make('capacity')
                //     ->sortable()
                //     ->label('Seats'),

                // // Badge shows color based on status
                // Tables\Columns\TextColumn::make('status')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'available' => 'success', // Green
                //         'occupied' => 'danger',   // Red
                //         'reserved' => 'warning',  // Orange
                //     }),

                Tables\Columns\TextColumn::make('restaurant.name')
                    ->visible(fn () => auth()->user()->role === 'super_admin')
                    ->label('Restaurant'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRestaurantTables::route('/'),
            'create' => Pages\CreateRestaurantTables::route('/create'),
            'edit' => Pages\EditRestaurantTables::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return in_array($user->role, ['admin', 'manager']);
    }
    public static function getEloquentQuery(): Builder
    {
        // 1. Get the standard list of orders from the database
        $query = parent::getEloquentQuery();

        // 2. Check who is logged in
        $user = Auth::user();

        // 3. If no one is logged in, show nothing (Safety check)
        if (! $user) {
            return $query->whereRaw('1 = 0'); 
        }

        // 4. If I am the SUPER ADMIN, stop here. Show me everything.
        if ($user->role === 'super_admin') {
            return $query;
        }

        // 5. If I am a NORMAL USER (Manager/Waiter), apply the filter.
        // "Show orders WHERE restaurant_id matches MY restaurant_id"
        return $query->where('restaurant_id', $user->restaurant_id);
    }
}
