<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BranchSwitcherController
{
    public function index(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->tenant_id === null) {
            return to_route('facility-switcher.index');
        }

        $branches = BranchContext::getAccessibleBranches($user)
            ->map(fn ($branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'branch_code' => $branch->branch_code,
                'is_main_branch' => $branch->is_main_branch,
                'status' => is_string($branch->status) ? $branch->status : $branch->status->value,
            ])
            ->values();

        return Inertia::render('branch-switcher/index', [
            'branches' => $branches,
            'activeBranchId' => BranchContext::getActiveBranchId(),
        ]);
    }

    public function switch(Request $request, string $branchId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(BranchContext::canAccessBranch($user, $branchId), 403, 'You are not allowed to switch to this branch.');

        BranchContext::setActiveBranchId($branchId);

        $request->session()->regenerate();
        $request->session()->flash('success', 'Branch switched successfully.');

        return to_route('dashboard');
    }
}
