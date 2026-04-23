<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\ImpersonationContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        $user = $request->user();
        $activeBranchModel = BranchContext::getActiveBranch($user);

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
            'flash' => [
                'success' => fn (): ?string => $this->sessionString($request, 'success'),
                'error' => fn (): ?string => $this->sessionString($request, 'error'),
                'info' => fn (): ?string => $this->sessionString($request, 'info'),
                'warning' => fn (): ?string => $this->sessionString($request, 'warning'),
                'reconciliationPrompt' => fn (): ?string => $this->sessionString($request, 'reconciliation_prompt'),
            ],
            'auth' => [
                'user' => $this->sharedUser($user, $activeBranch),
            ],
            'impersonation' => $this->sharedImpersonation($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @param  array{id: string, name: string, branch_code: string}|null  $activeBranch
     * @return array<string, mixed>|null
     */
    private function sharedUser(?User $user, ?array $activeBranch): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        $relations = [
            'tenant' => static function (mixed $query): void {
                if (! $query instanceof BelongsTo) {
                    return;
                }

                $query
                    ->select('tenants.id', 'tenants.name', 'tenants.onboarding_completed_at')
                    ->with([
                        'currentSubscription' => static function (mixed $subscriptionQuery): void {
                            if (! $subscriptionQuery instanceof HasOne) {
                                return;
                            }

                            $subscriptionQuery
                                ->select(
                                    'tenant_subscriptions.id',
                                    'tenant_subscriptions.tenant_id',
                                    'tenant_subscriptions.status',
                                    'tenant_subscriptions.trial_ends_at',
                                    'tenant_subscriptions.subscription_package_id',
                                )
                                ->with([
                                    'subscriptionPackage' => static function (mixed $packageQuery): void {
                                        if (! $packageQuery instanceof BelongsTo) {
                                            return;
                                        }

                                        $packageQuery->select(
                                            'subscription_packages.id',
                                            'subscription_packages.name',
                                            'subscription_packages.price',
                                        );
                                    },
                                ]);
                        },
                    ]);
            },
        ];

        if ($user->staffId() !== null) {
            $relations['staff'] = static function (mixed $query): void {
                if (! $query instanceof BelongsTo) {
                    return;
                }

                $query->select('staff.id', 'staff.first_name', 'staff.last_name');
            };
        }

        $user->loadMissing($relations);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at,
            'is_support' => $user->isSupportUser(),
            'tenant' => $this->sharedTenant($user),
            'active_branch' => $activeBranch,
            'can' => collect(
                $user->getAllPermissions()
                    ->pluck('name')
                    ->filter(static fn (mixed $permission): bool => is_string($permission) && $permission !== '')
                    ->values()
                    ->all(),
            )->mapWithKeys(static fn (string $permission): array => [$permission => true]),
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sharedTenant(User $user): ?array
    {
        $tenant = $user->tenant;

        if ($tenant === null) {
            return null;
        }

        $currentSubscription = $tenant->currentSubscription;

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'current_subscription' => $currentSubscription ? [
                'id' => $currentSubscription->id,
                'status' => $currentSubscription->status,
                'trial_ends_at' => $currentSubscription->trial_ends_at,
                'subscription_package' => $currentSubscription->subscriptionPackage ? [
                    'id' => $currentSubscription->subscriptionPackage->id,
                    'name' => $currentSubscription->subscriptionPackage->name,
                    'price' => $currentSubscription->subscriptionPackage->price,
                ] : null,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sharedImpersonation(Request $request): ?array
    {
        if (! ImpersonationContext::isActive($request)) {
            return null;
        }

        $realUser = ImpersonationContext::realUser($request);
        $targetUser = ImpersonationContext::targetUser($request);

        if (! $realUser instanceof User || ! $targetUser instanceof User) {
            return null;
        }

        $realUser->loadMissing('tenant');
        $targetUser->loadMissing('tenant');

        return [
            'active' => true,
            'started_at' => ImpersonationContext::startedAt($request),
            'real_user' => [
                'id' => $realUser->id,
                'name' => $realUser->name,
                'email' => $realUser->email,
            ],
            'target_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'tenant_name' => $targetUser->tenant?->name,
            ],
        ];
    }

    private function sessionString(Request $request, string $key): ?string
    {
        $value = $request->session()->get($key);

        return is_string($value) ? $value : null;
    }
}
