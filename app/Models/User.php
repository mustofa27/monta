<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'email_verified_at',
    'sso_sub',
    'sso_user_type',
    'sso_employee_type',
    'sso_profile',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        if ($this->roles()->where('slug', $role)->exists()) {
            return true;
        }

        return in_array($role, $this->resolvedSsoRoleSlugs(), true);
    }

    public function hasAnyRole(array $roles): bool
    {
        if ($this->roles()->whereIn('slug', $roles)->exists()) {
            return true;
        }

        return array_intersect($roles, $this->resolvedSsoRoleSlugs()) !== [];
    }

    public function syncRolesBySlug(array $roles): void
    {
        $normalizedRoles = array_values(array_unique(array_map(
            fn (string $role): string => Str::of($role)->lower()->snake()->toString(),
            $roles
        )));

        foreach ($normalizedRoles as $slug) {
            Role::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => (string) Str::of($slug)->replace('_', ' ')->title()]
            );
        }

        $roleIds = Role::query()->whereIn('slug', $normalizedRoles)->pluck('id')->all();
        $this->roles()->sync($roleIds);
    }

    /**
     * @return array<int, string>
     */
    private function resolvedSsoRoleSlugs(): array
    {
        $profile = is_array($this->sso_profile) ? $this->sso_profile : [];
        $rawRoles = [];

        foreach (['role', 'roles', 'user_role', 'user_roles', 'role_name'] as $key) {
            $value = data_get($profile, $key);

            if (is_string($value) && trim($value) !== '') {
                $rawRoles[] = Str::of($value)->lower()->squish()->toString();
            }

            if (is_array($value)) {
                foreach ($value as $nestedRole) {
                    if (is_string($nestedRole) && trim($nestedRole) !== '') {
                        $rawRoles[] = Str::of($nestedRole)->lower()->squish()->toString();
                        continue;
                    }

                    if (is_array($nestedRole)) {
                        foreach (['name', 'slug'] as $roleField) {
                            $roleValue = data_get($nestedRole, $roleField);

                            if (is_string($roleValue) && trim($roleValue) !== '') {
                                $rawRoles[] = Str::of($roleValue)->lower()->squish()->toString();
                            }
                        }
                    }
                }
            }
        }

        $userType = Str::of((string) $this->sso_user_type)->lower()->squish()->toString();
        if ($userType !== '') {
            $rawRoles[] = $userType;
        }

        $roleMapping = config('sso.sso_role_mapping', []);
        $resolved = [];

        foreach (array_unique($rawRoles) as $rawRole) {
            if (! isset($roleMapping[$rawRole]) || ! is_array($roleMapping[$rawRole])) {
                $directSlug = Str::of($rawRole)->snake()->toString();

                if (Role::query()->where('slug', $directSlug)->exists()) {
                    $resolved[] = $directSlug;
                }

                continue;
            }

            foreach ($roleMapping[$rawRole] as $mappedRole) {
                if (! is_string($mappedRole) || trim($mappedRole) === '') {
                    continue;
                }

                $resolved[] = Str::of($mappedRole)->lower()->snake()->toString();
            }
        }

        return array_values(array_unique($resolved));
    }

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
            'sso_profile' => 'array',
        ];
    }
}
