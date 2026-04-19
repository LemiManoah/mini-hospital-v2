<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StaffType;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

final class CreateStaff
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Staff
    {
        // Generate employee number if not provided
        if (empty($data['employee_number'])) {
            $data['employee_number'] = $this->generateEmployeeNumber(is_string($data['type'] ?? null) ? $data['type'] : '');
        }

        return DB::transaction(function () use ($data): Staff {
            $branchIds = $data['branch_ids'] ?? [];
            $departmentIds = $data['department_ids'] ?? [];
            $primaryBranchId = $data['primary_branch_id'] ?? null;
            unset($data['branch_ids'], $data['department_ids'], $data['primary_branch_id']);

            $staff = Staff::query()->create($data);

            if (is_array($branchIds) && $branchIds !== []) {
                $pivotData = [];
                foreach ($branchIds as $branchId) {
                    /** @var string $branchId */
                    $pivotData[$branchId] = [
                        'is_primary_location' => $branchId === $primaryBranchId,
                    ];
                }

                $staff->branches()->sync($pivotData);
            }

            if (is_array($departmentIds) && $departmentIds !== []) {
                $staff->departments()->sync($departmentIds);
            }

            return $staff;
        });
    }

    /**
     * Generate a unique employee number based on staff type.
     */
    private function generateEmployeeNumber(string $staffType): string
    {
        $prefix = match ($staffType) {
            StaffType::MEDICAL->value => 'MED',
            StaffType::NURSING->value => 'NUR',
            StaffType::ALLIED_HEALTH->value => 'AHL',
            StaffType::ADMINISTRATIVE->value => 'ADM',
            StaffType::SUPPORT->value => 'SUP',
            StaffType::TECHNICAL->value => 'TEC',
            default => 'STA',
        };

        // Get the next sequential number for this prefix
        $lastEmployee = Staff::query()->where('employee_number', 'like', $prefix.'-%')
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extract the number part and increment
            $lastNumber = (int) mb_substr((string) $lastEmployee->employee_number, mb_strlen($prefix) + 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
    }
}
