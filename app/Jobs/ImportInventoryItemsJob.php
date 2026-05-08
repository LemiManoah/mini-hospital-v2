<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ProcessInventoryItemImport;
use App\Enums\InventoryItemType;
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
    ) {}

    public function handle(ProcessInventoryItemImport $processInventoryItemImport): void
    {
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

        Cache::put($this->cacheKey(), [
            'imported' => 0,
            'skipped' => 1,
            'errors' => [
                [
                    'row' => 0,
                    'name' => $this->itemType->value.' import',
                    'messages' => [$exception->getMessage()],
                ],
            ],
        ], now()->addDay());

        Log::error('Inventory import failed.', [
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId,
            'item_type' => $this->itemType->value,
            'file_path' => $this->filePath,
            'exception' => $exception->getMessage(),
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
