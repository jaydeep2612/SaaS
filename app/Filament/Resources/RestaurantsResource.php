<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantsResource\Pages;
use App\Filament\Resources\RestaurantsResource\RelationManagers;
use App\Models\Restaurant;
use Filament\Forms;
use Illuminate\Support\Str;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!

class RestaurantsResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
                // 1. Restaurant Name
                 Forms\Components\TextInput::make('name')
                    ->label('Restaurant Name')
                    ->required()
                    ->live() // IMPORTANT for dynamic filename
                    ->reactive(),

                

                // 2. Email (Unique)
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                // 3. Logo Upload (Simple image uploader)
                Forms\Components\FileUpload::make('logo')
                    ->label('Restaurant Logo')
                    ->image()
                    ->disk('public')
                    ->directory(fn ($get) =>
                        'restaurants/' . Str::slug($get('name'))
                    )
                    ->getUploadedFileNameForStorageUsing(
                        fn ($file, $get) =>
                            Str::slug($get('name')) . '.' . $file->getClientOriginalExtension()
                    )
                    ->imageEditor()
                    ->required(),

                // 4. User Limit (Number only)
                Forms\Components\TextInput::make('user_limit')
                    ->numeric()
                    ->default(5)
                    ->required(),

                // 5. Active Status (Simple toggle switch)
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                // Show Logo in the list
                Tables\Columns\ImageColumn::make('logo')
                    ->circular(), // Makes it round

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_limit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Creator`s id')
                    ->searchable(),

                // Shows a checkmark or X icon
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurants::route('/create'),
            'edit' => Pages\EditRestaurants::route('/{record}/edit'),
        ];
    }
    protected static function booted()
    {
        static::creating(function ($restaurant) {
            // Automatically set the current user ID when creating
            if (Auth::check()) {
                $restaurant->created_by = Auth::id();
            }
        });
    }
    public static function canViewAny(): bool
    {
        // "Is the logged-in user a Super Admin?"
        // Yes -> Return true (Access Granted)
        // No  -> Return false (Access Denied & Menu Hidden)
        return auth()->user()->role === 'super_admin';
    }
}
