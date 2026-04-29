<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateConsultationTariff;
use App\Actions\DeleteConsultationTariff;
use App\Actions\UpdateConsultationTariff;
use App\Enums\ConsultationType;
use App\Enums\VisitType;
use App\Http\Requests\StoreConsultationTariffRequest;
use App\Models\ConsultationTariff;
use App\Models\FacilityService;
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ConsultationTariffController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:consultation_tariffs.view', only: ['index']),
            new Middleware('permission:consultation_tariffs.create', only: ['create', 'store']),
            new Middleware('permission:consultation_tariffs.update', only: ['edit', 'update']),
            new Middleware('permission:consultation_tariffs.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $tariffs = $this->activeBranchWorkspace->apply(ConsultationTariff::query())
            ->with('facilityService:id,name,service_code,selling_price,is_billable,is_active')
            ->when(
                $search !== '',
                static fn (Builder $query): Builder => $query->whereHas(
                    'facilityService',
                    static fn (Builder $serviceQuery): Builder => $serviceQuery
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('service_code', 'like', sprintf('%%%s%%', $search)),
                ),
            )
            ->orderBy('consultation_type')
            ->orderByRaw('CASE WHEN visit_type IS NULL THEN 1 ELSE 0 END')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('consultation-tariff/index', [
            'consultationTariffs' => $tariffs,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('consultation-tariff/create', $this->formOptions());
    }

    public function store(StoreConsultationTariffRequest $request, CreateConsultationTariff $action): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $branchId = BranchContext::getActiveBranchId();

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);
        abort_unless(is_string($branchId) && $branchId !== '', 403);

        $action->handle([
            ...$request->validated(),
            'tenant_id' => $tenantId,
            'facility_branch_id' => $branchId,
        ]);

        return to_route('consultation-tariffs.index')->with('success', 'Consultation tariff created successfully.');
    }

    public function edit(ConsultationTariff $consultationTariff): Response
    {
        $this->activeBranchWorkspace->authorizeModel($consultationTariff);

        return Inertia::render('consultation-tariff/edit', [
            'consultationTariff' => $consultationTariff->load('facilityService:id,name,service_code,selling_price'),
            ...$this->formOptions(),
        ]);
    }

    public function update(
        StoreConsultationTariffRequest $request,
        ConsultationTariff $consultationTariff,
        UpdateConsultationTariff $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($consultationTariff);

        $action->handle($consultationTariff, $request->validated());

        return to_route('consultation-tariffs.index')->with('success', 'Consultation tariff updated successfully.');
    }

    public function destroy(
        ConsultationTariff $consultationTariff,
        DeleteConsultationTariff $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($consultationTariff);

        $action->handle($consultationTariff);

        return to_route('consultation-tariffs.index')->with('success', 'Consultation tariff deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'visitTypeOptions' => collect(VisitType::cases())
                ->map(static fn (VisitType $visitType): array => [
                    'value' => $visitType->value,
                    'label' => $visitType->label(),
                ])
                ->prepend([
                    'value' => 'all',
                    'label' => 'All Visit Types',
                ])
                ->values()
                ->all(),
            'consultationTypeOptions' => collect(ConsultationType::cases())
                ->map(static fn (ConsultationType $consultationType): array => [
                    'value' => $consultationType->value,
                    'label' => $consultationType->label(),
                ])
                ->values()
                ->all(),
            'facilityServiceOptions' => FacilityService::query()
                ->where('tenant_id', $tenantId)
                ->where('is_billable', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'service_code', 'selling_price'])
                ->map(static fn (FacilityService $service): array => [
                    'value' => $service->id,
                    'label' => sprintf(
                        '%s%s%s',
                        $service->name,
                        $service->service_code !== '' ? ' ('.$service->service_code.')' : '',
                        $service->selling_price !== null ? ' - '.$service->selling_price : '',
                    ),
                ])
                ->values()
                ->all(),
        ];
    }
}
