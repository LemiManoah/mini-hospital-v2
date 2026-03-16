<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAppointment
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Appointment $appointment, array $attributes): Appointment
    {
        return DB::transaction(function () use ($appointment, $attributes): Appointment {
            $appointment->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $appointment->refresh();
        });
    }
}
