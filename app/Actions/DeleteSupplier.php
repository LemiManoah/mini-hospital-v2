<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

final readonly class DeleteSupplier
{
    public function handle(Supplier $supplier): void
    {
        DB::transaction(fn () => $supplier->delete());
    }
}
