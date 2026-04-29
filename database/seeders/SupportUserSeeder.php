<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffType;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

final class SupportUserSeeder extends Seeder
{
    public const string SUPPORT_EMAIL = 'support@mini-hospital.com';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrNew([
            'email' => self::SUPPORT_EMAIL,
        ]);

        $supportStaff = $this->preferredSupportStaff($this->seedTenantSupportStaff());

        $user->forceFill([
            'tenant_id' => $supportStaff?->tenant_id,
            'staff_id' => $supportStaff?->id,
            'password' => $user->exists ? $user->password : Hash::make('password'),
            'is_support' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles(['super_admin']);
    }

    /**
     * @return Collection<int, Staff>
     */
    private function seedTenantSupportStaff(): Collection
    {
        return Tenant::query()
            ->with([
                'branches' => static function (mixed $query): void {
                    if (! $query instanceof HasMany) {
                        return;
                    }

                    $query->orderByDesc('is_main_branch')->orderBy('name');
                },
                'departments' => static function (mixed $query): void {
                    if (! $query instanceof HasMany) {
                        return;
                    }

                    $query->orderBy('department_name');
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Tenant $tenant): ?Staff {
                $branches = $tenant->branches;
                $primaryBranch = $branches->first();

                if ($primaryBranch === null) {
                    return null;
                }

                $position = StaffPosition::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('name', 'System Administrator')
                    ->first()
                    ?? StaffPosition::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('name', 'IT Support Officer')
                        ->first();

                $department = $tenant->departments
                    ->firstWhere('department_name', 'Administration')
                    ?? $tenant->departments->first();

                $staff = Staff::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'email' => $this->tenantSupportEmail($tenant),
                    ],
                    [
                        'employee_number' => $this->tenantSupportEmployeeNumber($tenant),
                        'first_name' => 'Support',
                        'last_name' => 'User',
                        'phone' => $primaryBranch->main_contact,
                        'staff_position_id' => $position?->id,
                        'type' => StaffType::TECHNICAL->value,
                        'specialty' => 'Platform Support',
                        'hire_date' => now()->toDateString(),
                        'is_active' => true,
                    ],
                );

                $staff->branches()->sync(
                    $branches->mapWithKeys(fn (FacilityBranch $branch): array => [
                        $branch->id => ['is_primary_location' => $branch->id === $primaryBranch->id],
                    ])->all(),
                );

                if ($department instanceof Department) {
                    $staff->departments()->syncWithoutDetaching([$department->id]);
                }

                return $staff;
            })
            ->filter(fn (?Staff $staff): bool => $staff instanceof Staff)
            ->values();
    }

    /**
     * @param  Collection<int, Staff>  $supportStaff
     */
    private function preferredSupportStaff(Collection $supportStaff): ?Staff
    {
        $preferred = $supportStaff->first(function (Staff $staff): bool {
            $tenant = Tenant::query()
                ->select('id', 'domain')
                ->find($staff->tenant_id);

            return $tenant instanceof Tenant && $tenant->domain === 'qroo';
        });

        return $preferred instanceof Staff ? $preferred : $supportStaff->first();
    }

    private function tenantSupportEmail(Tenant $tenant): string
    {
        return sprintf('support+%s@mini-hospital.com', $tenant->domain);
    }

    private function tenantSupportEmployeeNumber(Tenant $tenant): string
    {
        return 'SUP-'.mb_strtoupper($tenant->domain ?? $tenant->id);
    }
}
