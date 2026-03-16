<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppointmentMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAppointmentMode
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(AppointmentMode $mode, array $attributes): AppointmentMode
    {
        return DB::transaction(function () use ($mode, $attributes): AppointmentMode {
            $mode->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $mode->refresh();
        });
    }
}
