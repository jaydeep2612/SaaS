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
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Restaurant;

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

    // 1️⃣ Restaurant (Super Admin only)
    Forms\Components\Select::make('restaurant_id')
        ->relationship('restaurant', 'name')
        ->visible(fn () => auth()->user()->role === 'super_admin')
        ->required(fn () => auth()->user()->role === 'super_admin')
        ->live()
        ->reactive(),

    // 2️⃣ Category (Filtered by Restaurant)
    Forms\Components\Select::make('category_id')
        ->label('Category')
        ->relationship(
            'category',
            'name',
            fn (Builder $query, callable $get) =>
                auth()->user()->role === 'super_admin'
                    ? $query->where('restaurant_id', $get('restaurant_id'))
                    : $query->where('restaurant_id', auth()->user()->restaurant_id)
        )
        ->required()
        ->searchable()
        ->preload()
        ->live()
        ->reactive(),

    // 3️⃣ Item Name
    Forms\Components\TextInput::make('name')
        ->required()
        ->maxLength(255)
        ->live()
        ->reactive(),

    // 4️⃣ Price
    Forms\Components\TextInput::make('price')
        ->numeric()
        ->prefix('₹')
        ->required(),

    // 5️⃣ IMAGE UPLOAD (🔥 MAIN PART)
    Forms\Components\FileUpload::make('image')
        ->label('Item Image')
        ->image()
        ->disk('public')
        ->directory(function (callable $get) {

            $restaurant = Restaurant::find(
                $get('restaurant_id') ?? Auth::user()->restaurant_id
            );

            $category = Category::find($get('category_id'));

            if (! $restaurant || ! $category) {
                return 'temp';
            }

            return
                'restaurants/' .
                Str::slug($restaurant->name) .
                '/categories/' .
                Str::slug($category->name);
        })
        ->getUploadedFileNameForStorageUsing(
            fn ($file, callable $get) =>
                Str::slug($get('name')) . '.' . $file->getClientOriginalExtension()
        )
        ->required(),

    // 6️⃣ Availability
    Forms\Components\Toggle::make('is_available')
        ->label('Available Now')
        ->default(true),

    // 7️⃣ Hidden restaurant_id (for non-super-admin)
    Forms\Components\Hidden::make('restaurant_id')
        ->default(Auth::user()->restaurant_id)
        ->visible(fn () => auth()->user()->role !== 'super_admin'),
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
