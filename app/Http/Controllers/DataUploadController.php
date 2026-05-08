<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessPatientImport;
use App\Enums\InventoryItemType;
use App\Exports\InventoryItemImportTemplate;
use App\Exports\PatientImportTemplate;
use App\Http\Requests\ImportInventoryItemsRequest;
use App\Http\Requests\ImportPatientsRequest;
use App\Jobs\ImportInventoryItemsJob;
use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DataUploadController implements HasMiddleware
{
    private const string IMPORT_ERROR_REPORT_SESSION_KEY = 'data_upload_import_error_report';

    public static function middleware(): array
    {
        return [
            new Middleware('permission:patients.create', only: ['patientTemplate', 'importPatients']),
            new Middleware('permission:inventory_items.create', only: [
                'drugTemplate',
                'consumableTemplate',
                'importDrugs',
                'importConsumables',
            ]),
        ];
    }

    public function index(): Response
    {
        $importResult = $this->normalizeImportResult(session('import_result'))
            ?? $this->cachedInventoryImportResult();

        return Inertia::render('data-upload/index', [
            'importResult' => $importResult,
            'hasErrorReport' => session()->has(self::IMPORT_ERROR_REPORT_SESSION_KEY)
                || ($importResult !== null && $importResult['errors'] !== []),
            'queuedImportMessage' => session('queued_import_message'),
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

    public function downloadErrorReport(): RedirectResponse|StreamedResponse
    {
        /** @var list<array{row: int, name: string, messages: list<string>}> $errors */
        $errors = session(self::IMPORT_ERROR_REPORT_SESSION_KEY, []);

        if ($errors === []) {
            $cachedResult = $this->cachedInventoryImportResult();
            $errors = $cachedResult !== null ? $cachedResult['errors'] : [];
        }

        if ($errors === []) {
            return to_route('data-upload.index');
        }

        $filename = sprintf('data-upload-error-report-%s.csv', now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($errors): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
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

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
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

        $result = $processPatientImport->handle(
            file: $file,
            tenantId: $tenantId,
            branchCode: $activeBranch->branch_code,
            userId: $userId,
        );
        $this->rememberImportErrorReport($result);

        return to_route('data-upload.index')->with('import_result', $result);
    }

    public function importDrugs(ImportInventoryItemsRequest $request): RedirectResponse
    {
        return $this->importInventoryItems($request, InventoryItemType::DRUG);
    }

    public function importConsumables(ImportInventoryItemsRequest $request): RedirectResponse
    {
        return $this->importInventoryItems($request, InventoryItemType::CONSUMABLE);
    }

    private function importInventoryItems(
        ImportInventoryItemsRequest $request,
        InventoryItemType $itemType,
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

        dispatch(new ImportInventoryItemsJob($path, $itemType, $tenantId, $activeBranch->id, $userId));

        Cache::forget($this->inventoryImportResultCacheKey($tenantId, $activeBranch->id, $userId, $itemType));
        session()->forget(self::IMPORT_ERROR_REPORT_SESSION_KEY);

        return to_route('data-upload.index')->with(
            'queued_import_message',
            ucfirst($itemType->value).' import queued. Keep the queue worker running, then refresh this page to see the latest result.',
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
