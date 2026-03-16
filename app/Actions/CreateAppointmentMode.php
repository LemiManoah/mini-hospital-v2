<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppointmentMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateAppointmentMode
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): AppointmentMode
    {
        return DB::transaction(fn (): AppointmentMode => AppointmentMode::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
