<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ProcessPatientImport;
use App\Enums\DataImportStatus;
use App\Models\DataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        private string $dataImportId,
    ) {}

    public function handle(ProcessPatientImport $processPatientImport): void
    {
        Log::info('Starting patient import for tenant: '.$this->tenantId);

        $dataImport = DataImport::query()->find($this->dataImportId);

        $dataImport?->forceFill([
            'status' => DataImportStatus::Processing->value,
            'imported_count' => 0,
            'skipped_count' => 0,
            'failure_message' => null,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ])->save();

        $result = $processPatientImport->handle(
            file: $this->filePath,
            tenantId: $this->tenantId,
            branchCode: $this->branchCode,
            userId: $this->userId,
            disk: 'local',
        );

        $dataImport?->forceFill([
            'status' => DataImportStatus::Completed->value,
            'imported_count' => $result['imported'],
            'skipped_count' => $result['skipped'],
            'error_report' => $result['errors'],
            'completed_at' => now(),
        ])->save();

        Storage::disk('local')->delete($this->filePath);

        Log::info('Patient import completed for tenant: '.$this->tenantId);
    }

    public function failed(Throwable $exception): void
    {
        DataImport::query()
            ->whereKey($this->dataImportId)
            ->update([
                'status' => DataImportStatus::Failed->value,
                'failure_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

        Log::error(sprintf('Patient import failed for tenant %s: %s', $this->tenantId, $exception->getMessage()));
    }
}
