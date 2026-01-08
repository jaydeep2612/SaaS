<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KitchenResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class KitchenResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-tv'; // TV Icon
    protected static ?string $navigationLabel = 'Kitchen Display';
    protected static ?string $navigationGroup = 'Kitchen Management';
    protected static ?int $navigationSort = 1;

    // 🚫 1. Disable "Create" button (Chefs don't create orders)
    public static function canCreate(): bool
    {
       return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Empty form since we don't edit here
    }

    public static function table(Table $table): Table
{
    return $table
        ->poll('5s')
        
        // 🔲 Grid Layout
        ->contentGrid([
            'md' => 2,
            'xl' => 3,
        ])
        
        // 🔍 Query & Optimization
        ->modifyQueryUsing(function (Builder $query) {
            return $query
                ->where('restaurant_id', auth()->user()->restaurant_id)
                ->whereIn('status', ['placed', 'pending', 'preparing'])
                ->orderBy('created_at', 'asc')
                // 🚀 Optimize: Pre-load all relationships to prevent slowness
                ->with(['items.menuItem.category', 'table']); 
        })

        // 🏗️ CARD LAYOUT (Stack)
        ->columns([
            \Filament\Tables\Columns\Layout\Stack::make([
                
                // 1. TOP: Table Number & Timer
                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Table')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                    ->weight('bold')
                    ->formatStateUsing(fn ($state, Order $record) => "Table {$state} • #{$record->id}")
                    ->description(fn (Order $record) => $record->created_at->diffForHumans()),

                // 2. MIDDLE: Custom Food List (Qty x Name + Category)
                Tables\Columns\TextColumn::make('items_list')
                    ->label('Items')
                    ->state(function (Order $record) {
                        // Loop through every item in the order
                        return $record->items->map(function ($item) {
                            // Format: "2 x Burger (Main Course)"
                            $qty = $item->quantity;
                            $name = $item->menuItem->name;
                            $cat = $item->menuItem->category->name ?? 'No Category';
                            
                            // HTML String
                            return "<div class='py-1'>
                                        <span class='font-bold text-primary-500'>{$qty}x</span> 
                                        <span>{$name}</span> 
                                        <span class='text-gray-400 text-xs'>({$cat})</span>
                                    </div>";
                        })->implode(''); // Join them all together
                    })
                    ->html() // ⚠️ REQUIRED: Tells Filament to render the HTML tags
                    ->extraAttributes(['class' => 'py-2']), // Add spacing

                // 3. BOTTOM: Status Badge
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'placed', 'pending' => 'gray',
                        'preparing' => 'warning',
                        'ready' => 'success',
                        default => 'gray',
                    }),
            ])->space(3),
        ])
        
        // 👇 ACTIONS (Buttons at the bottom)
        ->actions([
            Tables\Actions\Action::make('start_cooking')
                ->label('Start Cooking')
                ->icon('heroicon-m-fire')
                ->color('warning')
                ->button()
                ->authorize(true)
                ->visible(fn (Order $record) => in_array(strtolower($record->status), ['placed', 'pending']))
                ->action(fn (Order $record) => $record->update(['status' => 'preparing'])),

            Tables\Actions\Action::make('mark_ready')
                ->label('Order Ready')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->button()
                ->authorize(true)
                ->visible(fn (Order $record) => strtolower($record->status) === 'preparing')
                ->action(fn (Order $record) => $record->update(['status' => 'ready'])),
        ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKitchens::route('/'),
        ];
    }
}