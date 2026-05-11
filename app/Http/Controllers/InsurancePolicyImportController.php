<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessInsurancePriceListImport;
use App\Enums\DataImportStatus;
use App\Exports\InsurancePolicyImportTemplate;
use App\Http\Requests\ImportPatientsRequest;
use App\Jobs\ImportInsurancePriceListsJob;
use App\Models\DataImport;
use App\Models\FacilityBranch;
use App\Models\InsurancePackage;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final readonly class InsurancePolicyImportController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.update'),
        ];
    }

    public function template(InsurancePackage $insurancePackage, InsurancePolicy $insurancePolicy): BinaryFileResponse
    {
        $this->ensurePolicyBelongsToPackage($insurancePackage, $insurancePolicy);

        $filename = sprintf(
            'insurance-%s-policy-template-%s.xlsx',
            $insurancePolicy->policy_type->value,
            now()->format('Y-m-d'),
        );

        return Excel::download(new InsurancePolicyImportTemplate($insurancePolicy->policy_type->itemType()), $filename);
    }

    public function import(
        ImportPatientsRequest $request,
        InsurancePackage $insurancePackage,
        InsurancePolicy $insurancePolicy,
        ProcessInsurancePriceListImport $processInsurancePriceListImport,
    ): RedirectResponse {
        $this->ensurePolicyBelongsToPackage($insurancePackage, $insurancePolicy);

        /** @var User $user */
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;
        $userId = (string) $user->id;
        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return back()->withErrors([
                'branch' => 'Please select an active branch before importing an insurance policy.',
            ]);
        }

        if ($insurancePolicy->facility_branch_id !== $activeBranch->id) {
            return back()->withErrors([
                'branch' => 'This policy belongs to a different branch. Switch to that branch before importing prices.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->store('imports/insurance-policies');

        if (! is_string($path)) {
            return back()->withErrors([
                'file' => 'The uploaded file could not be stored for background processing.',
            ]);
        }

        $dataImport = DataImport::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $activeBranch->id,
            'user_id' => $userId,
            'import_type' => 'insurance_policy_items',
            'source_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => DataImportStatus::Processing->value,
            'started_at' => now(),
            'context' => [
                'insurance_package_id' => $insurancePackage->id,
                'insurance_policy_id' => $insurancePolicy->id,
                'insurance_policy_name' => $insurancePolicy->name,
                'policy_type' => $insurancePolicy->policy_type->value,
                'item_type' => $insurancePolicy->policy_type->itemType()->value,
                'branch_name' => $activeBranch->name,
            ],
        ]);

        try {
            $result = $processInsurancePriceListImport->handle(
                file: $path,
                tenantId: $tenantId,
                insurancePolicyId: $insurancePolicy->id,
                userId: $userId,
                branchName: $activeBranch->name,
                disk: 'local',
                preview: true,
            );
        } catch (Throwable $throwable) {
            $dataImport->forceFill([
                'status' => DataImportStatus::Failed->value,
                'failure_message' => $throwable->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $throwable;
        }

        $dataImport->forceFill([
            'status' => DataImportStatus::Previewed->value,
            'preview_count' => $result['imported'],
            'skipped_count' => $result['skipped'],
            'preview_rows' => $result['previewRows'],
            'error_report' => $result['errors'],
            'completed_at' => now(),
        ])->save();

        return to_route('insurance-packages.show', $insurancePackage)->with(
            'insurance_import_result',
            $result,
        )->with(
            'insurance_import_result_mode',
            'preview',
        )->with(
            'insurance_import_policy_id',
            $insurancePolicy->id,
        );
    }

    public function confirm(DataImport $dataImport): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();
        $activeBranch = BranchContext::getActiveBranch($user);
        /** @var array<string, mixed> $context */
        $context = is_array($dataImport->context) ? $dataImport->context : [];
        $insurancePackageId = $this->contextString($context, 'insurance_package_id');
        $insurancePolicyId = $this->contextString($context, 'insurance_policy_id');
        $insurancePolicyName = $this->contextString($context, 'insurance_policy_name') ?? 'Insurance policy';
        $branchName = $this->contextString($context, 'branch_name') ?? '';

        abort_if(! $activeBranch instanceof FacilityBranch
            || $dataImport->tenant_id !== (string) $user->tenant_id
            || $dataImport->branch_id !== $activeBranch->id
            || $insurancePackageId === null
            || $insurancePolicyId === null
            || $dataImport->import_type !== 'insurance_policy_items', 404);

        if ($dataImport->status !== DataImportStatus::Previewed) {
            return to_route('insurance-packages.show', $insurancePackageId)->withErrors([
                'import' => 'Only previewed insurance policy imports can be confirmed.',
            ]);
        }

        if ($dataImport->preview_count < 1) {
            return to_route('insurance-packages.show', $insurancePackageId)->withErrors([
                'import' => 'This policy import has no valid rows to commit.',
            ]);
        }

        $storedPath = $dataImport->stored_path;

        if (! is_string($storedPath) || $storedPath === '') {
            return to_route('insurance-packages.show', $insurancePackageId)->withErrors([
                'import' => 'The stored import file is missing.',
            ]);
        }

        $dataImport->forceFill([
            'status' => DataImportStatus::Queued->value,
            'imported_count' => 0,
            'skipped_count' => 0,
            'failure_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
        ])->save();

        dispatch(new ImportInsurancePriceListsJob(
            $storedPath,
            (string) $dataImport->tenant_id,
            $insurancePolicyId,
            $branchName !== '' ? $branchName : $activeBranch->name,
            (string) $user->id,
            $dataImport->id,
        ));

        return to_route('insurance-packages.show', $insurancePackageId)->with(
            'insurance_queued_import_message',
            $insurancePolicyName.' import confirmed and queued. Keep the queue worker running, then refresh this package to see the latest result.',
        )->with(
            'insurance_import_policy_id',
            $insurancePolicyId,
        );
    }

    private function ensurePolicyBelongsToPackage(InsurancePackage $insurancePackage, InsurancePolicy $insurancePolicy): void
    {
        abort_unless($insurancePolicy->insurance_package_id === $insurancePackage->id, 404);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextString(array $context, string $key): ?string
    {
        $value = $context[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
