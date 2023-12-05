<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Item;
use App\Models\Vendor;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ItemResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ItemResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Forms\Components\MarkdownEditor;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $recordRouteKeyName = 'slug';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Vendors';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Item Name')
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
                    ->unique(Item::class, 'slug', ignoreRecord: true),
                Forms\Components\Select::make('vendor_id')
                    ->label('Vendor Name')
                    ->relationship('vendors', 'name')
                    ->multiple()
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
                Forms\Components\TextInput::make('uom')
                    ->label('Unit of Measurement'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('Rs.')
                    ->default(0),
                Forms\Components\TextInput::make('qty')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('reorderlevel')
                    ->label('Re-Order Level')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('qtytoorder')
                    ->label('Quantity to Order')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendors.name')
                    ->label('Vendor Name')
                    ->badge(),
                Tables\Columns\TextColumn::make('uom')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record:slug}'),
            'edit' => Pages\EditItem::route('/{record:slug}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
