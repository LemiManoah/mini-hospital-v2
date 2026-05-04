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
     *   errors: list<array{row: int, name: string, messages: list<string>}>
     * }
     */
    public function handle(
        UploadedFile $file,
        InventoryItemType $itemType,
        string $tenantId,
        string $userId,
    ): array {
        $import = new InventoryItemImport(
            itemType: $itemType,
            tenantId: $tenantId,
            userId: $userId,
        );

        Excel::import($import, $file);

        $errors = $import->errors();

        return [
            'imported' => $import->getImportedCount(),
            'skipped' => count($errors),
            'errors' => $errors,
        ];
    }
}
