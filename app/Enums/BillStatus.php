<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BillStatus: string implements HasColor, HasLabel
{
    case New = 'new';

    case Pending = 'pending';

    case Partial_Paid = 'partial_paid';

    case Fully_Paid = 'fully_paid';

    public function getLabel(): string {
        return match ($this) {
            self::New => 'New',
            self::Pending => 'Pending',
            // Replace hyphens with spaces
            self::Partial_Paid => 'Partial Paid',
            self::Fully_Paid => 'Fully Paid',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::New => 'gray',
            self::Pending => 'danger',
            self::Partial_Paid => 'warning',
            self::Fully_Paid => 'success',
        };
    }

    public static function toSelectArray(): array {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->all();
    }
}