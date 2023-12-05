<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Bill;
use Filament\Tables;
use App\Models\Payment;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PaymentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $recordTitleAttribute = 'number';
    protected static ?string $navigationGroup = 'Vendors';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->live(),
                Forms\Components\Select::make('bill_id')
                ->label('Select a vendor first')
                // ->options(fn (Get $get): Collection => Bill::query()->where('vendor_id', $get('vendor_id'))->pluck('number','id'))
                ->options(function (Get $get): Collection {
                    $vendorId = $get('vendor_id');
                    
                    // Use where clause to filter bills based on vendor_id
                    $bills = Bill::query()->where('vendor_id', $vendorId)->get();

                    // Map bills to key-value pairs for the select options
                    $options = $bills->mapWithKeys(function ($bill) {
                        return [
                            $bill->id => sprintf(
                                '%s - Rs. %s - %s',
                                $bill->number,
                                number_format($bill->final_price, 2),
                                $bill->bill_date
                            ),
                        ];
                    });

                    return $options;
                })
                ->required(),
                Forms\Components\TextInput::make('number')
                ->label('Payment Number')
                ->default(function () {
                return 'PAYMENT-' . now()->format('M-Y') . '-' . random_int(100000, 999999);
                })
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(32)
                    ->unique(Bill::class, 'number', ignoreRecord: true),
                Forms\Components\DatePicker::make('payment_date')
                    ->native(false)
                    ->displayFormat('d-M-Y')
                    ->closeOnDateSelection(),
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('mode')
                    ->required()
                    ->maxLength(191),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getBillOptions($vendorId): array
{
    // Fetch bills based on $vendorId using your logic
    $bills = Bill::getBillsByVendor($vendorId);

    // Format bills as options for the select component
    $options = collect($bills)->mapWithKeys(function ($bill) {
        return [$bill->id => $bill->number];
    })->toArray();

    return $options;
}
}
