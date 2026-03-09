<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCurrency;
use App\Actions\DeleteCurrency;
use App\Actions\UpdateCurrency;
use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Http\Requests\DeleteCurrencyRequest;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CurrencyController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $currencies = Currency::query()
            ->when(
                $search !== '',
                static fn(Builder $query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('currency/index', [
            'currencies' => $currencies,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('currency/create');
    }

    public function store(StoreCurrencyRequest $request, CreateCurrency $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('currencies.index')->with('success', 'Currency created successfully.');
    }

    public function edit(Currency $currency): Response
    {
        return Inertia::render('currency/edit', [
            'currency' => $currency,
        ]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency, UpdateCurrency $action): RedirectResponse
    {
        $action->handle($currency, $request->validated());

        return to_route('currencies.index')->with('success', 'Currency updated successfully.');
    }

    public function destroy(DeleteCurrencyRequest $request, Currency $currency, DeleteCurrency $action): RedirectResponse
    {
        if ($action->handle($currency)) {
            return to_route('currencies.index')->with('success', 'Currency deleted successfully.');
        }

        return back()->with('error', 'This currency cannot be deleted.');
    }
}
