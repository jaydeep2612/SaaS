<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriesResource\Pages;
use App\Filament\Resources\CategoriesResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!

class CategoriesResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. Name
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),

                Forms\Components\Hidden::make('restaurant_id')
                ->default(Auth::user()->restaurant_id),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Category ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Category Name')->sortable()->searchable(),
                Tables\Columns\BooleanColumn::make('is_active')->label('Active')->sortable(),
                Tables\Columns\TextColumn::make('restaurant.name')->label('Restaurant')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime()->sortable(),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategories::route('/create'),
            'edit' => Pages\EditCategories::route('/{record}/edit'),
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
