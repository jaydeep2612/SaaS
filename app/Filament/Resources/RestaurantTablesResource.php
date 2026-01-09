<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantTablesResource\Pages;
use App\Models\RestaurantTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; 
use SimpleSoftwareIO\QrCode\Facades\QrCode; // 👈 Import QR Library
use Illuminate\Support\Facades\Storage;     // 👈 Import Storage
use Filament\Notifications\Notification;    // 👈 Import Notifications
use Illuminate\Support\HtmlString;          // 👈 Import HtmlString

class RestaurantTablesResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Restaurant Selection (Super Admin only)
                Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->visible(fn () => auth()->user()->role === 'super_admin')
                    ->required(fn () => auth()->user()->role === 'super_admin')
                    ->default(fn () => auth()->user()->restaurant_id),

                // 👇 CHANGED: We ask for "How many tables?", not "Table Number"
                Forms\Components\TextInput::make('total_tables')
                    ->label('Number of Tables to Add')
                    ->helperText('Example: Enter "5" to generate the next 5 tables automatically.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(1)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('table_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('id')
                    ->label('Table ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('restaurant.name')
                    ->visible(fn () => auth()->user()->role === 'super_admin')
                    ->label('Restaurant'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // 👇 ACTION 1: View QR Code (Popup)
                Tables\Actions\Action::make('view_qr')
                    ->label('View QR')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading(fn ($record) => "Table {$record->table_number} QR Code")
                    ->modalContent(function ($record) {
                        $frontendUrl = env('FRONTEND_URL', 'http://127.0.0.1:3000');
                        $scanUrl = "{$frontendUrl}/menu/{$record->restaurant_id}/{$record->id}";
                        
                        $qrCode = QrCode::size(200)->generate($scanUrl);

                        return new HtmlString("
                            <div class='flex flex-col items-center justify-center p-4'>
                                <div class='border-2 border-gray-800 p-2 rounded'>{$qrCode}</div>
                                <p class='mt-2 text-sm text-gray-500'>{$scanUrl}</p>
                            </div>
                        ");
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Close')),

                // 👇 ACTION 2: Save QR Code to Server Folder
                Tables\Actions\Action::make('save_qr')
                    ->label('Save to Server')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        // 1. Generate URL & Content
                        $frontendUrl = env('FRONTEND_URL', 'http://127.0.0.1:3000');
                        $scanUrl = "{$frontendUrl}/menu/{$record->restaurant_id}/{$record->id}";
                        $qrContent = QrCode::format('svg')->size(300)->margin(2)->generate($scanUrl);

                        // 2. Define Path: public/storage/restaurant_{id}/Tables-QR/table_{number}.svg
                        $path = "restaurant_{$record->restaurant_id}/Tables-QR/table_{$record->table_number}.svg";

                        // 3. Save File
                        Storage::disk('public')->put($path, $qrContent);

                        // 4. Notify User
                        Notification::make()
                            ->title('QR Saved Successfully')
                            ->body("Saved to: storage/{$path}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
        return in_array($user->role, ['super_admin', 'admin', 'manager']);
    }

    // Security & Filtering Logic
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) return $query->whereRaw('1 = 0');
        if ($user->role === 'super_admin') return $query;

        return $query->where('restaurant_id', $user->restaurant_id);
    }
}