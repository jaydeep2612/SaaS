<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!


class UserResource extends Resource
{
    protected static ?string $model = User::class;

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
                // 1. Name
                Forms\Components\TextInput::make('name')
                    ->required(),

                // 2. Email
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),

                // 3. Role Dropdown
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'chef' => 'Chef',
                        'waiter' => 'Waiter',
                    ])
                    ->required(),

                // 4. Restaurant Selection
               Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload()
                    ->dehydrated()
                    ->visible(fn () => auth()->user()->role === 'super_admin')
                    ->default(fn () => auth()->user()->restaurant_id)
                    
                    ->required(fn () => auth()->user()->role === 'super_admin'),

                // 5. Password (Simple hashing)
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)) // Auto-hash password
                    ->required(fn ($operation) => $operation === 'create'),  // Required only when creating
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin', 'manager' => 'danger', // Red for high privelege
                        'chef' => 'warning',            // Orange/Yellow
                        'waiter' => 'success',          // Green
                        default => 'gray',
                    }),

                // Only show Restaurant column if Super Admin
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->sortable(),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
        // Get the logged-in user
        $user = auth()->user();

        // Check if their role is in the allowed list
        return in_array($user->role, ['super_admin', 'manager', 'admin']);
    }
}
