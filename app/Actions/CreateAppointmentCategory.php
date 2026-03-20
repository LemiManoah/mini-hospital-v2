<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppointmentCategory;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateAppointmentCategory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): AppointmentCategory
    {
        return DB::transaction(fn (): AppointmentCategory => AppointmentCategory::query()->create([
            ...$attributes,
            'facility_branch_id' => $attributes['facility_branch_id'] ?? BranchContext::getActiveBranchId(),
            'created_by' => Auth::id(),
        ]));
    }
}
