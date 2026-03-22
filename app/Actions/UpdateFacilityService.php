<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateFacilityService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(FacilityService $service, array $attributes): FacilityService
    {
        return DB::transaction(function () use ($service, $attributes): FacilityService {
            $service->update([
                ...$attributes,
                'charge_master_id' => ($attributes['is_billable'] ?? $service->is_billable) ? $service->id : null,
                'updated_by' => Auth::id(),
            ]);

            return $service->refresh();
        });
    }
}
