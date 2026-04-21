<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InventoryLocationType;
use App\Enums\Priority;
use App\Models\InventoryLocation;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryWorkspace;
use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreInventoryRequisitionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_inventory_location_id' => [
                'required',
                'string',
                Rule::exists('inventory_locations', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'destination_inventory_location_id' => [
                'required',
                'string',
                Rule::exists('inventory_locations', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'priority' => ['required', Rule::enum(Priority::class)],
            'requisition_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'items.*.requested_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $activeBranchId = BranchContext::getActiveBranchId();
                $fulfillingLocationId = $this->input('source_inventory_location_id');
                $requestingLocationId = $this->input('destination_inventory_location_id');

                if (
                    ! is_string($activeBranchId)
                    || $activeBranchId === ''
                    || ! is_string($fulfillingLocationId)
                    || ! is_string($requestingLocationId)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                if ($fulfillingLocationId === $requestingLocationId) {
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'The requesting location must be different from the fulfilling location.',
                    );
                }

                $locations = InventoryLocation::query()
                    ->whereIn('id', [$fulfillingLocationId, $requestingLocationId])
                    ->where('branch_id', $activeBranchId)
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('id');

                if (! $locations->has($fulfillingLocationId)) {
                    $validator->errors()->add(
                        'source_inventory_location_id',
                        'The fulfilling location must be active in the current branch.',
                    );
                }

                if (! $locations->has($requestingLocationId)) {
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'The requesting location must be active in the current branch.',
                    );
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $inventoryLocationAccess = resolve(InventoryLocationAccess::class);
                $workspace = InventoryWorkspace::fromRequest($this);
                $workspaceTypes = $workspace->locationTypeValues();

                $canCreate = $inventoryLocationAccess->canCreateRequestedRequisition(
                    $this->user(),
                    $fulfillingLocationId,
                    $requestingLocationId,
                    $workspaceTypes,
                    $activeBranchId,
                );

                if (! $canCreate) {
                    $validator->errors()->add(
                        'source_inventory_location_id',
                        'You can only request stock from a fulfilling location you are allowed to use.',
                    );
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'You can only request stock for requesting locations you manage.',
                    );
                }

                if ($workspaceTypes === []) {
                    return;
                }

                $fulfillingLocation = $locations->get($fulfillingLocationId);
                $requestingLocation = $locations->get($requestingLocationId);

                if ($fulfillingLocation instanceof InventoryLocation && $fulfillingLocation->type !== InventoryLocationType::MAIN_STORE) {
                    $validator->errors()->add(
                        'source_inventory_location_id',
                        'Dedicated lab and pharmacy requisitions must be fulfilled from a main store location.',
                    );
                }

                if (
                    $requestingLocation instanceof InventoryLocation
                    && ! in_array($requestingLocation->type->value, $workspaceTypes, true)
                ) {
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'The requesting location does not belong to this workspace.',
                    );
                }
            },
        ];
    }
}


