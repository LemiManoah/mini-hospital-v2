<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAppointmentMode;
use App\Actions\DeleteAppointmentMode;
use App\Actions\UpdateAppointmentMode;
use App\Http\Requests\DeleteAppointmentModeRequest;
use App\Http\Requests\StoreAppointmentModeRequest;
use App\Http\Requests\UpdateAppointmentModeRequest;
use App\Models\AppointmentMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AppointmentModeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:appointment_modes.view', only: ['index']),
            new Middleware('permission:appointment_modes.create', only: ['create', 'store']),
            new Middleware('permission:appointment_modes.update', only: ['edit', 'update']),
            new Middleware('permission:appointment_modes.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $appointmentModes = AppointmentMode::query()
            ->when($search !== '', static fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $search)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('appointment-mode/index', [
            'appointmentModes' => $appointmentModes,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('appointment-mode/create');
    }

    public function store(StoreAppointmentModeRequest $request, CreateAppointmentMode $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('appointment-modes.index')->with('success', 'Appointment mode created successfully.');
    }

    public function edit(AppointmentMode $appointmentMode): Response
    {
        return Inertia::render('appointment-mode/edit', [
            'appointmentMode' => $appointmentMode,
        ]);
    }

    public function update(
        UpdateAppointmentModeRequest $request,
        AppointmentMode $appointmentMode,
        UpdateAppointmentMode $action,
    ): RedirectResponse {
        $action->handle($appointmentMode, $request->validated());

        return to_route('appointment-modes.index')->with('success', 'Appointment mode updated successfully.');
    }

    public function destroy(
        DeleteAppointmentModeRequest $request,
        AppointmentMode $appointmentMode,
        DeleteAppointmentMode $action,
    ): RedirectResponse {
        $action->handle($appointmentMode);

        return to_route('appointment-modes.index')->with('success', 'Appointment mode deleted successfully.');
    }
}
