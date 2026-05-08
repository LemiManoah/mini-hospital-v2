<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryItemType;
use App\Imports\InventoryItemImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ProcessInventoryItemImport
{
    /**
     * @return array{
     *   imported: int,
     *   skipped: int,
     *   errors: list<array{row: int, name: string, messages: list<string>}>,
     *   previewRows: list<array{row: int, name: string, location: string, quantity: float, batchNumber: string|null, expiryDate: string|null, unitCost: float}>
     * }
     */
    public function handle(
        UploadedFile|string $file,
        InventoryItemType $itemType,
        string $tenantId,
        string $branchId,
        string $userId,
        ?string $disk = null,
        bool $preview = false,
    ): array {
        $import = new InventoryItemImport(
            itemType: $itemType,
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            preview: $preview,
        );

        Excel::import($import, $file, $disk);

        $errors = $import->errors();

        return [
            'imported' => $import->getImportedCount(),
            'skipped' => count($errors),
            'errors' => $errors,
            'previewRows' => $import->previewRows(),
        ];
    }
}
