<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Drug;
use Illuminate\Support\Facades\DB;

final readonly class DeleteDrug
{
    public function handle(Drug $drug): void
    {
        DB::transaction(fn () => $drug->delete());
    }
}
