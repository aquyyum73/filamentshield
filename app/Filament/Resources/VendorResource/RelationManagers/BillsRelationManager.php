<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use App\Models\BillItem;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Split;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class BillsRelationManager extends RelationManager
{
    protected static string $relationship = 'bills';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('bill_date')
                    ->label('Bill Date')
                    ->date(),
                TextColumn::make('number')
                    ->label('Bill #')
                    ->searchable(),
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
                TextColumn::make('paid_price')
                    ->label('Paid Amount')
                    ->money('Rs. ')
                    ->default(0)
                    ->badge()
                    ->sortable(),
                TextColumn::make('balance_price')
                    ->label('Balance Amount')
                    ->money('Rs. ')
                    ->default(0)
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                //  Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                //  Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //     Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
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
                                        ->columns(1)
                                        ->schema([
                                            TextEntry::make('bill_date')
                                                ->label('Bill Date')
                                                ->color('primary')
                                                ->icon('heroicon-o-calendar-days')
                                                ->iconColor('primary')
                                                ->date(),
                                            TextEntry::make('number')
                                                ->label('Bill Number')
                                                ->color('primary')
                                                ->icon('heroicon-o-banknotes')
                                                ->iconColor('primary'),
                                            TextEntry::make('vendor.name')
                                                ->label('Vendor Name')
                                                ->color('primary')
                                                ->icon('heroicon-o-briefcase')
                                                ->iconColor('primary'),
                                            TextEntry::make('status')
                                                ->label('Bill Status'),
                                        ])->grow(),
                                    Section::make('Creation Information')
                                        ->description('Bill Creation Dates')
                                        ->columns(2)
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
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('qty'),
                                TextEntry::make('price')
                                    ->money('Rs. ',0),
                                TextEntry::make('total_price')
                                    ->state(function (BillItem $record): float {
                                        return $record->qty * $record->price;
                                    })
                                    ->money('Rs. ', 0)
                                    ->weight(FontWeight::Bold),
                            ])->columns(4)
                                ]),
                Section::make('Financial Details')
                    ->description('Calculations as per Vendor Bill')
                    ->schema([
                                TextEntry::make('total_price')
                                    ->label('Total Price')
                                    ->money('Rs. ',0),
                                TextEntry::make('bill_discount')
                                    ->label('Bill Discount')
                                    ->money('Rs. ',0),
                                TextEntry::make('final_price')
                                    ->label('Final Price')
                                    ->money('Rs. ',0)
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
}
