<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppointmentCategory;
use Illuminate\Support\Facades\DB;

final readonly class DeleteAppointmentCategory
{
    public function handle(AppointmentCategory $category): void
    {
        DB::transaction(fn () => $category->delete());
    }
}
