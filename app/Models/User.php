<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ROOT_SUPER_ADMIN_EMAIL = 'super_admin_!@minhaj.com';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasPermission(string $permissionName): bool
    {
        $directRolePermission = $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permissionName))
            ->exists();

        if ($directRolePermission) {
            return true;
        }

        return $this->groups()
            ->whereHas('roles.permissions', fn ($query) => $query->where('name', $permissionName))
            ->exists();
    }

    public function hasRule(string $resource, string $action): bool
    {
        return $this->hasPermission($resource . '_' . $action);
    }

    public function isRootSuperAdmin(): bool
    {
        return strcasecmp($this->email, self::ROOT_SUPER_ADMIN_EMAIL) === 0;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isRootSuperAdmin() || $this->roles()->where('slug', 'super_admin')->exists();
    }
}
