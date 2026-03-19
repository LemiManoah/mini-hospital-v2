<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateSubscriptionPackage;
use App\Actions\DeleteSubscriptionPackage;
use App\Actions\UpdateSubscriptionPackage;
use App\Http\Requests\DeleteSubscriptionPackageRequest;
use App\Http\Requests\StoreSubscriptionPackageRequest;
use App\Http\Requests\UpdateSubscriptionPackageRequest;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SubscriptionPackageController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:subscription_packages.view', only: ['index']),
            new Middleware('permission:subscription_packages.create', only: ['create', 'store']),
            new Middleware('permission:subscription_packages.update', only: ['edit', 'update']),
            new Middleware('permission:subscription_packages.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $packages = SubscriptionPackage::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('subscription-package/index', [
            'packages' => $packages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('subscription-package/create');
    }

    public function store(StoreSubscriptionPackageRequest $request, CreateSubscriptionPackage $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('subscription-packages.index')->with('success', 'Subscription package created successfully.');
    }

    public function edit(SubscriptionPackage $subscriptionPackage): Response
    {
        return Inertia::render('subscription-package/edit', [
            'package' => $subscriptionPackage,
        ]);
    }

    public function update(UpdateSubscriptionPackageRequest $request, SubscriptionPackage $subscriptionPackage, UpdateSubscriptionPackage $action): RedirectResponse
    {
        $action->handle($subscriptionPackage, $request->validated());

        return to_route('subscription-packages.index')->with('success', 'Subscription package updated successfully.');
    }

    public function destroy(DeleteSubscriptionPackageRequest $request, SubscriptionPackage $subscriptionPackage, DeleteSubscriptionPackage $action): RedirectResponse
    {
        $action->handle($subscriptionPackage);

        return to_route('subscription-packages.index')->with('success', 'Subscription package deleted successfully.');
    }
}
