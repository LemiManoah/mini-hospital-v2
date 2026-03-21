<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToTenant;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $appends = ['name', 'avatar'];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'tenant_id' => 'string',
            'staff_id' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'is_support' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Get the user's full name.
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->staffId() !== null && $this->staff) {
                return mb_trim(sprintf('%s %s', $this->staff->first_name, $this->staff->last_name));
            }

            return explode('@', $this->email)[0];
        });
    }

    /**
     * Get the user's avatar URL.
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(function (): string {
            $name = urlencode($this->name ?? $this->email);

            return sprintf('https://ui-avatars.com/api/?name=%s&color=7F9CF5&background=EBF4FF', $name);
        });
    }

    public function staffId(): ?string
    {
        $staffId = $this->getAttributes()['staff_id'] ?? null;

        return is_string($staffId) && $staffId !== '' ? $staffId : null;
    }

    public function tenantId(): ?string
    {
        $tenantId = $this->getAttributes()['tenant_id'] ?? null;

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    public function isSupportUser(): bool
    {
        return (bool) ($this->getAttributes()['is_support'] ?? false);
    }
}
