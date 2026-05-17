<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyExchangeRateRequest;
use App\Models\CurrencyExchangeRate;
use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

final readonly class CurrencyExchangeRateController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:currency_exchange_rates.view', only: ['index']),
            new Middleware('permission:currency_exchange_rates.create', only: ['store']),
            new Middleware('permission:currency_exchange_rates.delete', only: ['destroy']),
        ];
    }

    public function index(): RedirectResponse
    {
        return to_route('administration.currencies.index');
    }

    public function store(StoreCurrencyExchangeRateRequest $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $tenantId = $user?->tenant_id;

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);
        $branch = BranchContext::getActiveBranch($user);

        abort_unless($branch instanceof FacilityBranch, 403);

        $userId = Auth::id();
        $validated = $request->validated();

        CurrencyExchangeRate::query()->create([
            'tenant_id' => $tenantId,
            'facility_branch_id' => $branch->id,
            'from_currency_id' => $validated['from_currency_id'],
            'to_currency_id' => $validated['to_currency_id'],
            'rate' => $validated['rate'],
            'effective_date' => $validated['effective_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return to_route('administration.currencies.index')
            ->with('success', 'Exchange rate added successfully.');
    }

    public function destroy(Request $request, CurrencyExchangeRate $currencyExchangeRate): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user instanceof User && $user->can('currency_exchange_rates.delete'), 403);

        $tenantId = $user->tenant_id;
        $branch = BranchContext::getActiveBranch($user);

        abort_unless(
            $branch instanceof FacilityBranch
                && $currencyExchangeRate->tenant_id === $tenantId
                && $currencyExchangeRate->facility_branch_id === $branch->id,
            403,
        );

        $currencyExchangeRate->delete();

        return to_route('administration.currencies.index')
            ->with('success', 'Exchange rate removed.');
    }
}
