<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

final class BootstrapOnboardingDepartments
{
    /**
     * @param  array<int, array{name: string, location?: string|null, is_clinical?: bool}>  $departments
     */
    public function handle(Tenant $tenant, User $user, array $departments): void
    {
        foreach ($departments as $index => $departmentData) {
            $name = mb_trim($departmentData['name']);

            Department::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'department_name' => $name,
                ],
                [
                    'department_code' => $this->departmentCode($name, $index),
                    'location' => $departmentData['location'] ?? null,
                    'is_clinical' => (bool) ($departmentData['is_clinical'] ?? true),
                    'is_active' => true,
                    'contact_info' => null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ],
            );
        }

        $tenant->update([
            'updated_by' => $user->id,
            'onboarding_current_step' => 'staff',
        ]);
    }

    private function departmentCode(string $name, int $index): string
    {
        $letters = preg_replace('/[^A-Z]/', '', Str::upper($name)) ?: 'DEPT';

        return Str::substr($letters, 0, 6).sprintf('%02d', $index + 1);
    }
}
