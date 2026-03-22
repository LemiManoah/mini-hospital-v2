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
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ClinicController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

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

        $clinics = $this->activeBranchWorkspace->apply(Clinic::query(), 'branch_id')
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
            'branches' => FacilityBranch::query()
                ->whereKey(BranchContext::getActiveBranchId())
                ->get(),
            'departments' => Department::all(),
        ]);
    }

    public function store(StoreClinicRequest $request, CreateClinic $action): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['branch_id'] ?? null) !== BranchContext::getActiveBranchId()) {
            return back()->with('error', 'You can only create clinics in the active branch.');
        }

        $action->handle($validated);

        return to_route('clinics.index')->with('success', 'Clinic created successfully.');
    }

    public function edit(Clinic $clinic): Response
    {
        $this->activeBranchWorkspace->authorizeModel($clinic, 'branch_id');

        return Inertia::render('clinic/edit', [
            'clinic' => $clinic,
            'branches' => FacilityBranch::query()
                ->whereKey(BranchContext::getActiveBranchId())
                ->get(),
            'departments' => Department::all(),
        ]);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic, UpdateClinic $action): RedirectResponse
    {
        $this->activeBranchWorkspace->authorizeModel($clinic, 'branch_id');

        $validated = $request->validated();

        if (($validated['branch_id'] ?? null) !== BranchContext::getActiveBranchId()) {
            return back()->with('error', 'You can only assign clinics to the active branch.');
        }

        $action->handle($clinic, $validated);

        return to_route('clinics.index')->with('success', 'Clinic updated successfully.');
    }

    public function destroy(DeleteClinicRequest $request, Clinic $clinic, DeleteClinic $action): RedirectResponse
    {
        $this->activeBranchWorkspace->authorizeModel($clinic, 'branch_id');

        $action->handle($clinic);

        return to_route('clinics.index')->with('success', 'Clinic deleted successfully.');
    }
}
