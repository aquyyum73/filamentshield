<?php

namespace App\Filament\Resources\PaymentTermResource\Pages;

use App\Filament\Resources\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentTerm extends ViewRecord
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
