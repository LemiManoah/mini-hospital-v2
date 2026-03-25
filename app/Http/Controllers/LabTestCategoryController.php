<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DeleteLabTestCategoryRequest;
use App\Http\Requests\StoreLabTestCategoryRequest;
use App\Http\Requests\UpdateLabTestCategoryRequest;
use App\Models\LabTestCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LabTestCategoryController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_test_categories.view', only: ['index']),
            new Middleware('permission:lab_test_categories.create', only: ['create', 'store']),
            new Middleware('permission:lab_test_categories.update', only: ['edit', 'update']),
            new Middleware('permission:lab_test_categories.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $categories = LabTestCategory::query()
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $search)))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('lab-test-category/index', [
            'categories' => $categories,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('lab-test-category/create');
    }

    public function store(StoreLabTestCategoryRequest $request): RedirectResponse
    {
        DB::transaction(static fn (): LabTestCategory => LabTestCategory::query()->create($request->validated()));

        return to_route('lab-test-categories.index')->with('success', 'Lab test category created successfully.');
    }

    public function edit(LabTestCategory $labTestCategory): RedirectResponse|Response
    {
        if ($labTestCategory->tenant_id === null) {
            return to_route('lab-test-categories.index')->with('error', 'Default lab test categories cannot be edited.');
        }

        return Inertia::render('lab-test-category/edit', [
            'category' => $labTestCategory,
        ]);
    }

    public function update(
        UpdateLabTestCategoryRequest $request,
        LabTestCategory $labTestCategory,
    ): RedirectResponse {
        $this->ensureMutable($labTestCategory, 'Default lab test categories cannot be edited.');

        DB::transaction(static fn (): bool => $labTestCategory->update($request->validated()));

        return to_route('lab-test-categories.index')->with('success', 'Lab test category updated successfully.');
    }

    public function destroy(
        DeleteLabTestCategoryRequest $request,
        LabTestCategory $labTestCategory,
    ): RedirectResponse {
        try {
            $this->ensureMutable($labTestCategory, 'Default lab test categories cannot be deleted.', key: 'delete');

            if ($labTestCategory->labTests()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This lab test category cannot be deleted because it is already used by lab tests.',
                ]);
            }

            DB::transaction(static fn () => $labTestCategory->delete());
        } catch (ValidationException $validationException) {
            return to_route('lab-test-categories.index')
                ->with('error', $validationException->validator->errors()->first() ?: 'This lab test category could not be deleted.');
        }

        return to_route('lab-test-categories.index')->with('success', 'Lab test category deleted successfully.');
    }

    private function ensureMutable(LabTestCategory $labTestCategory, string $message, string $key = 'name'): void
    {
        if ($labTestCategory->tenant_id === null) {
            throw ValidationException::withMessages([$key => $message]);
        }
    }
}
