<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;

enum BillStatus: string implements HasColor, HasLabel, HasIcon
{
    case New = 'new';
    case Pending = 'pending';
    case Partial_Paid = 'partial_paid';
    case Fully_Paid = 'fully_paid';

    public function getLabel(): string {
        return match ($this) {
            self::New => 'New',
            self::Pending => 'Pending',
            self::Partial_Paid => 'Partial Paid',
            self::Fully_Paid => 'Fully Paid',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::New => 'primary',
            self::Pending => 'danger',
            self::Partial_Paid => 'warning',
            self::Fully_Paid => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::New => 'heroicon-o-document-plus',
            self::Pending => 'heroicon-o-document-arrow-down',
            self::Partial_Paid => 'heroicon-o-document-arrow-up',
            self::Fully_Paid => 'heroicon-o-face-smile',
        };
    }

    public static function toSelectArray(): array {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->all();
    }
}