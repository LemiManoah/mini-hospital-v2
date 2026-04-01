<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAllergen;
use App\Actions\DeleteAllergen;
use App\Actions\UpdateAllergen;
use App\Enums\AllergyType;
use App\Http\Requests\DeleteAllergenRequest;
use App\Http\Requests\StoreAllergenRequest;
use App\Http\Requests\UpdateAllergenRequest;
use App\Models\Allergen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AllergenController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:allergens.view', only: ['index']),
            new Middleware('permission:allergens.create', only: ['create', 'store']),
            new Middleware('permission:allergens.update', only: ['edit', 'update']),
            new Middleware('permission:allergens.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $allergens = Allergen::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
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
        return Inertia::render('allergen/create', [
            'allergyTypes' => $this->allergenOptions(),
        ]);
    }

    public function store(StoreAllergenRequest $request, CreateAllergen $action): RedirectResponse
    {
        $action->handle($request->validated());

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Allergen created successfully.');
        }

        return to_route('allergens.index')->with('success', 'Allergen created successfully.');
    }

    private function allergenOptions(): array
    {
        return collect(AllergyType::cases())->map(fn (AllergyType $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->values()->all();
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
