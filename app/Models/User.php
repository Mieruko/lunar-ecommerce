<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable(['name', 'email', 'phone', 'password', 'status', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany { return $this->hasMany(Address::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function carts(): HasMany { return $this->hasMany(Cart::class); }
    public function wishlists(): HasMany { return $this->hasMany(Wishlist::class); }
    public function customerProfile(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(CustomerProfile::class); }

    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class, 'user_roles'); }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * Support both the legacy SQL role (`admin`) and the newer RBAC role
     * (`super-admin`). Either one is treated as a full administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->roles()
            ->whereIn('slug', ['admin', 'super-admin'])
            ->exists();
    }

    public function isStaff(): bool
    {
        return $this->isAdministrator()
            || $this->roles()->where('is_staff', true)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin'
            || $this->status !== 'active'
            || $this->email_verified_at === null) {
            return false;
        }

        // Legacy administrators can enter immediately. New staff roles are
        // granted access explicitly through the `admin.access` permission.
        if ($this->isAdministrator()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', 'admin.access'))
            ->exists();
    }
}
