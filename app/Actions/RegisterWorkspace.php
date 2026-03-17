<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SensitiveParameter;

final readonly class RegisterWorkspace
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array{tenant: Tenant, staff: Staff, user: User}
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): array
    {
        return DB::transaction(function () use ($attributes, $password): array {
            $tenant = Tenant::query()->create([
                'name' => $attributes['workspace_name'],
                'domain' => $attributes['domain'] ?: null,
                'has_branches' => false,
                'subscription_package_id' => $attributes['subscription_package_id'],
                'status' => GeneralStatus::PENDING,
                'facility_level' => $attributes['facility_level'],
                'country_id' => $attributes['country_id'] ?: null,
                'onboarding_completed_at' => null,
            ]);

            [$firstName, $lastName] = $this->splitName($attributes['owner_name']);

            $staff = Staff::query()->create([
                'tenant_id' => $tenant->id,
                'employee_number' => sprintf('OWN-%s', Str::upper(Str::random(8))),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $attributes['email'],
                'type' => StaffType::ADMINISTRATIVE,
                'hire_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'staff_id' => $staff->id,
                'email' => $attributes['email'],
                'password' => $password,
            ]);

            $tenant->forceFill([
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->save();

            $staff->forceFill([
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->save();

            $adminRole = Role::query()->where('name', 'admin')->first();
            if ($adminRole instanceof Role) {
                $user->assignRole($adminRole);
            }

            event(new Registered($user));

            return [
                'tenant' => $tenant,
                'staff' => $staff,
                'user' => $user,
            ];
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', mb_trim($name)) ?: [];
        $firstName = $parts[0] ?? 'Owner';
        $lastName = count($parts) > 1
            ? implode(' ', array_slice($parts, 1))
            : 'Admin';

        return [$firstName, $lastName];
    }
}
