<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDrug;
use App\Actions\DeleteDrug;
use App\Actions\UpdateDrug;
use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Http\Requests\DeleteDrugRequest;
use App\Http\Requests\StoreDrugRequest;
use App\Http\Requests\UpdateDrugRequest;
use App\Models\Drug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DrugController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:drugs.view', only: ['index']),
            new Middleware('permission:drugs.create', only: ['create', 'store']),
            new Middleware('permission:drugs.update', only: ['edit', 'update']),
            new Middleware('permission:drugs.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $drugs = Drug::query()
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('generic_name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('brand_name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('drug_code', 'like', sprintf('%%%s%%', $search)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('drug/index', [
            'drugs' => $drugs,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('drug/create', $this->formOptions());
    }

    public function store(StoreDrugRequest $request, CreateDrug $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('drugs.index')->with('success', 'Drug created successfully.');
    }

    public function edit(Drug $drug): Response
    {
        return Inertia::render('drug/edit', [
            'drug' => $drug,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateDrugRequest $request, Drug $drug, UpdateDrug $action): RedirectResponse
    {
        $action->handle($drug, $request->validated());

        return to_route('drugs.index')->with('success', 'Drug updated successfully.');
    }

    public function destroy(DeleteDrugRequest $request, Drug $drug, DeleteDrug $action): RedirectResponse
    {
        $action->handle($drug);

        return to_route('drugs.index')->with('success', 'Drug deleted successfully.');
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function formOptions(): array
    {
        return [
            'categories' => collect(DrugCategory::cases())->map(static fn (DrugCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ])->all(),
            'dosageForms' => collect(DrugDosageForm::cases())->map(static fn (DrugDosageForm $form): array => [
                'value' => $form->value,
                'label' => $form->label(),
            ])->all(),
        ];
    }
}
