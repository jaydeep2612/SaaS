<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import
use App\Models\RestaurantTable;
use App\Models\MenuItem;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // 🟢 SECTION 1: ORDER HEADER
            Forms\Components\Section::make('Order Details')
                ->schema([
                    Forms\Components\Select::make('restaurant_id')
                        ->relationship('restaurant', 'name')
                        ->visible(fn () => auth()->user()->role === 'super_admin')
                        ->required(fn () => auth()->user()->role === 'super_admin'),

                    Forms\Components\Select::make('table_id')
                        ->relationship('table', 'table_number', function (Builder $query) {
                            if (auth()->user()->role !== 'super_admin') {
                                return $query->where('restaurant_id', auth()->user()->restaurant_id);
                            }
                            return $query;
                        })
                        ->searchable()
                        ->preload(),

                        //placed', 'preparing', 'ready', 'served','completed'
                    Forms\Components\Select::make('status')
                        ->options([
                            'placed' => 'Placed',
                            'preparing' => 'Preparing',
                            'ready' => 'Ready to Serve',
                            'served' => 'Served',
                            'completed' => 'Completed',
                        ])
                        ->default('placed')
                        ->required(),

                    // 👇 THIS IS THE TOTAL FIELD WE WANT TO AUTO-UPDATE
                    Forms\Components\TextInput::make('total_amount')
                        ->numeric()
                        ->prefix('$')
                        ->readOnly() // Make it Read Only so users don't mess it up
                        ->default(0),
                ])->columns(2),

            // 🟢 SECTION 2: ITEMS (THE CALCULATOR)
            Forms\Components\Section::make('Menu Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            
                            // 1. SELECT ITEM
                            Forms\Components\Select::make('menu_item_id')
                                ->label('Item')
                                ->options(function () {
                                    $query = \App\Models\MenuItem::query();
                                    if (auth()->user()->role !== 'super_admin') {
                                        $query->where('restaurant_id', auth()->user()->restaurant_id);
                                    }
                                    return $query->pluck('name', 'id');
                                })
                                ->required()
                                ->live() // ⚡ Update immediately when item changes
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    // A. Set the Unit Price for this row
                                    $price = \App\Models\MenuItem::find($state)?->price ?? 0;
                                    $set('price', $price);
                                    
                                    // B. Recalculate the Grand Total
                                    self::updateGrandTotal($get, $set);
                                }),

                            // 2. QUANTITY
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live() // ⚡ Update immediately when quantity changes
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    // A. Recalculate the Grand Total
                                    self::updateGrandTotal($get, $set);
                                }),

                            // 3. UNIT PRICE (Read Only)
                            Forms\Components\TextInput::make('price')
                                ->label('Unit Price')
                                ->disabled()
                                ->dehydrated()
                                ->numeric()
                                ->prefix('$'),

                            // 4. SUBTOTAL (Visual Only - Optional)
                            // This shows "50 * 3 = 150" for the user to see
                            Forms\Components\Placeholder::make('subtotal_display')
                                ->label('Subtotal')
                                ->content(function (Forms\Get $get) {
                                    $q = intval($get('quantity'));
                                    $p = floatval($get('price'));
                                    return '$' . number_format($q * $p, 2);
                                }),

                        ])
                        ->columns(4) // Make space for the new columns
                        ->addActionLabel('Add Dish')
                        // 👇 Also update total when a row is deleted
                        ->live()
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                            self::updateGrandTotal($get, $set);
                        }),
                ]),
        ]);
}

// 👇 HELPER FUNCTION: Calculates the sum of all rows
// Paste this RIGHT AFTER the form() function closes, but inside the Class
public static function updateGrandTotal(Forms\Get $get, Forms\Set $set): void
{
    // 1. Get all items from the repeater
    // We use '../../items' to go up out of the row to the main form
    $items = $get('../../items') ?? []; 
    
    // If we are triggering this from the main form (like delete), path might be just 'items'
    if (empty($items)) {
        $items = $get('items') ?? [];
    }

    $grandTotal = 0;

    // 2. Loop through and sum (Quantity * Price)
    foreach ($items as $item) {
        $qty = intval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $grandTotal += ($qty * $price);
    }

    // 3. Set the Total Amount field
    $set('../../total_amount', $grandTotal);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->label('Order #'),
                
                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Table')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'placed' => 'gray',
                        'preparing' => 'warning',
                        'ready' => 'success',
                        'served' => 'info',
                        'completed' => 'primary',
                        
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('INR'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('start_cooking')
                    ->label('Start Cooking')
                    ->icon('heroicon-m-fire')
                    ->color('warning')
                    ->button()
                    ->visible(fn (Order $record) => in_array(strtolower($record->status), ['placed', 'pending']))
                    ->action(fn (Order $record) => $record->update(['status' => 'preparing'])),
                    
                Tables\Actions\Action::make('mark_ready')
                    ->label('Order Ready')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (Order $record) => strtolower($record->status) === 'preparing')
                    ->action(fn (Order $record) => $record->update(['status' => 'ready'])),
            ])
            ->poll('5s')
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
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
