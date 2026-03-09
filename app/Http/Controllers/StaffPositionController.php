<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStaffPosition;
use App\Actions\DeleteStaffPosition;
use App\Actions\UpdateStaffPosition;
use App\Http\Requests\DeleteStaffPositionRequest;
use App\Http\Requests\StoreStaffPositionRequest;
use App\Http\Requests\UpdateStaffPositionRequest;
use App\Models\StaffPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StaffPositionController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $positions = StaffPosition::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('staff-position/index', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('staff-position/create');
    }

    public function store(StoreStaffPositionRequest $request, CreateStaffPosition $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('staff-positions.index')->with('success', 'Staff position created successfully.');
    }

    public function edit(StaffPosition $staffPosition): Response
    {
        return Inertia::render('staff-position/edit', [
            'position' => $staffPosition,
        ]);
    }

    public function update(UpdateStaffPositionRequest $request, StaffPosition $staffPosition, UpdateStaffPosition $action): RedirectResponse
    {
        $action->handle($staffPosition, $request->validated());

        return to_route('staff-positions.index')->with('success', 'Staff position updated successfully.');
    }

    public function destroy(DeleteStaffPositionRequest $request, StaffPosition $staffPosition, DeleteStaffPosition $action): RedirectResponse
    {
        $action->handle($staffPosition);

        return to_route('staff-positions.index')->with('success', 'Staff position deleted successfully.');
    }
}
