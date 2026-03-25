<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteLabRequestItemConsumable;
use App\Actions\RecordLabRequestItemConsumable;
use App\Http\Requests\StoreLabRequestItemConsumableRequest;
use App\Models\LabRequestItem;
use App\Models\LabRequestItemConsumable;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class LabRequestItemConsumableController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_requests.update', only: ['store', 'destroy']),
        ];
    }

    public function store(
        StoreLabRequestItemConsumableRequest $request,
        LabRequestItem $labRequestItem,
        RecordLabRequestItemConsumable $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($labRequestItem->request);

        $staffId = $request->user()?->staff_id;

        if ($staffId === null) {
            return to_route('laboratory.request-items.show', $labRequestItem)
                ->with('error', 'Consumable usage requires a linked staff profile for audit tracking.');
        }

        $action->handle($labRequestItem->loadMissing('request'), $request->validated(), $staffId);

        return to_route('laboratory.request-items.show', $labRequestItem)
            ->with('success', 'Consumable usage recorded successfully.');
    }

    public function destroy(
        LabRequestItem $labRequestItem,
        LabRequestItemConsumable $labRequestItemConsumable,
        DeleteLabRequestItemConsumable $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($labRequestItem->request);

        abort_unless($labRequestItemConsumable->lab_request_item_id === $labRequestItem->id, 404);

        $action->handle($labRequestItemConsumable);

        return to_route('laboratory.request-items.show', $labRequestItem)
            ->with('success', 'Consumable usage removed successfully.');
    }
}
