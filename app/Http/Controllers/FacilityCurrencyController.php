<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EnsureFacilityBranchDefaultCurrency;
use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use App\Models\FacilityBranch;
use App\Models\FacilityBranchCurrency;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilityCurrencyController implements HasMiddleware
{
    public function __construct(
        private EnsureFacilityBranchDefaultCurrency $ensureDefaultCurrency,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:currencies.view', only: ['index']),
            new Middleware('permission:currencies.update', only: ['updateMultiCurrency', 'storeCurrency', 'destroyCurrency']),
        ];
    }

    public function index(Request $request): Response
    {
        $branch = $this->activeBranch($request);

        $this->ensureDefaultCurrency->handle($branch);

        $branch->load([
            'currency:id,code,name,symbol,decimal_places',
            'supportedCurrencies' => static fn (BelongsToMany $query): BelongsToMany => $query->orderBy('code'),
        ]);

        $rates = CurrencyExchangeRate::query()
            ->where('tenant_id', $branch->tenant_id)
            ->where('facility_branch_id', $branch->id)
            ->with([
                'fromCurrency:id,code,name,symbol',
                'toCurrency:id,code,name,symbol',
            ])
            ->latest('effective_date')
            ->orderBy('from_currency_id')
            ->get();

        return Inertia::render('administration/currencies', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'multi_currency_enabled' => (bool) $branch->multi_currency_enabled,
            ],
            'defaultCurrency' => $branch->currency,
            'selectedCurrencies' => $branch->supportedCurrencies,
            'availableCurrencies' => Currency::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'symbol', 'decimal_places', 'symbol_position', 'modifiable']),
            'rates' => $rates,
        ]);
    }

    public function updateMultiCurrency(Request $request): RedirectResponse
    {
        $branch = $this->activeBranch($request);

        /** @var array{multi_currency_enabled: bool} $validated */
        $validated = $request->validate([
            'multi_currency_enabled' => ['required', 'boolean'],
        ]);

        $branch->forceFill([
            'multi_currency_enabled' => (bool) $validated['multi_currency_enabled'],
            'updated_by' => Auth::id(),
        ])->save();

        $this->ensureDefaultCurrency->handle($branch);

        return to_route('administration.currencies.index')
            ->with('success', 'Currency settings updated successfully.');
    }

    public function storeCurrency(Request $request): RedirectResponse
    {
        $branch = $this->activeBranch($request);
        $tenantId = $branch->getAttribute('tenant_id');

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);

        if (! $branch->multi_currency_enabled) {
            return to_route('administration.currencies.index')
                ->with('error', 'Enable multi-currency before adding accepted currencies.');
        }

        /** @var array{currency_id: string} $validated */
        $validated = $request->validate([
            'currency_id' => [
                'required',
                'uuid',
                'exists:currencies,id',
                Rule::unique('facility_branch_currencies')->where('facility_branch_id', $branch->id),
            ],
        ]);

        FacilityBranchCurrency::query()->create([
            'tenant_id' => $tenantId,
            'facility_branch_id' => $branch->id,
            'currency_id' => $validated['currency_id'],
            'is_default' => $validated['currency_id'] === $branch->currency_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return to_route('administration.currencies.index')
            ->with('success', 'Currency added to this branch.');
    }

    public function destroyCurrency(Request $request, Currency $currency): RedirectResponse
    {
        $branch = $this->activeBranch($request);

        if ($currency->id === $branch->currency_id) {
            return to_route('administration.currencies.index')
                ->with('error', 'The branch base currency cannot be removed.');
        }

        DB::transaction(function () use ($branch, $currency): void {
            FacilityBranchCurrency::query()
                ->where('facility_branch_id', $branch->id)
                ->where('currency_id', $currency->id)
                ->delete();

            CurrencyExchangeRate::query()
                ->where('facility_branch_id', $branch->id)
                ->where(function (Builder $query) use ($currency): void {
                    $query
                        ->where('from_currency_id', $currency->id)
                        ->orWhere('to_currency_id', $currency->id);
                })
                ->delete();
        });

        return to_route('administration.currencies.index')
            ->with('success', 'Currency removed from this branch.');
    }

    private function activeBranch(Request $request): FacilityBranch
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $branch = BranchContext::getActiveBranch($user);

        abort_unless($branch instanceof FacilityBranch, 403, 'Select an active branch before managing currencies.');

        return $branch;
    }
}
