<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAllergen;
use App\Actions\DeleteAllergen;
use App\Actions\UpdateAllergen;
use App\Http\Requests\StoreAllergenRequest;
use App\Http\Requests\UpdateAllergenRequest;
use App\Http\Requests\DeleteAllergenRequest;
use App\Models\Allergen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AllergenController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $allergens = Allergen::query()
            ->when(
                $search !== '',
                static fn($query) => $query->where('name', 'like', "%{$search}%")
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('allergen/index', [
            'allergens' => $allergens,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('allergen/create');
    }

    public function store(StoreAllergenRequest $request, CreateAllergen $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('allergens.index')->with('success', 'Allergen created successfully.');
    }

    public function edit(Allergen $allergen): Response
    {
        return Inertia::render('allergen/edit', [
            'allergen' => $allergen,
        ]);
    }

    public function update(UpdateAllergenRequest $request, Allergen $allergen, UpdateAllergen $action): RedirectResponse
    {
        $action->handle($allergen, $request->validated());

        return to_route('allergens.index')->with('success', 'Allergen updated successfully.');
    }

    public function destroy(DeleteAllergenRequest $request, Allergen $allergen, DeleteAllergen $action): RedirectResponse
    {
        $action->handle($allergen);

        return to_route('allergens.index')->with('success', 'Allergen deleted successfully.');
    }
}
