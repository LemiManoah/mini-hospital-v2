<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUnit;
use App\Actions\DeleteUnit;
use App\Actions\UpdateUnit;
use App\Http\Requests\DeleteUnitRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UnitController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $units = Unit::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('symbol', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('unit/index', [
            'units' => $units,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('unit/create');
    }

    public function store(StoreUnitRequest $request, CreateUnit $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('units.index')->with('success', 'Unit created successfully.');
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('unit/edit', [
            'unit' => $unit,
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit, UpdateUnit $action): RedirectResponse
    {
        $action->handle($unit, $request->validated());

        return to_route('units.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(DeleteUnitRequest $request, Unit $unit, DeleteUnit $action): RedirectResponse
    {
        $action->handle($unit);

        return to_route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
