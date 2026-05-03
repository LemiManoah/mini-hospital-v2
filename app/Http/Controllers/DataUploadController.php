<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessPatientImport;
use App\Exports\PatientImportTemplate;
use App\Http\Requests\ImportPatientsRequest;
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

    public function importPatients(ImportPatientsRequest $request, ProcessPatientImport $processPatientImport): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $tenantId = (string) $user->tenant_id;
        $userId = (string) $user->id;

        $activeBranch = BranchContext::getActiveBranch($user);

        if ($activeBranch === null) {
            return back()->withErrors([
                'branch' => 'Please select an active branch before importing patients.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $result = $processPatientImport->handle(
            file: $file,
            tenantId: $tenantId,
            branchName: $activeBranch->name,
            userId: $userId,
        );

        return to_route('data-upload.index')->with('import_result', $result);
    }
}
