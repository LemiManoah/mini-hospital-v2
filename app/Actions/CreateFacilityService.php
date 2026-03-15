<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateFacilityService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): FacilityService
    {
        return DB::transaction(fn (): FacilityService => FacilityService::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
