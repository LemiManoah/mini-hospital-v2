<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateClinic;
use App\Actions\DeleteClinic;
use App\Actions\UpdateClinic;
use App\Http\Requests\DeleteClinicRequest;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Models\Clinic;
use App\Models\Department;
use App\Models\FacilityBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ClinicController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:clinics.view', only: ['index']),
            new Middleware('permission:clinics.create', only: ['create', 'store']),
            new Middleware('permission:clinics.update', only: ['edit', 'update']),
            new Middleware('permission:clinics.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $clinics = Clinic::query()
            ->with(['branch', 'department'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('clinic_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('clinic_code', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('clinic/index', [
            'clinics' => $clinics,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('clinic/create', [
            'branches' => FacilityBranch::all(),
            'departments' => Department::all(),
        ]);
    }

    public function store(StoreClinicRequest $request, CreateClinic $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('clinics.index')->with('success', 'Clinic created successfully.');
    }

    public function edit(Clinic $clinic): Response
    {
        return Inertia::render('clinic/edit', [
            'clinic' => $clinic,
            'branches' => FacilityBranch::all(),
            'departments' => Department::all(),
        ]);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic, UpdateClinic $action): RedirectResponse
    {
        $action->handle($clinic, $request->validated());

        return to_route('clinics.index')->with('success', 'Clinic updated successfully.');
    }

    public function destroy(DeleteClinicRequest $request, Clinic $clinic, DeleteClinic $action): RedirectResponse
    {
        $action->handle($clinic);

        return to_route('clinics.index')->with('success', 'Clinic deleted successfully.');
    }
}
