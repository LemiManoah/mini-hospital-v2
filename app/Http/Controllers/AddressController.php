<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAddress;
use App\Actions\DeleteAddress;
use App\Actions\UpdateAddress;
use App\Http\Requests\DeleteAddressRequest;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AddressController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $addresses = Address::query()
            ->with('country')
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('city', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('district', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('state', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('address/index', [
            'addresses' => $addresses,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('address/create', [
            'countries' => Country::all(),
        ]);
    }

    public function store(StoreAddressRequest $request, CreateAddress $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('addresses.index')->with('success', 'Address created successfully.');
    }

    public function edit(Address $address): Response
    {
        return Inertia::render('address/edit', [
            'address' => $address,
            'countries' => Country::all(),
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address, UpdateAddress $action): RedirectResponse
    {
        $action->handle($address, $request->validated());

        return to_route('addresses.index')->with('success', 'Address updated successfully.');
    }

    public function destroy(DeleteAddressRequest $request, Address $address, DeleteAddress $action): RedirectResponse
    {
        $action->handle($address);

        return to_route('addresses.index')->with('success', 'Address deleted successfully.');
    }
}
