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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AppointmentCategoryController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $appointmentCategories = AppointmentCategory::query()
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
        $action->handle($request->validated());

        return to_route('appointment-categories.index')->with('success', 'Appointment category created successfully.');
    }

    public function edit(AppointmentCategory $appointmentCategory): Response
    {
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
        $action->handle($appointmentCategory, $request->validated());

        return to_route('appointment-categories.index')->with('success', 'Appointment category updated successfully.');
    }

    public function destroy(
        DeleteAppointmentCategoryRequest $request,
        AppointmentCategory $appointmentCategory,
        DeleteAppointmentCategory $action,
    ): RedirectResponse {
        $action->handle($appointmentCategory);

        return to_route('appointment-categories.index')->with('success', 'Appointment category deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'clinics' => Clinic::query()
                ->orderBy('clinic_name')
                ->get(['id', 'clinic_name'])
                ->map(static fn (Clinic $clinic): array => [
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                ])
                ->all(),
        ];
    }
}
