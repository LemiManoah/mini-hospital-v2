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
            $branchIds = $this->normalizeIds($data['branch_ids'] ?? []);
            $departmentIds = $this->normalizeIds($data['department_ids'] ?? []);
            $primaryBranchId = $this->nullableString($data['primary_branch_id'] ?? null);
            unset($data['branch_ids'], $data['department_ids'], $data['primary_branch_id']);

            $staff->update($data);

            if ($branchIds !== []) {
                $pivotData = [];
                foreach ($branchIds as $branchId) {
                    $pivotData[$branchId] = [
                        'is_primary_location' => $branchId === $primaryBranchId,
                    ];
                }

                $staff->branches()->sync($pivotData);
            }

            if ($departmentIds !== []) {
                $staff->departments()->sync($departmentIds);
            }
        });

        return $staff;
    }

    /**
     * @return list<string>
     */
    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $id) {
            if (! is_string($id)) {
                continue;
            }

            if ($id === '') {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
