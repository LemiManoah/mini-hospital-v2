<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSpecimenTypeRequest;
use App\Http\Requests\StoreSpecimenTypeRequest;
use App\Http\Requests\UpdateSpecimenTypeRequest;
use App\Models\SpecimenType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SpecimenTypeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:specimen_types.view', only: ['index']),
            new Middleware('permission:specimen_types.create', only: ['create', 'store']),
            new Middleware('permission:specimen_types.update', only: ['edit', 'update']),
            new Middleware('permission:specimen_types.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $specimenTypes = SpecimenType::query()
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $search)))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('specimen-type/index', [
            'specimenTypes' => $specimenTypes,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('specimen-type/create');
    }

    public function store(StoreSpecimenTypeRequest $request): RedirectResponse
    {
        DB::transaction(static fn (): SpecimenType => SpecimenType::query()->create($request->validated()));

        return to_route('specimen-types.index')->with('success', 'Specimen type created successfully.');
    }

    public function edit(SpecimenType $specimenType): RedirectResponse|Response
    {
        if ($specimenType->tenant_id === null) {
            return to_route('specimen-types.index')->with('error', 'Default specimen types cannot be edited.');
        }

        return Inertia::render('specimen-type/edit', [
            'specimenType' => $specimenType,
        ]);
    }

    public function update(
        UpdateSpecimenTypeRequest $request,
        SpecimenType $specimenType,
    ): RedirectResponse {
        $this->ensureMutable($specimenType, 'Default specimen types cannot be edited.');

        DB::transaction(static fn (): bool => $specimenType->update($request->validated()));

        return to_route('specimen-types.index')->with('success', 'Specimen type updated successfully.');
    }

    public function destroy(
        DeleteSpecimenTypeRequest $request,
        SpecimenType $specimenType,
    ): RedirectResponse {
        try {
            $this->ensureMutable($specimenType, 'Default specimen types cannot be deleted.', key: 'delete');

            if ($specimenType->labTests()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This specimen type cannot be deleted because it is already used by lab tests.',
                ]);
            }

            DB::transaction(static fn () => $specimenType->delete());
        } catch (ValidationException $validationException) {
            return to_route('specimen-types.index')
                ->with('error', $validationException->validator->errors()->first() ?: 'This specimen type could not be deleted.');
        }

        return to_route('specimen-types.index')->with('success', 'Specimen type deleted successfully.');
    }

    private function ensureMutable(SpecimenType $specimenType, string $message, string $key = 'name'): void
    {
        if ($specimenType->tenant_id === null) {
            throw ValidationException::withMessages([$key => $message]);
        }
    }
}
