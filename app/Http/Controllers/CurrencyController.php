<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCurrency;
use App\Actions\DeleteCurrency;
use App\Actions\UpdateCurrency;
use App\Http\Requests\DeleteCurrencyRequest;
use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CurrencyController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:currencies.view', only: ['index']),
            new Middleware('permission:currencies.create', only: ['create', 'store']),
            new Middleware('permission:currencies.update', only: ['edit', 'update']),
            new Middleware('permission:currencies.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $currencies = Currency::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('code', 'like', sprintf('%%%s%%', $search))
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
