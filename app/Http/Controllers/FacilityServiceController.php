<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityService;
use App\Actions\DeleteFacilityService;
use App\Actions\UpdateFacilityService;
use App\Enums\FacilityServiceCategory;
use App\Http\Requests\DeleteFacilityServiceRequest;
use App\Http\Requests\StoreFacilityServiceRequest;
use App\Http\Requests\UpdateFacilityServiceRequest;
use App\Models\FacilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilityServiceController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:facility_services.view', only: ['index']),
            new Middleware('permission:facility_services.create', only: ['create', 'store']),
            new Middleware('permission:facility_services.update', only: ['edit', 'update']),
            new Middleware('permission:facility_services.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $facilityServices = FacilityService::query()
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('service_code', 'like', sprintf('%%%s%%', $search))
                ->orWhere('department_name', 'like', sprintf('%%%s%%', $search)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('facility-service/index', [
            'facilityServices' => $facilityServices,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('facility-service/create', $this->formOptions());
    }

    public function store(StoreFacilityServiceRequest $request, CreateFacilityService $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('facility-services.index')->with('success', 'Facility service created successfully.');
    }

    public function edit(FacilityService $facilityService): Response
    {
        return Inertia::render('facility-service/edit', [
            'facilityService' => $facilityService,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateFacilityServiceRequest $request, FacilityService $facilityService, UpdateFacilityService $action): RedirectResponse
    {
        $action->handle($facilityService, $request->validated());

        return to_route('facility-services.index')->with('success', 'Facility service updated successfully.');
    }

    public function destroy(DeleteFacilityServiceRequest $request, FacilityService $facilityService, DeleteFacilityService $action): RedirectResponse
    {
        $action->handle($facilityService);

        return to_route('facility-services.index')->with('success', 'Facility service deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'categories' => collect(FacilityServiceCategory::cases())
                ->map(static fn (FacilityServiceCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ])
                ->all(),
        ];
    }
}
