<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessFacilityServiceImport;
use App\Actions\ProcessInventoryItemImport;
use App\Actions\ProcessPatientImport;
use App\Enums\DataImportStatus;
use App\Enums\InventoryItemType;
use App\Exports\FacilityServiceImportTemplate;
use App\Exports\InventoryItemImportTemplate;
use App\Exports\PatientImportTemplate;
use App\Http\Requests\ImportInventoryItemsRequest;
use App\Http\Requests\ImportPatientsRequest;
use App\Jobs\ImportFacilityServicesJob;
use App\Jobs\ImportInventoryItemsJob;
use App\Jobs\ImportPatientsJob;
use App\Models\DataImport;
use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final readonly class DataUploadController implements HasMiddleware
{
    private const string IMPORT_ERROR_REPORT_SESSION_KEY = 'data_upload_import_error_report';

    public static function middleware(): array
    {
        return [
            new Middleware('permission:patients.create', only: ['patientTemplate', 'importPatients', 'confirmPatientImport']),
            new Middleware('permission:inventory_items.create', only: [
                'drugTemplate',
                'consumableTemplate',
                'importDrugs',
                'importConsumables',
                'confirmInventoryImport',
            ]),
            new Middleware('permission:facility_services.create', only: [
                'facilityServiceTemplate',
                'importFacilityServices',
                'confirmFacilityServiceImport',
            ]),
        ];
    }

    public function index(): Response
    {
        $importResult = $this->normalizeImportResult(session('import_result'))
            ?? $this->cachedInventoryImportResult()
            ?? $this->latestDataImportResult();

        return Inertia::render('data-upload/index', [
            'importResult' => $importResult,
            'importResultMode' => session('import_result_mode') === 'preview'
                ? 'preview'
                : $this->latestImportResultMode(),
            'hasErrorReport' => session()->has(self::IMPORT_ERROR_REPORT_SESSION_KEY)
                || ($importResult !== null && $importResult['errors'] !== []),
            'queuedImportMessage' => session('queued_import_message'),
            'dataImports' => $this->latestDataImports(),
        ]);
    }

    public function patientTemplate(): BinaryFileResponse
    {
        $filename = sprintf('patient-import-template-%s.xlsx', now()->format('Y-m-d'));

        return Excel::download(new PatientImportTemplate(), $filename);
    }

    public function drugTemplate(): BinaryFileResponse
    {
        $filename = sprintf('drug-import-template-%s.xlsx', now()->format('Y-m-d'));

        return Excel::download(new InventoryItemImportTemplate(InventoryItemType::DRUG), $filename);
    }

    public function consumableTemplate(): BinaryFileResponse
    {
        $filename = sprintf('consumable-import-template-%s.xlsx', now()->format('Y-m-d'));

        return Excel::download(new InventoryItemImportTemplate(InventoryItemType::CONSUMABLE), $filename);
    }

    public function facilityServiceTemplate(): BinaryFileResponse
    {
        $filename = sprintf('facility-service-import-template-%s.xlsx', now()->format('Y-m-d'));

        return Excel::download(new FacilityServiceImportTemplate(), $filename);
    }

    public function downloadErrorReport(): RedirectResponse|HttpResponse
    {
        /** @var list<array{row: int, name: string, messages: list<string>}> $errors */
        $errors = session(self::IMPORT_ERROR_REPORT_SESSION_KEY, []);

        if ($errors === []) {
            $cachedResult = $this->cachedInventoryImportResult();
            $errors = $cachedResult !== null ? $cachedResult['errors'] : [];
        }

        if ($errors === []) {
            $latestResult = $this->latestDataImportResult();
            $errors = $latestResult !== null ? $latestResult['errors'] : [];
        }

        if ($errors === []) {
            return to_route('data-upload.index');
        }

        $filename = sprintf('data-upload-error-report-%s.csv', now()->format('Y-m-d'));

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return to_route('data-upload.index');
        }

        fputcsv($handle, ['row', 'name', 'errors'], escape: '\\');

        foreach ($errors as $error) {
            fputcsv(
                $handle,
                [
                    (string) $error['row'],
                    $error['name'],
                    implode('; ', $error['messages']),
                ],
                escape: '\\',
            );
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return response((string) $contents, 200, [
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importPatients(ImportPatientsRequest $request, ProcessPatientImport $processPatientImport): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;
        $userId = (string) $user->id;

        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return back()->withErrors([
                'branch' => 'Please select an active branch before importing patients.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->store('imports/patients');

        if (! is_string($path)) {
            return back()->withErrors([
                'file' => 'The uploaded file could not be stored for background processing.',
            ]);
        }

        $dataImport = DataImport::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $activeBranch->id,
            'user_id' => $userId,
            'import_type' => 'patients',
            'source_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => DataImportStatus::Processing->value,
            'started_at' => now(),
        ]);

        try {
            $result = $processPatientImport->handle(
                file: $path,
                tenantId: $tenantId,
                branchCode: $activeBranch->branch_code,
                userId: $userId,
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

        $this->rememberImportErrorReport($result);

        return to_route('data-upload.index')->with(
            'import_result',
            $result,
        )->with(
            'import_result_mode',
            'preview',
        );
    }

    public function importDrugs(ImportInventoryItemsRequest $request, ProcessInventoryItemImport $processInventoryItemImport): RedirectResponse
    {
        return $this->importInventoryItems($request, InventoryItemType::DRUG, $processInventoryItemImport);
    }

    public function importConsumables(ImportInventoryItemsRequest $request, ProcessInventoryItemImport $processInventoryItemImport): RedirectResponse
    {
        return $this->importInventoryItems($request, InventoryItemType::CONSUMABLE, $processInventoryItemImport);
    }

    public function importFacilityServices(ImportPatientsRequest $request, ProcessFacilityServiceImport $processFacilityServiceImport): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;
        $userId = (string) $user->id;
        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return back()->withErrors([
                'branch' => 'Please select an active branch before importing facility services.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->store('imports/facility-services');

        if (! is_string($path)) {
            return back()->withErrors([
                'file' => 'The uploaded file could not be stored for background processing.',
            ]);
        }

        $dataImport = DataImport::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $activeBranch->id,
            'user_id' => $userId,
            'import_type' => 'facility_services',
            'source_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => DataImportStatus::Processing->value,
            'started_at' => now(),
        ]);

        try {
            $result = $processFacilityServiceImport->handle(
                file: $path,
                tenantId: $tenantId,
                userId: $userId,
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

        $this->rememberImportErrorReport($result);

        return to_route('data-upload.index')->with(
            'import_result',
            $result,
        )->with(
            'import_result_mode',
            'preview',
        );
    }

    public function confirmInventoryImport(DataImport $dataImport): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();
        $activeBranch = BranchContext::getActiveBranch($user);

        abort_if(! $activeBranch instanceof FacilityBranch
            || $dataImport->tenant_id !== (string) $user->tenant_id
            || $dataImport->branch_id !== $activeBranch->id
            || ! str_starts_with($dataImport->import_type, 'inventory_'), 404);

        if ($dataImport->status !== DataImportStatus::Previewed) {
            return to_route('data-upload.index')->withErrors([
                'import' => 'Only previewed inventory imports can be confirmed.',
            ]);
        }

        if ($dataImport->preview_count < 1) {
            return to_route('data-upload.index')->withErrors([
                'import' => 'This inventory import has no valid rows to commit.',
            ]);
        }

        $storedPath = $dataImport->stored_path;
        $itemType = str_ends_with($dataImport->import_type, InventoryItemType::DRUG->value)
            ? InventoryItemType::DRUG
            : InventoryItemType::CONSUMABLE;

        if (! is_string($storedPath) || $storedPath === '') {
            return to_route('data-upload.index')->withErrors([
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

        dispatch(new ImportInventoryItemsJob(
            $storedPath,
            $itemType,
            (string) $dataImport->tenant_id,
            (string) $dataImport->branch_id,
            (string) $user->id,
            $dataImport->id,
        ));

        Cache::forget($this->inventoryImportResultCacheKey((string) $dataImport->tenant_id, (string) $dataImport->branch_id, (string) $user->id, $itemType));
        session()->forget(self::IMPORT_ERROR_REPORT_SESSION_KEY);

        return to_route('data-upload.index')->with(
            'queued_import_message',
            ucfirst($itemType->value).' import confirmed and queued. Keep the queue worker running, then refresh this page to see the latest result.',
        );
    }

    public function confirmPatientImport(DataImport $dataImport): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();
        $activeBranch = BranchContext::getActiveBranch($user);

        abort_if(! $activeBranch instanceof FacilityBranch
            || $dataImport->tenant_id !== (string) $user->tenant_id
            || $dataImport->branch_id !== $activeBranch->id
            || $dataImport->import_type !== 'patients', 404);

        if ($dataImport->status !== DataImportStatus::Previewed) {
            return to_route('data-upload.index')->withErrors([
                'import' => 'Only previewed patient imports can be confirmed.',
            ]);
        }

        if ($dataImport->preview_count < 1) {
            return to_route('data-upload.index')->withErrors([
                'import' => 'This patient import has no valid rows to commit.',
            ]);
        }

        $storedPath = $dataImport->stored_path;

        if (! is_string($storedPath) || $storedPath === '') {
            return to_route('data-upload.index')->withErrors([
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

        dispatch(new ImportPatientsJob(
            $storedPath,
            (string) $dataImport->tenant_id,
            $activeBranch->branch_code,
            (string) $user->id,
            $dataImport->id,
        ));

        session()->forget(self::IMPORT_ERROR_REPORT_SESSION_KEY);

        return to_route('data-upload.index')->with(
            'queued_import_message',
            'Patient import confirmed and queued. Keep the queue worker running, then refresh this page to see the latest result.',
        );
    }

    public function confirmFacilityServiceImport(DataImport $dataImport): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();
        $activeBranch = BranchContext::getActiveBranch($user);

        abort_if(! $activeBranch instanceof FacilityBranch
            || $dataImport->tenant_id !== (string) $user->tenant_id
            || $dataImport->branch_id !== $activeBranch->id
            || $dataImport->import_type !== 'facility_services', 404);

        if ($dataImport->status !== DataImportStatus::Previewed) {
            return to_route('data-upload.index')->withErrors([
                'import' => 'Only previewed facility service imports can be confirmed.',
            ]);
        }

        if ($dataImport->preview_count < 1) {
            return to_route('data-upload.index')->withErrors([
                'import' => 'This facility service import has no valid rows to commit.',
            ]);
        }

        $storedPath = $dataImport->stored_path;

        if (! is_string($storedPath) || $storedPath === '') {
            return to_route('data-upload.index')->withErrors([
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

        dispatch(new ImportFacilityServicesJob(
            $storedPath,
            (string) $dataImport->tenant_id,
            (string) $user->id,
            $dataImport->id,
        ));

        session()->forget(self::IMPORT_ERROR_REPORT_SESSION_KEY);

        return to_route('data-upload.index')->with(
            'queued_import_message',
            'Facility service import confirmed and queued. Keep the queue worker running, then refresh this page to see the latest result.',
        );
    }

    private function importInventoryItems(
        ImportInventoryItemsRequest $request,
        InventoryItemType $itemType,
        ProcessInventoryItemImport $processInventoryItemImport,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;
        $userId = (string) $user->id;

        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return back()->withErrors([
                'branch' => 'Please select an active branch before importing inventory items.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $path = $file->store('imports/inventory');

        if (! is_string($path)) {
            return back()->withErrors([
                'file' => 'The uploaded file could not be stored for background processing.',
            ]);
        }

        $dataImport = DataImport::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $activeBranch->id,
            'user_id' => $userId,
            'import_type' => 'inventory_'.$itemType->value,
            'source_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => DataImportStatus::Processing->value,
            'started_at' => now(),
        ]);

        try {
            $result = $processInventoryItemImport->handle(
                file: $path,
                itemType: $itemType,
                tenantId: $tenantId,
                branchId: $activeBranch->id,
                userId: $userId,
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

        Cache::forget($this->inventoryImportResultCacheKey($tenantId, $activeBranch->id, $userId, $itemType));
        session()->forget(self::IMPORT_ERROR_REPORT_SESSION_KEY);
        $this->rememberImportErrorReport($result);

        return to_route('data-upload.index')->with(
            'import_result',
            $result,
        )->with(
            'import_result_mode',
            'preview',
        );
    }

    /**
     * @param  array{
     *     imported: int,
     *     skipped: int,
     *     errors: list<array{row: int, name: string, messages: list<string>}>
     * }  $result
     */
    private function rememberImportErrorReport(array $result): void
    {
        if ($result['errors'] === []) {
            session()->forget(self::IMPORT_ERROR_REPORT_SESSION_KEY);

            return;
        }

        session()->put(self::IMPORT_ERROR_REPORT_SESSION_KEY, $result['errors']);
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<array{row: int, name: string, messages: list<string>}>}|null
     */
    private function cachedInventoryImportResult(): ?array
    {
        $user = request()->user();

        if (! $user instanceof User) {
            return null;
        }

        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return null;
        }

        foreach ([InventoryItemType::DRUG, InventoryItemType::CONSUMABLE] as $itemType) {
            $result = Cache::get($this->inventoryImportResultCacheKey(
                (string) $user->tenant_id,
                $activeBranch->id,
                (string) $user->id,
                $itemType,
            ));

            $result = $this->normalizeImportResult($result);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<array{row: int, name: string, messages: list<string>}>}|null
     */
    private function latestDataImportResult(): ?array
    {
        $user = request()->user();

        if (! $user instanceof User) {
            return null;
        }

        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return null;
        }

        $dataImport = DataImport::query()
            ->where('tenant_id', (string) $user->tenant_id)
            ->where('branch_id', $activeBranch->id)
            ->whereIn('status', [
                DataImportStatus::Previewed->value,
                DataImportStatus::Completed->value,
                DataImportStatus::Failed->value,
            ])
            ->latest()
            ->first();

        if (! $dataImport instanceof DataImport) {
            return null;
        }

        return $this->normalizeImportResult([
            'imported' => $dataImport->status === DataImportStatus::Previewed
                ? $dataImport->preview_count
                : $dataImport->imported_count,
            'skipped' => $dataImport->skipped_count,
            'errors' => $dataImport->error_report ?? [],
        ]);
    }

    private function latestImportResultMode(): string
    {
        $user = request()->user();

        if (! $user instanceof User) {
            return 'import';
        }

        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return 'import';
        }

        $status = DataImport::query()
            ->where('tenant_id', (string) $user->tenant_id)
            ->where('branch_id', $activeBranch->id)
            ->whereIn('status', [
                DataImportStatus::Previewed->value,
                DataImportStatus::Completed->value,
                DataImportStatus::Failed->value,
            ])
            ->latest()
            ->value('status');

        return $status === DataImportStatus::Previewed->value ? 'preview' : 'import';
    }

    /**
     * @return list<array{
     *     id: string,
     *     importType: string,
     *     sourceFilename: string,
     *     status: string,
     *     importedCount: int,
     *     skippedCount: int,
     *     previewCount: int,
     *     failureMessage: string|null,
     *     createdAt: string|null,
     *     startedAt: string|null,
     *     completedAt: string|null,
     *     failedAt: string|null
     * }>
     */
    private function latestDataImports(): array
    {
        $user = request()->user();

        if (! $user instanceof User) {
            return [];
        }

        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return [];
        }

        $dataImports = DataImport::query()
            ->where('tenant_id', (string) $user->tenant_id)
            ->where('branch_id', $activeBranch->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DataImport $dataImport): array => [
                'id' => $dataImport->id,
                'importType' => $dataImport->import_type,
                'sourceFilename' => $dataImport->source_filename,
                'status' => $dataImport->status->value,
                'importedCount' => $dataImport->imported_count,
                'skippedCount' => $dataImport->skipped_count,
                'previewCount' => $dataImport->preview_count,
                'failureMessage' => $dataImport->failure_message,
                'createdAt' => $dataImport->created_at?->toDateTimeString(),
                'startedAt' => $dataImport->started_at?->toDateTimeString(),
                'completedAt' => $dataImport->completed_at?->toDateTimeString(),
                'failedAt' => $dataImport->failed_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        return array_values($dataImports);
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<array{row: int, name: string, messages: list<string>}>}|null
     */
    private function normalizeImportResult(mixed $result): ?array
    {
        if (! is_array($result)
            || ! is_int($result['imported'] ?? null)
            || ! is_int($result['skipped'] ?? null)
            || ! is_array($result['errors'] ?? null)
        ) {
            return null;
        }

        $errors = [];

        foreach ($result['errors'] as $error) {
            if (! is_array($error)
                || ! is_int($error['row'] ?? null)
                || ! is_string($error['name'] ?? null)
                || ! is_array($error['messages'] ?? null)
            ) {
                return null;
            }

            $messages = [];

            foreach ($error['messages'] as $message) {
                if (! is_string($message)) {
                    return null;
                }

                $messages[] = $message;
            }

            $errors[] = [
                'row' => $error['row'],
                'name' => $error['name'],
                'messages' => $messages,
            ];
        }

        return [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => $errors,
        ];
    }

    private function inventoryImportResultCacheKey(
        string $tenantId,
        string $branchId,
        string $userId,
        InventoryItemType $itemType,
    ): string {
        return implode(':', [
            'inventory-import-result',
            $tenantId,
            $branchId,
            $userId,
            $itemType->value,
        ]);
    }
}
