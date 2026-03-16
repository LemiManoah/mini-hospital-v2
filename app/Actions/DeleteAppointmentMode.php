<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppointmentMode;
use Illuminate\Support\Facades\DB;

final readonly class DeleteAppointmentMode
{
    public function handle(AppointmentMode $mode): void
    {
        DB::transaction(fn () => $mode->delete());
    }
}
