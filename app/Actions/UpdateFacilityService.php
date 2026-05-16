<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateFacilityService
{
    public function __construct(
        private SyncFacilityServiceChargeMaster $syncFacilityServiceChargeMaster,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(FacilityService $service, array $attributes): FacilityService
    {
        return DB::transaction(function () use ($service, $attributes): FacilityService {
            $unitPrice = $this->unitPrice($attributes['unit_price'] ?? null);
            unset($attributes['unit_price']);

            $service->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            $this->syncFacilityServiceChargeMaster->handle($service->refresh(), $unitPrice);

            return $service->refresh();
        });
    }

    private function unitPrice(mixed $value): int|float|string|null
    {
        return is_int($value) || is_float($value) || is_string($value) ? $value : null;
    }
}
