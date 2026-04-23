<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BranchSwitcherController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:facility_branches.view', only: ['index']),
            new Middleware('permission:facility_branches.update', only: ['switch']),
        ];
    }

    public function index(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->tenant_id === null) {
            return to_route('facility-manager.dashboard');
        }

        Gate::authorize('viewAny', FacilityBranch::class);

        $branches = BranchContext::getAccessibleBranches($user)
            ->map(fn (FacilityBranch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'branch_code' => $branch->branch_code,
                'is_main_branch' => $branch->is_main_branch,
                'status' => $branch->status->value,
            ])
            ->values();

        return Inertia::render('branch-switcher/index', [
            'branches' => $branches,
            'activeBranchId' => BranchContext::getActiveBranchId(),
        ]);
    }

    public function switch(Request $request, string $branchId): RedirectResponse
    {
        $request->user();
        $branch = FacilityBranch::query()->findOrFail($branchId);

        Gate::authorize('switchTo', $branch);

        BranchContext::setActiveBranchId($branchId);

        $request->session()->regenerate();
        $request->session()->flash('success', 'Branch switched successfully.');

        return to_route('dashboard');
    }
}
