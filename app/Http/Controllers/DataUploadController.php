<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessInventoryItemImport;
use App\Actions\ProcessPatientImport;
use App\Enums\InventoryItemType;
use App\Exports\InventoryItemImportTemplate;
use App\Exports\PatientImportTemplate;
use App\Http\Requests\ImportInventoryItemsRequest;
use App\Http\Requests\ImportPatientsRequest;
use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class DataUploadController implements HasMiddleware
{
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
        /** @var array{imported: int, skipped: int, errors: list<array{row: int, name: string, messages: list<string>}>}|null $importResult */
        $importResult = session('import_result');

        return Inertia::render('data-upload/index', [
            'importResult' => is_array($importResult) ? $importResult : null,
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

        return to_route('data-upload.index')->with('import_result', $result);
    }

    public function importDrugs(ImportInventoryItemsRequest $request, ProcessInventoryItemImport $processInventoryItemImport): RedirectResponse
    {
        return $this->importInventoryItems($request, $processInventoryItemImport, InventoryItemType::DRUG);
    }

    public function importConsumables(ImportInventoryItemsRequest $request, ProcessInventoryItemImport $processInventoryItemImport): RedirectResponse
    {
        return $this->importInventoryItems($request, $processInventoryItemImport, InventoryItemType::CONSUMABLE);
    }

    private function importInventoryItems(
        ImportInventoryItemsRequest $request,
        ProcessInventoryItemImport $processInventoryItemImport,
        InventoryItemType $itemType,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;
        $userId = (string) $user->id;

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $result = $processInventoryItemImport->handle(
            file: $file,
            itemType: $itemType,
            tenantId: $tenantId,
            userId: $userId,
        );

        return to_route('data-upload.index')->with('import_result', $result);
    }
}
