<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCountry;
use App\Actions\DeleteCountry;
use App\Actions\UpdateCountry;
use App\Http\Requests\DeleteCountryRequest;
use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CountryController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $countries = Country::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('country_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('country_code', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('country/index', [
            'countries' => $countries,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('country/create');
    }

    public function store(StoreCountryRequest $request, CreateCountry $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('countries.index')->with('success', 'Country created successfully.');
    }

    public function edit(Country $country): Response
    {
        return Inertia::render('country/edit', [
            'country' => $country,
        ]);
    }

    public function update(UpdateCountryRequest $request, Country $country, UpdateCountry $action): RedirectResponse
    {
        $action->handle($country, $request->validated());

        return to_route('countries.index')->with('success', 'Country updated successfully.');
    }

    public function destroy(DeleteCountryRequest $request, Country $country, DeleteCountry $action): RedirectResponse
    {
        $action->handle($country);

        return to_route('countries.index')->with('success', 'Country deleted successfully.');
    }
}
