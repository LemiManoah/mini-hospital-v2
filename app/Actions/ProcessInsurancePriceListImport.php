<?php

declare(strict_types=1);

namespace App\Actions;

use App\Imports\InsurancePriceListImport;
use App\Models\InsurancePolicy;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ProcessInsurancePriceListImport
{
    /**
     * @return array{
     *   imported: int,
     *   skipped: int,
     *   errors: list<array{row: int, name: string, messages: list<string>}>,
     *   previewRows: list<array{row: int, name: string, branch: string, itemType: string, price: float, effectiveFrom: string}>
     * }
     */
    public function handle(
        UploadedFile|string $file,
        string $tenantId,
        string $insurancePolicyId,
        string $userId,
        string $branchName,
        ?string $disk = null,
        bool $preview = false,
    ): array {
        /** @var InsurancePolicy $insurancePolicy */
        $insurancePolicy = InsurancePolicy::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($insurancePolicyId);

        $import = new InsurancePriceListImport(
            tenantId: $tenantId,
            insurancePolicyId: $insurancePolicy->id,
            userId: $userId,
            itemType: $insurancePolicy->policy_type->itemType(),
            branchName: $branchName,
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
