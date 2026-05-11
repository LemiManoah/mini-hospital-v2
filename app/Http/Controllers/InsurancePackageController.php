<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsurancePackage;
use App\Actions\DeleteInsurancePackage;
use App\Actions\UpdateInsurancePackage;
use App\Enums\BillableItemType;
use App\Enums\DataImportStatus;
use App\Enums\InventoryItemType;
use App\Http\Requests\DeleteInsurancePackageRequest;
use App\Http\Requests\StoreInsurancePackageRequest;
use App\Http\Requests\UpdateInsurancePackageRequest;
use App\Models\DataImport;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\InsurancePolicy;
use App\Models\InsurancePolicyItem;
use App\Models\InventoryItem;
use App\Models\LabTestCatalog;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InsurancePackageController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.view', only: ['index', 'show']),
            new Middleware('permission:insurance_packages.create', only: ['create', 'store']),
            new Middleware('permission:insurance_packages.update', only: ['edit', 'update']),
            new Middleware('permission:insurance_packages.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $insurancePackages = InsurancePackage::query()
            ->with(['insuranceCompany:id,name'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where(
                    static fn (Builder $searchQuery) => $searchQuery
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas(
                            'insuranceCompany',
                            static fn (Builder $companyQuery) => $companyQuery
                                ->where('name', 'like', sprintf('%%%s%%', $search))
                        )
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('insurance-package/index', [
            'insurancePackages' => $insurancePackages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(InsurancePackage $insurancePackage): Response
    {
        /** @var User|null $user */
        $user = Auth::user();
        $tenantId = (string) $user?->tenant_id;
        $activeBranch = $user instanceof User ? BranchContext::getActiveBranch($user) : null;
        $activeBranchId = $activeBranch instanceof FacilityBranch ? $activeBranch->id : null;

        $insurancePackage->load(['insuranceCompany:id,name']);

        $policies = InsurancePolicy::query()
            ->where('insurance_package_id', $insurancePackage->id)
            ->when(
                is_string($activeBranchId),
                static fn (Builder $query): Builder => $query->where('facility_branch_id', $activeBranchId),
            )
            ->with([
                'branch:id,name,branch_code',
                'items' => static function (Relation $query): Relation {
                    $query->getQuery()->orderBy('effective_from');

                    return $query;
                },
            ])
            ->orderBy('policy_type')
            ->orderBy('name')
            ->get();

        $policyItems = $policies->flatMap(static fn (InsurancePolicy $policy) => $policy->items);
        $serviceIds = $policyItems->where('item_type', BillableItemType::SERVICE)->pluck('item_id');
        $drugIds = $policyItems->where('item_type', BillableItemType::DRUG)->pluck('item_id');
        $testIds = $policyItems->where('item_type', BillableItemType::TEST)->pluck('item_id');

        $serviceNames = FacilityService::query()->whereIn('id', $serviceIds)->pluck('name', 'id');
        $drugNames = InventoryItem::query()->whereIn('id', $drugIds)->pluck('name', 'id');
        $testNames = LabTestCatalog::query()->whereIn('id', $testIds)->pluck('test_name', 'id');

        $policiesForView = $policies->map(static fn (InsurancePolicy $policy): array => [
            'id' => $policy->id,
            'name' => $policy->name,
            'policyType' => $policy->policy_type->value,
            'policyTypeLabel' => $policy->policy_type->label(),
            'facilityBranchId' => $policy->facility_branch_id,
            'effectiveFrom' => $policy->effective_from?->toDateString(),
            'effectiveTo' => $policy->effective_to?->toDateString(),
            'status' => $policy->status->value,
            'branch' => $policy->branch ? [
                'id' => $policy->branch->id,
                'name' => $policy->branch->name,
                'branchCode' => $policy->branch->branch_code,
            ] : null,
            'items' => $policy->items->map(static fn (InsurancePolicyItem $item): array => [
                'id' => $item->id,
                'itemType' => $item->item_type->value,
                'itemId' => $item->item_id,
                'itemName' => match ($item->item_type) {
                    BillableItemType::SERVICE => $serviceNames->get($item->item_id, 'Unknown'),
                    BillableItemType::DRUG => $drugNames->get($item->item_id, 'Unknown'),
                    BillableItemType::TEST => $testNames->get($item->item_id, 'Unknown'),
                    default => '-',
                },
                'price' => $item->price,
                'effectiveFrom' => $item->effective_from?->toDateString(),
                'effectiveTo' => $item->effective_to?->toDateString(),
                'status' => $item->status->value,
            ])->values(),
        ])->values();

        $serviceOptions = FacilityService::query()
            ->where('tenant_id', $tenantId)
            ->where('is_billable', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'service_code'])
            ->map(static fn (FacilityService $s): array => [
                'value' => $s->id,
                'label' => $s->name.($s->service_code !== '' ? ' ('.$s->service_code.')' : ''),
            ])
            ->values()
            ->all();

        $drugOptions = InventoryItem::query()
            ->where('tenant_id', $tenantId)
            ->where('item_type', InventoryItemType::DRUG)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name'])
            ->map(static fn (InventoryItem $d): array => [
                'value' => $d->id,
                'label' => $d->name.($d->generic_name !== '' ? ' ('.$d->generic_name.')' : ''),
            ])
            ->values()
            ->all();

        $testOptions = LabTestCatalog::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('test_name')
            ->get(['id', 'test_name', 'test_code'])
            ->map(static fn (LabTestCatalog $t): array => [
                'value' => $t->id,
                'label' => $t->test_name.($t->test_code !== '' ? ' ('.$t->test_code.')' : ''),
            ])
            ->values()
            ->all();

        $branches = FacilityBranch::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (FacilityBranch $b): array => ['value' => $b->id, 'label' => $b->name])
            ->values()
            ->all();

        return Inertia::render('insurance-package/show', [
            'insurancePackage' => $insurancePackage,
            'policies' => $policiesForView,
            'activeBranch' => $activeBranch instanceof FacilityBranch
                ? [
                    'id' => $activeBranch->id,
                    'name' => $activeBranch->name,
                    'branchCode' => $activeBranch->branch_code,
                ]
                : null,
            'billableItems' => [
                'service' => $serviceOptions,
                'drug' => $drugOptions,
                'test' => $testOptions,
            ],
            'branches' => $branches,
            'policyImports' => $this->latestPolicyImports($insurancePackage, $activeBranch),
            'importResult' => $this->normalizeImportResult(session('insurance_import_result')) ?? $this->latestPolicyImportResult($insurancePackage, $activeBranch),
            'importResultMode' => session('insurance_import_result_mode') === 'preview'
                ? 'preview'
                : $this->latestPolicyImportResultMode($insurancePackage, $activeBranch),
            'queuedImportMessage' => session('insurance_queued_import_message'),
            'selectedPolicyId' => session('insurance_import_policy_id'),
        ]);
    }

    public function create(): Response
    {
        $companies = InsuranceCompany::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('insurance-package/create', [
            'companies' => $companies,
        ]);
    }

    public function store(StoreInsurancePackageRequest $request, CreateInsurancePackage $action): RedirectResponse
    {
        $action->handle($request->createDto());

        return to_route('insurance-packages.index')->with('success', 'Insurance package created successfully.');
    }

    public function edit(InsurancePackage $insurancePackage): Response
    {
        $companies = InsuranceCompany::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('insurance-package/edit', [
            'insurancePackage' => $insurancePackage,
            'companies' => $companies,
        ]);
    }

    public function update(
        UpdateInsurancePackageRequest $request,
        InsurancePackage $insurancePackage,
        UpdateInsurancePackage $action
    ): RedirectResponse {
        $action->handle($insurancePackage, $request->updateDto());

        return to_route('insurance-packages.index')->with('success', 'Insurance package updated successfully.');
    }

    public function destroy(
        DeleteInsurancePackageRequest $request,
        InsurancePackage $insurancePackage,
        DeleteInsurancePackage $action
    ): RedirectResponse {
        $action->handle($insurancePackage);

        return to_route('insurance-packages.index')->with('success', 'Insurance package deleted successfully.');
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
     *     policyId: string|null,
     *     policyName: string|null,
     *     createdAt: string|null,
     *     startedAt: string|null,
     *     completedAt: string|null,
     *     failedAt: string|null
     * }>
     */
    private function latestPolicyImports(InsurancePackage $insurancePackage, ?FacilityBranch $activeBranch): array
    {
        if (! $activeBranch instanceof FacilityBranch) {
            return [];
        }

        $policyImports = DataImport::query()
            ->where('tenant_id', $insurancePackage->tenant_id)
            ->where('branch_id', $activeBranch->id)
            ->where('import_type', 'insurance_policy_items')
            ->where('context->insurance_package_id', $insurancePackage->id)
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
                'policyId' => is_array($dataImport->context) && is_string($dataImport->context['insurance_policy_id'] ?? null)
                    ? $dataImport->context['insurance_policy_id']
                    : null,
                'policyName' => is_array($dataImport->context) && is_string($dataImport->context['insurance_policy_name'] ?? null)
                    ? $dataImport->context['insurance_policy_name']
                    : null,
                'createdAt' => $dataImport->created_at?->toDateTimeString(),
                'startedAt' => $dataImport->started_at?->toDateTimeString(),
                'completedAt' => $dataImport->completed_at?->toDateTimeString(),
                'failedAt' => $dataImport->failed_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        return array_values($policyImports);
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<array{row: int, name: string, messages: list<string>}>}|null
     */
    private function latestPolicyImportResult(InsurancePackage $insurancePackage, ?FacilityBranch $activeBranch): ?array
    {
        if (! $activeBranch instanceof FacilityBranch) {
            return null;
        }

        $dataImport = DataImport::query()
            ->where('tenant_id', $insurancePackage->tenant_id)
            ->where('branch_id', $activeBranch->id)
            ->where('import_type', 'insurance_policy_items')
            ->where('context->insurance_package_id', $insurancePackage->id)
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

    private function latestPolicyImportResultMode(InsurancePackage $insurancePackage, ?FacilityBranch $activeBranch): string
    {
        if (! $activeBranch instanceof FacilityBranch) {
            return 'import';
        }

        $status = DataImport::query()
            ->where('tenant_id', $insurancePackage->tenant_id)
            ->where('branch_id', $activeBranch->id)
            ->where('import_type', 'insurance_policy_items')
            ->where('context->insurance_package_id', $insurancePackage->id)
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
}
