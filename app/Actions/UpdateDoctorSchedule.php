<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DoctorSchedule;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateDoctorSchedule
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(DoctorSchedule $schedule, array $attributes): DoctorSchedule
    {
        return DB::transaction(function () use ($schedule, $attributes): DoctorSchedule {
            $schedule->update([
                ...$attributes,
                'facility_branch_id' => $attributes['facility_branch_id'] ?? $schedule->facility_branch_id ?? BranchContext::getActiveBranchId(),
                'updated_by' => Auth::id(),
            ]);

            return $schedule->refresh();
        });
    }
}
