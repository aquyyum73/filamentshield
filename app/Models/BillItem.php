<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BillItem extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'bill_items';

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
    // public function items(): HasMany
    // {
    //     return $this->hasMany(BillItem::class, 'bill_id');
    // }
}
