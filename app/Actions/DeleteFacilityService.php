<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use Illuminate\Support\Facades\DB;

final readonly class DeleteFacilityService
{
    public function handle(FacilityService $service): void
    {
        DB::transaction(fn () => $service->delete());
    }
}
