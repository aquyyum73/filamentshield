<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Bill;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Form;
use App\Enums\BillStatus;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Resources\BillResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BillResource\RelationManagers;

class BillResource extends Resource
{

    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $recordTitleAttribute = 'number';
    protected static ?string $navigationGroup = 'Vendors';
    protected static ?string $navigationParentItem = 'Vendors';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Bill Details')
                        ->schema([
                            Section::make('Bill Information')
                                ->schema([
                                    TextInput::make('number')
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
                                        ]),
                                    DatePicker::make('bill_date')
                                        ->native(false)
                                        ->displayFormat('d-M-Y')
                                        ->closeOnDateSelection(),
                                    Forms\Components\Select::make('status')
                                        ->options(BillStatus::toSelectArray())
                                        ->required()
                                        ->native(false),
                                ])->columns(3),
                            Section::make('Special Notes')
                                ->schema([
                                    MarkdownEditor::make('notes')
                                        ->label('Notes')
                                        ->columnSpan('full'),  // This makes the 'notes' field span the full width
                                ]),
                            Section::make('Financial Information')
                                ->schema([
                                    // Display field for total_price
                                    Forms\Components\TextInput::make('total_price')
                                        ->disabled()
                                        ->live(onBlur: true)
                                        ->default(fn ($record) => $record ? $record->total_price : 0)
                                        ->prefix('Rs. ')
                                        ->dehydrated(true)
                                        ->extraAttributes(['id' => 'total_price']),
                                    
                                        Forms\Components\TextInput::make('bill_discount')
                                        ->default(fn ($record) => $record ? $record->bill_discount : 0)
                                        ->prefix('Rs. ')
                                        ->live(onBlur: true)
                                        ->extraAttributes(['id' => 'bill_discount']),

                                    Forms\Components\TextInput::make('final_price')
                                        ->disabled()
                                        ->live(onBlur: true)
                                        ->default(fn ($record) => $record ? $record->final_price : 0)
                                        ->prefix('Rs. ')
                                        ->inputMode('decimal')
                                        ->dehydrated(true)
                                        ->extraAttributes(['id' => 'final_price']),
                                ])->columns(3),
                        ]),
                        
                    Wizard\Step::make('Items Details')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Select::make('item_id')
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
                                    TextInput::make('qty')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->columnSpan([
                                            'md' => 2,
                                        ])
                                        ->required(),
                                    Forms\Components\TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan([
                                            'md' => 3,
                                        ]),
                                ]),
                        ]),
                ])->columnSpan('full')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
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
