<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAppointmentCategory;
use App\Actions\DeleteAppointmentCategory;
use App\Actions\UpdateAppointmentCategory;
use App\Http\Requests\DeleteAppointmentCategoryRequest;
use App\Http\Requests\StoreAppointmentCategoryRequest;
use App\Http\Requests\UpdateAppointmentCategoryRequest;
use App\Models\AppointmentCategory;
use App\Models\Clinic;
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AppointmentCategoryController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:appointment_categories.view', only: ['index']),
            new Middleware('permission:appointment_categories.create', only: ['create', 'store']),
            new Middleware('permission:appointment_categories.update', only: ['edit', 'update']),
            new Middleware('permission:appointment_categories.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $appointmentCategories = $this->activeBranchWorkspace->apply(AppointmentCategory::query())
            ->with('clinic:id,clinic_name')
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $search))
                ->orWhereHas('clinic', static fn (Builder $clinicQuery) => $clinicQuery
                    ->where('clinic_name', 'like', sprintf('%%%s%%', $search))))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('appointment-category/index', [
            'appointmentCategories' => $appointmentCategories,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('appointment-category/create', $this->formOptions());
    }

    public function store(StoreAppointmentCategoryRequest $request, CreateAppointmentCategory $action): RedirectResponse
    {
        $validated = $request->validated();

        if (! $this->usesActiveBranchClinic($validated)) {
            return back()->with('error', 'Select a clinic from the active branch.');
        }

        $action->handle($validated);

        return to_route('appointment-categories.index')->with('success', 'Appointment category created successfully.');
    }

    public function edit(AppointmentCategory $appointmentCategory): Response
    {
        $this->activeBranchWorkspace->authorizeModel($appointmentCategory);

        return Inertia::render('appointment-category/edit', [
            'appointmentCategory' => $appointmentCategory->load('clinic:id,clinic_name'),
            ...$this->formOptions(),
        ]);
    }

    public function update(
        UpdateAppointmentCategoryRequest $request,
        AppointmentCategory $appointmentCategory,
        UpdateAppointmentCategory $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($appointmentCategory);

        $validated = $request->validated();

        if (! $this->usesActiveBranchClinic($validated)) {
            return back()->with('error', 'Select a clinic from the active branch.');
        }

        $action->handle($appointmentCategory, $validated);

        return to_route('appointment-categories.index')->with('success', 'Appointment category updated successfully.');
    }

    public function destroy(
        DeleteAppointmentCategoryRequest $request,
        AppointmentCategory $appointmentCategory,
        DeleteAppointmentCategory $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($appointmentCategory);

        $action->handle($appointmentCategory);

        return to_route('appointment-categories.index')->with('success', 'Appointment category deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'clinics' => Clinic::query()
                ->where('branch_id', BranchContext::getActiveBranchId())
                ->orderBy('clinic_name')
                ->get(['id', 'clinic_name'])
                ->map(static fn (Clinic $clinic): array => [
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function usesActiveBranchClinic(array $attributes): bool
    {
        $clinicId = $attributes['clinic_id'] ?? null;

        return ! is_string($clinicId)
            || $clinicId === ''
            || Clinic::query()
                ->whereKey($clinicId)
                ->where('branch_id', BranchContext::getActiveBranchId())
                ->exists();
    }
}
