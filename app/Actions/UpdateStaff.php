<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

final class UpdateStaff
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Staff $staff, array $data): Staff
    {
        DB::transaction(function () use ($staff, $data): void {
            $branchIds = $data['branch_ids'] ?? [];
            $departmentIds = $data['department_ids'] ?? [];
            $primaryBranchId = $data['primary_branch_id'] ?? null;
            unset($data['branch_ids'], $data['department_ids'], $data['primary_branch_id']);

            $staff->update($data);

            if (is_array($branchIds) && $branchIds !== []) {
                $pivotData = [];
                foreach ($branchIds as $branchId) {
                    $pivotData[(string) $branchId] = [
                        'is_primary_location' => $branchId === $primaryBranchId,
                    ];
                }

                $staff->branches()->sync($pivotData);
            }

            if (is_array($departmentIds) && $departmentIds !== []) {
                $staff->departments()->sync($departmentIds);
            }
        });

        return $staff;
    }
}
