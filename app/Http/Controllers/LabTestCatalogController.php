<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateLabTestCatalog;
use App\Actions\DeleteLabTestCatalog;
use App\Actions\UpdateLabTestCatalog;
use App\Http\Requests\DeleteLabTestCatalogRequest;
use App\Http\Requests\StoreLabTestCatalogRequest;
use App\Http\Requests\UpdateLabTestCatalogRequest;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\SpecimenType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LabTestCatalogController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_test_catalogs.view', only: ['index']),
            new Middleware('permission:lab_test_catalogs.create', only: ['create', 'store']),
            new Middleware('permission:lab_test_catalogs.update', only: ['edit', 'update']),
            new Middleware('permission:lab_test_catalogs.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $labTests = LabTestCatalog::query()
            ->with([
                'labCategory:id,name',
                'specimenTypes:id,name',
                'resultTypeDefinition:id,code,name',
            ])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('test_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('test_code', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('labCategory', static fn (Builder $relationQuery) => $relationQuery
                            ->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('specimenTypes', static fn (Builder $relationQuery) => $relationQuery
                            ->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('resultTypeDefinition', static fn (Builder $relationQuery) => $relationQuery
                            ->where('name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('code', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('lab-test-catalog/index', [
            'labTests' => $labTests,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('lab-test-catalog/create', $this->formOptions());
    }

    public function store(StoreLabTestCatalogRequest $request, CreateLabTestCatalog $action): RedirectResponse
    {
        $action->handle($request->createDto());

        return to_route('lab-test-catalogs.index')->with('success', 'Lab test created successfully.');
    }

    public function edit(LabTestCatalog $labTestCatalog): Response
    {
        return Inertia::render('lab-test-catalog/edit', [
            'labTestCatalog' => $labTestCatalog->load([
                'labCategory:id,name',
                'specimenTypes:id,name',
                'resultTypeDefinition:id,code,name',
                'resultOptions:id,lab_test_catalog_id,label,sort_order,is_active',
                'resultParameters:id,lab_test_catalog_id,label,unit,reference_range,value_type,sort_order,is_active',
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function update(
        UpdateLabTestCatalogRequest $request,
        LabTestCatalog $labTestCatalog,
        UpdateLabTestCatalog $action,
    ): RedirectResponse {
        $action->handle($labTestCatalog, $request->updateDto());

        return to_route('lab-test-catalogs.index')->with('success', 'Lab test updated successfully.');
    }

    public function destroy(
        DeleteLabTestCatalogRequest $request,
        LabTestCatalog $labTestCatalog,
        DeleteLabTestCatalog $action,
    ): RedirectResponse {
        try {
            $action->handle($labTestCatalog);
        } catch (ValidationException $validationException) {
            return to_route('lab-test-catalogs.index')
                ->with('error', $validationException->validator->errors()->first() ?: 'This lab test could not be deleted.');
        }

        return to_route('lab-test-catalogs.index')->with('success', 'Lab test deleted successfully.');
    }

    /**
     * @return array{
     *     categories: array<int, array{value: string, label: string}>,
     *     specimenTypes: array<int, array{value: string, label: string}>,
     *     resultTypes: array<int, array{value: string, label: string, code: string, description: ?string}>
     * }
     */
    private function formOptions(): array
    {
        return [
            'categories' => LabTestCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (LabTestCategory $category): array => [
                    'value' => $category->id,
                    'label' => $category->name,
                ])
                ->all(),
            'specimenTypes' => SpecimenType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (SpecimenType $specimenType): array => [
                    'value' => $specimenType->id,
                    'label' => $specimenType->name,
                ])
                ->all(),
            'resultTypes' => LabResultType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'description'])
                ->map(static fn (LabResultType $resultType): array => [
                    'value' => $resultType->id,
                    'label' => $resultType->name,
                    'code' => $resultType->code,
                    'description' => $resultType->description,
                ])
                ->all(),
        ];
    }
}
