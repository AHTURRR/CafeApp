<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'status', 'last_login_at'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Email selalu disimpan huruf kecil (selaras dengan unique index lower(email)). */
    protected function email(): Attribute
    {
        return Attribute::make(set: static fn (string $value) => mb_strtolower(trim($value)));
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
