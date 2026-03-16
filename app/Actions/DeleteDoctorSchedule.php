<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DoctorSchedule;
use Illuminate\Support\Facades\DB;

final readonly class DeleteDoctorSchedule
{
    public function handle(DoctorSchedule $schedule): void
    {
        DB::transaction(fn () => $schedule->delete());
    }
}
