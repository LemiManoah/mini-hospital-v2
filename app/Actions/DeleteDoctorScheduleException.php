<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DoctorScheduleException;
use Illuminate\Support\Facades\DB;

final readonly class DeleteDoctorScheduleException
{
    public function handle(DoctorScheduleException $exception): void
    {
        DB::transaction(fn () => $exception->delete());
    }
}
