<?php

namespace App\Models;

use Carbon\Carbon;
use App\Enums\BillStatus;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bill extends Model
{
    use SoftDeletes, HasApiTokens, HasFactory, Notifiable, HasRoles, HasPanelShield;

    /**
     * @var string
     */
    protected $table = 'bills';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'total_price',
        'bill_date',
        'due_date',
        'total_price',
        'bill_discount',
        'final_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => BillStatus::class,
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class, 'bill_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }


    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function totalBillAmount(): float
    {
        return $this->bills->sum('final_price');
    }

    public function getTotalPriceAttribute()
    {
        return $this->billItems->sum(fn($item) => $item->qty * $item->price);
    }

    public function setBillDiscountAttribute($value)
    {
        if (str_ends_with($value, '%')) {
            $percentage = rtrim($value, '%');
            $discountAmount = ($this->total_price * $percentage) / 100;
            $this->attributes['bill_discount'] = $discountAmount;
        } else {
            $this->attributes['bill_discount'] = $value;
        }
    }

    public function getFinalPriceAttribute()
    {
        return $this->total_price - $this->bill_discount;
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (str_ends_with($model->bill_discount, '%')) {
                $percentage = rtrim($model->bill_discount, '%');
                $model->bill_discount = ($model->total_price * $percentage) / 100;
            }
        });

        static::saving(function ($model) {
            // Assuming total_price and bill_discount are already set
            $model->final_price = $model->total_price - $model->bill_discount;
        });
    }
}
