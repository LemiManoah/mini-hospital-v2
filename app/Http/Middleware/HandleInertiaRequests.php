<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\FacilityBranch;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $activeBranch = null;
        $activeBranchModel = BranchContext::getActiveBranch($request->user());

        if ($activeBranchModel instanceof FacilityBranch) {
            $activeBranch = [
                'id' => $activeBranchModel->id,
                'name' => $activeBranchModel->name,
                'branch_code' => $activeBranchModel->branch_code,
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'tenant' => $request->user()->tenant?->loadMissing([
                        'subscriptionPackage',
                        'currentSubscription.subscriptionPackage',
                    ]),
                    'active_branch_id' => BranchContext::getActiveBranchId(),
                    'active_branch' => $activeBranch,
                    'can' => $request->user()->getAllPermissions()->pluck('name')->mapWithKeys(fn ($p): array => [$p => true]),
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
