<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DeleteLabResultTypeRequest;
use App\Http\Requests\StoreLabResultTypeRequest;
use App\Http\Requests\UpdateLabResultTypeRequest;
use App\Models\LabResultType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LabResultTypeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:result_types.view', only: ['index']),
            new Middleware('permission:result_types.create', only: ['create', 'store']),
            new Middleware('permission:result_types.update', only: ['edit', 'update']),
            new Middleware('permission:result_types.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $resultTypes = LabResultType::query()
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('code', 'like', sprintf('%%%s%%', $search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $search)))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('result-type/index', [
            'resultTypes' => $resultTypes,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('result-type/create');
    }

    public function store(StoreLabResultTypeRequest $request): RedirectResponse
    {
        DB::transaction(static fn (): LabResultType => LabResultType::query()->create($request->validated()));

        return to_route('result-types.index')->with('success', 'Result type created successfully.');
    }

    public function edit(LabResultType $resultType): RedirectResponse|Response
    {
        if ($resultType->tenant_id === null) {
            return to_route('result-types.index')->with('error', 'Default result types cannot be edited.');
        }

        return Inertia::render('result-type/edit', [
            'resultType' => $resultType,
        ]);
    }

    public function update(
        UpdateLabResultTypeRequest $request,
        LabResultType $resultType,
    ): RedirectResponse {
        $this->ensureMutable($resultType, 'Default result types cannot be edited.');

        DB::transaction(static fn (): bool => $resultType->update($request->validated()));

        return to_route('result-types.index')->with('success', 'Result type updated successfully.');
    }

    public function destroy(
        DeleteLabResultTypeRequest $request,
        LabResultType $resultType,
    ): RedirectResponse {
        try {
            $this->ensureMutable($resultType, 'Default result types cannot be deleted.', key: 'delete');

            if ($resultType->labTests()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This result type cannot be deleted because it is already used by lab tests.',
                ]);
            }

            DB::transaction(static fn () => $resultType->delete());
        } catch (ValidationException $validationException) {
            return to_route('result-types.index')
                ->with('error', $validationException->validator->errors()->first() ?: 'This result type could not be deleted.');
        }

        return to_route('result-types.index')->with('success', 'Result type deleted successfully.');
    }

    private function ensureMutable(LabResultType $resultType, string $message, string $key = 'name'): void
    {
        if ($resultType->tenant_id === null) {
            throw ValidationException::withMessages([$key => $message]);
        }
    }
}
