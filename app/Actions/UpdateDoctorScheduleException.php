<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DoctorScheduleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateDoctorScheduleException
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(DoctorScheduleException $exception, array $attributes): DoctorScheduleException
    {
        return DB::transaction(function () use ($exception, $attributes): DoctorScheduleException {
            $exception->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $exception->refresh();
        });
    }
}
