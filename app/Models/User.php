<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Filament\Models\Contracts\HasAvatar;

class User extends Authenticatable implements FilamentUser, HasMedia, HasAvatar
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPanelShield, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        $user = Auth::user();
        return $user->roles->isNotEmpty();
    }

    public function getRoleNameAttribute() : String
    {
        return $this->roles->pluck('name')->join(',');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        // Replace 'avatar' with the actual media collection name for avatars
        $media = $this->getFirstMedia('users');

        return $media ? $media->getUrl() : null;
    }
    
}
