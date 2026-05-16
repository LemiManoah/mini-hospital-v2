<?php

declare(strict_types=1);

namespace App\Actions;

use App\Imports\FacilityServiceImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ProcessFacilityServiceImport
{
    public function __construct(
        private SyncFacilityServiceChargeMaster $syncFacilityServiceChargeMaster,
    ) {}

    /**
     * @return array{
     *   imported: int,
     *   skipped: int,
     *   errors: list<array{row: int, name: string, messages: list<string>}>,
     *   previewRows: list<array{row: int, name: string, serviceCode: string, category: string, unitPrice: float|null}>
     * }
     */
    public function handle(
        UploadedFile|string $file,
        string $tenantId,
        string $userId,
        ?string $disk = null,
        bool $preview = false,
    ): array {
        $import = new FacilityServiceImport(
            tenantId: $tenantId,
            userId: $userId,
            syncFacilityServiceChargeMaster: $this->syncFacilityServiceChargeMaster,
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
