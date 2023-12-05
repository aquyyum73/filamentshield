<?php

namespace App\Filament\Resources\BillResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use App\Filament\Resources\BillResource;
use Filament\Resources\Pages\ListRecords;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make(),
            'New' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('status', 'new');
            }),
            'Pending' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('status', 'pending');
            }),
            'Partial Paid' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('status', 'partial_paid');
            }),
            'Fully Paid' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('status', 'fully_paid');
            }),
        ];
    }
}
