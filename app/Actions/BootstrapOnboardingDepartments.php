<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Onboarding\CreateOnboardingDepartmentsDTO;
use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

final class BootstrapOnboardingDepartments
{
    public function handle(Tenant $tenant, User $user, CreateOnboardingDepartmentsDTO $data): void
    {
        foreach ($data->departments as $index => $department) {
            Department::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'department_name' => $department->name,
                ],
                [
                    'department_code' => $this->departmentCode($department->name, $index),
                    'location' => $department->location,
                    'is_clinical' => $department->isClinical,
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
