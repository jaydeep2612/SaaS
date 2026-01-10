<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Order;
use App\Models\RestaurantTable; // Rename to avoid conflict with Filament Table
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class PaymentResource extends Resource
{
    protected static ?string $model = Order::class;
    
    // 🏷️ Navigation Settings
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Cashier / Billing';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    // 🚫 Disable Create/Edit (Cashiers only process payments)
    public static function canCreate(): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // 🔄 Auto-refresh to see new finished orders
            ->poll('10s')

            // 🔍 FILTER: Show only Unpaid orders that are ready/served
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->where('restaurant_id', auth()->user()->restaurant_id)
                    //->where('payment_status', 'unpaid')
                    // Show orders that are at least 'ready' (or 'served' if you have that status)
                    ->whereIn('status', ['ready', 'served']) 
                    ->orderBy('created_at', 'desc');
            })

            ->columns([
                Tables\Columns\TextColumn::make('table.table_number')
                    ->label('Table')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD') // Change currency if needed
                    ->weight('bold')
                    ->color('success')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'warning',
                        'served' => 'info',
                        default => 'gray',
                    }),
            ])
            ->actions([
                // 💵 THE PAYMENT ACTION BUTTON
                Tables\Actions\Action::make('collect_payment')
                    ->label('Collect Payment')
                    ->icon('heroicon-m-currency-dollar')
                    ->color('success')
                    ->button()
                    
                    // 1. The Popup Form (Modal)
                    ->form([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Amount Due')
                            ->default(fn (Order $record) => $record->total_amount)
                            ->disabled() // Read-only
                            ->prefix('$'),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Credit/Debit Card',
                                'upi'  => 'UPI / QR Code',
                            ])
                            ->required()
                            ->default('cash'),
                    ])

                    // 2. What happens when they click "Submit"
                    ->action(function (Order $record, array $data) {
                        // A. Update Order as Paid
                        $record->update([
                            'status' => 'completed',
                            //'payment_status' => 'paid',
                            'payment_method' => $data['payment_method'],
                        ]);

                        // B. Free up the Table
                        if ($record->table_id) {
                            $table = RestaurantTable::find($record->table_id);
                            if ($table) {
                                $table->update(['status' => 'available']);
                            }
                        }

                        // C. Send Success Notification
                        Notification::make()
                            ->title('Payment Received')
                            ->body("Table {$record->table->table_number} is now free.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}