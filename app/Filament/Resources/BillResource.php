<?php

namespace App\Filament\Resources;

use layout;
use Filament\Forms;
use Filament\Forms\Set;
use App\Models\Bill;
use App\Models\Item;
use Filament\Tables;
use App\Models\Vendor;
use App\Models\BillItem;
use Filament\Forms\Form;
use App\Enums\BillStatus;
use Filament\Tables\Table;
use App\Models\PaymentTerm;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Resources\BillResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BillResource\RelationManagers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Debug\Dumper;

class BillResource extends Resource
{

    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $recordTitleAttribute = 'number';
    protected static ?string $navigationGroup = 'Vendors';
    protected static ?int $navigationSort = 2;
    //protected static ?string $navigationParentItem = 'Vendors';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::where('status', 'pending')->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bill Information')
                                ->schema([
                                    Forms\Components\TextInput::make('number')
                                        ->label('Bill Number')
                                        ->default(function () {
                                            return 'BILL-' . now()->format('M-Y') . '-' . random_int(100000, 999999);
                                        })
                                        ->disabled()
                                        ->dehydrated()
                                        ->required()
                                        ->maxLength(32)
                                        ->unique(Bill::class, 'number', ignoreRecord: true),
                                    Forms\Components\Select::make('vendor_id')
                                        ->label('Vendor Name')
                                        ->relationship('vendor', 'name')
                                        ->searchable()
                                        ->preload(true)
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->maxLength(191)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                                    $set('slug', Str::slug($state));
                                                }),
                                            Forms\Components\TextInput::make('slug')
                                                ->disabled()
                                                ->dehydrated()
                                                ->required()
                                                ->maxLength(191)
                                                ->unique(Vendor::class, 'slug', ignoreRecord: true),
                                            Forms\Components\TextInput::make('personel')
                                                ->label('Contact Person')
                                                ->required()
                                                ->maxLength(191),
                                            Forms\Components\TextInput::make('email')
                                                ->email()
                                                ->required()
                                                ->maxLength(191),
                                            Forms\Components\TextInput::make('mobile')
                                                ->required()
                                                ->maxLength(191),
                                            Forms\Components\TextInput::make('phone')
                                                ->tel()
                                                ->required()
                                                ->maxLength(191),
                                            Forms\Components\TextInput::make('address')
                                                ->required()
                                                ->maxLength(191),
                                            Forms\Components\Toggle::make('is_active')
                                                ->required(),
                                            Forms\Components\MarkdownEditor::make('notes')
                                                ->required()
                                                ->maxLength(16777215)
                                                ->columnSpanFull(),
                                            ])
                                            ->live()
                                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                                if ($state) {
                                                    $vendor = Vendor::find($state);
                                                    if ($vendor) {
                                                        $paymentTerm = $vendor->payment_terms;
                                                        $set('payment_terms_name', optional($paymentTerm)->name);
                                        
                                                        // Trigger calculation for due_date based on current bill_date
                                                        $billDate = request()->input('bill_date');
                                                        $set('due_date', $this->calculateDueDate(optional($paymentTerm)->name, $billDate));
                                                    } else {
                                                        $set('payment_terms_name', null);
                                                        $set('due_date', null);
                                                    }
                                                }
                                            }),
                                        Forms\Components\TextInput::make('payment_terms_name')
                                            ->label('Payment Terms')
                                            ->disabled(),
                                            Forms\Components\DatePicker::make('bill_date')
                                            ->native(false)
                                            ->displayFormat('d-M-Y')
                                            ->closeOnDateSelection()
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, Forms\Set $set) => [
                                                'due_date' => $this->calculateDueDate(request()->input('payment_terms_name'), $state)
                                            ]),
                                        Forms\Components\TextInput::make('due_date')
                                            ->disabled(),
                                    Forms\Components\Select::make('status')
                                        ->options(BillStatus::toSelectArray())
                                        ->required()
                                        ->native(false),
                                ])->columns(3),
                            Forms\Components\Section::make('Special Notes')
                                ->schema([
                                    MarkdownEditor::make('notes')
                                        ->label('Notes')
                                        ->columnSpan('full'),  // This makes the 'notes' field span the full width
                                ]),
                                Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('item_id')
                                        ->label('Item Name')
                                        ->options(Item::query()->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload(true)
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('price', Item::find($state)?->price ?? 0))
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan([
                                            'md' => 5,
                                        ]),
                                    Forms\Components\TextInput::make('qty')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->columnSpan([
                                            'md' => 2,
                                        ])
                                        ->required()
                                        ->live(),
                                    Forms\Components\TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan([
                                            'md' => 3,
                                        ])
                                        ->live(),
                                        Forms\Components\TextInput::make('item_total_price')
                                        ->label('Item Total Price')
                                        ->numeric()
                                        ->live(),
                                    ]),
                            Forms\Components\Section::make('Financial Information')
                                ->schema([
                                    // Display field for total_price
                                    Forms\Components\TextInput::make('total_price')
                                        ->disabled()
                                        ->live()
                                        ->default(fn ($record) => $record ? $record->total_price : 0)
                                        ->prefix('Rs. ')
                                        ->dehydrated(true)
                                        ->extraAttributes(['id' => 'total_price']),
                                    
                                    Forms\Components\TextInput::make('bill_discount')
                                        ->default(fn ($record) => $record ? $record->bill_discount : 0)
                                        ->prefix('Rs. ')
                                        ->live()
                                        ->extraAttributes(['id' => 'bill_discount']),

                                    Forms\Components\TextInput::make('final_price')
                                        ->disabled()
                                        ->live()
                                        ->default(fn ($record) => $record ? $record->final_price : 0)
                                        ->prefix('Rs. ')
                                        ->inputMode('decimal')
                                        ->dehydrated(true)
                                        ->extraAttributes(['id' => 'final_price']),
                                ])->columns(3),
                                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bill_date')
                    ->label('Bill Date')
                    ->date(),
                TextColumn::make('number')
                    ->label('Bill #')
                    ->searchable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bill_date')
                    ->label('Bill Date')
                    ->date(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Bill Amount')
                    ->money('Rs. ')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bill_discount')
                    ->label('Bill Discount')
                    ->money('Rs. ')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('final_price')
                    ->label('Final Bill Amount')
                    ->money('Rs. ')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_payments')
                    ->label('Total Payments')
                    ->badge()
                    ->default(function ($record) {
                        return 'Rs. ' . number_format(DB::table('payments')->where('bill_id', $record->id)->sum('paid_amount'));
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                    // Example of a text filter for 'Status'
                    SelectFilter::make('status')
                        ->label('Status Filter')
                        ->multiple()
                        ->options(collect(BillStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                    SelectFilter::make('vendors')
                        ->label('Vendor Filter')
                        ->relationship('vendor', 'name')
                        ->searchable()
                        ->preload(),
                    Filter::make('bill_date_from')
                        ->form([
                            DatePicker::make('bill_date_from')
                                ->label('Bill Date From')
                                ->native(false)
                                ->closeOnDateSelection(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query->when(
                                $data['bill_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('bill_date', '>=', $date)
                            );
                        })
                        ->indicateUsing(function (array $data): array {
                            $indicators = [];
                            if ($data['bill_date_from'] ?? null) {
                                $indicators['bill_date_from'] = 'Bill Date From ' . Carbon::parse($data['bill_date_from'])->toFormattedDateString();
                            }
                            return $indicators;
                        }),
                    
                    // Filter for bill_date_until
                    Filter::make('bill_date_until')
                        ->form([
                            DatePicker::make('bill_date_until')
                                ->label('Bill Date Until')
                                ->native(false)
                                ->closeOnDateSelection(),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query->when(
                                $data['bill_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('bill_date', '<=', $date)
                            );
                        })
                        ->indicateUsing(function (array $data): array {
                            $indicators = [];
                            if ($data['bill_date_until'] ?? null) {
                                $indicators['bill_date_until'] = 'Bill Date Until ' . Carbon::parse($data['bill_date_until'])->toFormattedDateString();
                            }
                            return $indicators;
                        })
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
 
    public static function infolist(Infolist $infolist): Infolist
{

    return $infolist
        ->schema([
        // ...
        Grid::make([
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
            'xl' => 6,
            '2xl' => 8,
        ])
            ->schema([
                // ...
                Section::make([
                        Split::make([
                                    Section::make('Bill Information')
                                        ->description('All about Bills')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('bill_date')
                                                ->label('Bill Date')
                                                ->color('primary')
                                                ->icon('heroicon-o-calendar-days')
                                                ->iconColor('primary')
                                                ->size(TextEntry\TextEntrySize::Large)
                                                ->weight(FontWeight::Bold)
                                                ->date(),
                                            TextEntry::make('number')
                                                ->label('Bill Number')
                                                ->color('primary')
                                                ->icon('heroicon-o-banknotes')
                                                ->iconColor('primary')
                                                ->size(TextEntry\TextEntrySize::Large)
                                                ->weight(FontWeight::Bold),
                                            TextEntry::make('vendor.name')
                                                ->label('Vendor Name')
                                                ->color('primary')
                                                ->icon('heroicon-o-briefcase')
                                                ->iconColor('primary')
                                                ->size(TextEntry\TextEntrySize::Large)
                                                ->weight(FontWeight::Bold),
                                            TextEntry::make('status')
                                                ->label('Bill Status')
                                                ->size(TextEntry\TextEntrySize::Large)
                                                ->weight(FontWeight::Bold),
                                        ])->grow(),
                                    Section::make('Creation Information')
                                        ->description('Bill Creation Dates')
                                        ->columns(3)
                                        ->columnSpan(['lg' => 1])
                                        ->schema([
                                            TextEntry::make('created_at')
                                                ->dateTime(),
                                            TextEntry::make('updated_at')
                                                ->dateTime(),
                                        ]),
                    ])->from('md'),
                Section::make('Item Details')
                    ->description('List of Items as per Vendor Bill')
                    ->aside()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('item_id')
                                    ->label('Item Name')
                                    ->color('primary')
                                    ->state(function ($record) {
                                        $item = Item::find($record->item_id);
                                        return $item ? $item->name : '';
                                    })
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('qty'),
                                TextEntry::make('price')
                                    ->money('Rs. '),
                                TextEntry::make('total_price')
                                    ->state(function (BillItem $record): float {
                                        return $record->qty * $record->price;
                                    })
                                    ->money('Rs. ')
                                    ->weight(FontWeight::Bold),
                            ])->columns(4)
                                ]),
                Section::make('Financial Details')
                    ->description('Calculations as per Vendor Bill')
                    ->schema([
                                TextEntry::make('total_price')
                                    ->label('Total Price')
                                    ->money('Rs. '),
                                TextEntry::make('bill_discount')
                                    ->label('Bill Discoun')
                                    ->money('Rs. '),
                                TextEntry::make('final_price')
                                    ->label('Final Price')
                                    ->money('Rs. ')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                                TextEntry::make('paid_price')
                                    ->label('Paid Amount')
                                    ->money('Rs. '),
                                TextEntry::make('balance_price')
                                    ->label('Balance Amount')
                                    ->money('Rs. '),
                    ])->columns(5)
            ])
        ])
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
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'view' => Pages\ViewBill::route('/{record}'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }


    public static function serving(): void
    {
        Filament::registerScripts([
            'bill-resource-js' => <<<'JS'
                document.addEventListener('DOMContentLoaded', function() {
                    const formatCurrency = (value) => {
                        // Remove decimal places
                        return parseFloat(value).toFixed(0);
                    };
                    const discountInput = document.querySelector('#bill_discount');
                    const totalPriceInput = document.querySelector('#total_price');
                    const finalPriceInput = document.querySelector('#final_price');

                    if (discountInput && totalPriceInput && finalPriceInput) {
                        discountInput.addEventListener('change', function() {
                            let discountValue = discountInput.value.trim();
                            let totalPrice = parseFloat(totalPriceInput.value);
                            let finalPrice = totalPrice;

                            if (discountValue.endsWith('%')) {
                                let percentage = parseFloat(discountValue.slice(0, -1));
                                if (!isNaN(percentage)) {
                                    let discountAmount = (totalPrice * percentage) / 100;
                                    finalPrice = totalPrice - discountAmount;
                                }
                            } else {
                                let discountAmount = parseFloat(discountValue);
                                if (!isNaN(discountAmount)) {
                                    finalPrice = totalPrice - discountAmount;
                                }
                            }

                            finalPriceInput.value = finalPrice.toFixed(2); // Format as needed
                            discountInput.value = formatCurrency(discountInput.value); // Remove decimal places
                        });
                    }
                });
            JS,
        ]);
    }
}
