<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPanelShield;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'uom',
        'price',
        'qty',
        'qtytoorder',
        'reorderlevel',
        'is_active',
    ];

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }

    

    // public function getVendorNameAttribute() : String
    // {
    //     return $this->vendors->pluck('name')->join(',');
    // }
}
