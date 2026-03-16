<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Appointment;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateAppointment
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Appointment
    {
        return DB::transaction(fn (): Appointment => Appointment::query()->create([
            ...$attributes,
            'facility_branch_id' => $attributes['facility_branch_id'] ?? BranchContext::getActiveBranchId(),
            'created_by' => Auth::id(),
        ]));
    }
}
