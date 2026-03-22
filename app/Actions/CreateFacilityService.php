<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateFacilityService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): FacilityService
    {
        return DB::transaction(function () use ($attributes): FacilityService {
            $service = FacilityService::query()->create([
                ...$attributes,
                'service_code' => $this->generateServiceCode(),
                'created_by' => Auth::id(),
            ]);

            $service->forceFill([
                'charge_master_id' => $service->is_billable ? $service->id : null,
            ])->save();

            return $service->refresh();
        });
    }

    private function generateServiceCode(): string
    {
        do {
            $code = 'SVC-'.Str::upper(Str::random(8));
        } while (FacilityService::query()->where('service_code', $code)->exists());

        return $code;
    }
}
