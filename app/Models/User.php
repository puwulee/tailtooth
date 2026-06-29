<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'line_user_id', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
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

    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    /** @param string|\App\Enums\UserRole ...$roles */
    public function hasAnyRole(...$roles): bool
    {
        $wanted = array_map(fn ($r) => $r instanceof \App\Enums\UserRole ? $r->value : $r, $roles);

        return $this->roles->contains(fn (UserRole $r) => in_array($r->role->value, $wanted, true));
    }

    public function assignRole(\App\Enums\UserRole|string $role): UserRole
    {
        $value = $role instanceof \App\Enums\UserRole ? $role->value : $role;

        return $this->roles()->firstOrCreate(['role' => $value]);
    }
}
