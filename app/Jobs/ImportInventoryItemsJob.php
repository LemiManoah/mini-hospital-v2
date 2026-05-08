<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ProcessInventoryItemImport;
use App\Enums\DataImportStatus;
use App\Enums\InventoryItemType;
use App\Models\DataImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ImportInventoryItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        private readonly string $filePath,
        private readonly InventoryItemType $itemType,
        private readonly string $tenantId,
        private readonly string $branchId,
        private readonly string $userId,
        private readonly string $dataImportId,
    ) {}

    public function handle(ProcessInventoryItemImport $processInventoryItemImport): void
    {
        $this->markImportProcessing();

        Log::info('Starting inventory import.', [
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId,
            'item_type' => $this->itemType->value,
            'file_path' => $this->filePath,
        ]);

        $result = $processInventoryItemImport->handle(
            file: $this->filePath,
            itemType: $this->itemType,
            tenantId: $this->tenantId,
            branchId: $this->branchId,
            userId: $this->userId,
            disk: 'local',
        );

        Cache::put($this->cacheKey(), $result, now()->addDay());
        $this->markImportCompleted($result);
        Storage::disk('local')->delete($this->filePath);

        Log::info('Inventory import completed.', [
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId,
            'item_type' => $this->itemType->value,
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Storage::disk('local')->delete($this->filePath);

        $result = [
            'imported' => 0,
            'skipped' => 1,
            'errors' => [
                [
                    'row' => 0,
                    'name' => $this->itemType->value.' import',
                    'messages' => [$exception->getMessage()],
                ],
            ],
        ];

        Cache::put($this->cacheKey(), $result, now()->addDay());
        $this->markImportFailed($exception, $result);

        Log::error('Inventory import failed.', [
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId,
            'item_type' => $this->itemType->value,
            'file_path' => $this->filePath,
            'exception' => $exception->getMessage(),
        ]);
    }

    private function markImportProcessing(): void
    {
        DataImport::query()
            ->whereKey($this->dataImportId)
            ->update([
                'status' => DataImportStatus::Processing->value,
                'started_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array{
     *     imported: int,
     *     skipped: int,
     *     errors: list<array{row: int, name: string, messages: list<string>}>
     * }  $result
     */
    private function markImportCompleted(array $result): void
    {
        DataImport::query()
            ->whereKey($this->dataImportId)
            ->update([
                'status' => DataImportStatus::Completed->value,
                'imported_count' => $result['imported'],
                'skipped_count' => $result['skipped'],
                'error_report' => json_encode($result['errors'], JSON_THROW_ON_ERROR),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array{
     *     imported: int,
     *     skipped: int,
     *     errors: list<array{row: int, name: string, messages: list<string>}>
     * }  $result
     */
    private function markImportFailed(Throwable $exception, array $result): void
    {
        DataImport::query()
            ->whereKey($this->dataImportId)
            ->update([
                'status' => DataImportStatus::Failed->value,
                'imported_count' => $result['imported'],
                'skipped_count' => $result['skipped'],
                'error_report' => json_encode($result['errors'], JSON_THROW_ON_ERROR),
                'failure_message' => $exception->getMessage(),
                'failed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function cacheKey(): string
    {
        return implode(':', [
            'inventory-import-result',
            $this->tenantId,
            $this->branchId,
            $this->userId,
            $this->itemType->value,
        ]);
    }
}
