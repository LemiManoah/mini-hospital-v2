<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DoctorSchedule;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateDoctorSchedule
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): DoctorSchedule
    {
        return DB::transaction(fn (): DoctorSchedule => DoctorSchedule::query()->create([
            ...$attributes,
            'facility_branch_id' => $attributes['facility_branch_id'] ?? BranchContext::getActiveBranchId(),
            'created_by' => Auth::id(),
        ]));
    }
}
