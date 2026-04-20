<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyExchangeRateRequest;
use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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

    public function index(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id;

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);

        $rates = CurrencyExchangeRate::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'fromCurrency:id,code,name,symbol',
                'toCurrency:id,code,name,symbol',
            ])
            ->orderByDesc('effective_date')
            ->orderBy('from_currency_id')
            ->get();

        $currencies = Currency::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'symbol', 'decimal_places']);

        return Inertia::render('currency/exchange-rates', [
            'rates' => $rates,
            'currencies' => $currencies,
        ]);
    }

    public function store(StoreCurrencyExchangeRateRequest $request): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);

        $userId = Auth::id();
        $validated = $request->validated();

        CurrencyExchangeRate::query()->create([
            'tenant_id' => $tenantId,
            'from_currency_id' => $validated['from_currency_id'],
            'to_currency_id' => $validated['to_currency_id'],
            'rate' => $validated['rate'],
            'effective_date' => $validated['effective_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return to_route('currency-exchange-rates.index')
            ->with('success', 'Exchange rate added successfully.');
    }

    public function destroy(Request $request, CurrencyExchangeRate $currencyExchangeRate): RedirectResponse
    {
        abort_unless($request->user()?->can('currency_exchange_rates.delete'), 403);

        $tenantId = $request->user()?->tenant_id;

        abort_unless($currencyExchangeRate->tenant_id === $tenantId, 403);

        $currencyExchangeRate->delete();

        return to_route('currency-exchange-rates.index')
            ->with('success', 'Exchange rate removed.');
    }
}
