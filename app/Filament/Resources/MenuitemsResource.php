<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuitemsResource\Pages;
use App\Filament\Resources\MenuitemsResource\RelationManagers;
use App\Models\Menuitem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this!

class MenuitemsResource extends Resource
{
    protected static ?string $model = Menuitem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
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
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->visible(fn () => auth()->user()->role === 'super_admin')
                    ->required(fn () => auth()->user()->role === 'super_admin'),

                // 2. Category Selection
                // We use a query filter so Manager A doesn't see Manager B's categories
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name', function (Builder $query) {
                        // If not Super Admin, show only MY categories
                        if (auth()->user()->role !== 'super_admin') {
                            return $query->where('restaurant_id', auth()->user()->restaurant_id);
                        }
                        return $query;
                    })
                    ->required()
                    ->searchable()
                    ->preload(),

                // 3. Basic Details
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('₹') // or your currency symbol
                    ->required(),

                // 4. Image Upload
                Forms\Components\FileUpload::make('image')
                    ->directory('menu-items')
                    ->image(),

                // 5. Availability Toggle
                Forms\Components\Toggle::make('is_available')
                    ->label('Available Now')
                    ->default(true),
                Forms\Components\Hidden::make('restaurant_id')
                ->default(Auth::user()->restaurant_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->badge(), // Makes it look like a tag

                Tables\Columns\TextColumn::make('price')
                    ->money('INR') // Change currency as needed
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Available'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListMenuitems::route('/'),
            'create' => Pages\CreateMenuitems::route('/create'),
            'edit' => Pages\EditMenuitems::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return in_array($user->role, ['admin', 'manager']);
    }
}
