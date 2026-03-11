<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUnit
{
    public function handle(Unit $unit): void
    {
        DB::transaction(fn () => $unit->delete());
    }
}
