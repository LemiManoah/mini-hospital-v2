<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Imports\PatientImport;
use App\Support\BranchScopedNumberGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ImportPatientsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        private string $filePath,
        private string $tenantId,
        private string $branchCode,
        private string $userId,
    ) {}

    public function handle(BranchScopedNumberGenerator $numberGenerator): void
    {
        Log::info('Starting patient import for tenant: '.$this->tenantId);

        Excel::import(
            new PatientImport(
                tenantId: $this->tenantId,
                branchCode: $this->branchCode,
                userId: $this->userId,
                numberGenerator: $numberGenerator,
            ),
            $this->filePath
        );

        Log::info('Patient import completed for tenant: '.$this->tenantId);
    }

    public function failed(Throwable $exception): void
    {
        Log::error(sprintf('Patient import failed for tenant %s: %s', $this->tenantId, $exception->getMessage()));
    }
}
