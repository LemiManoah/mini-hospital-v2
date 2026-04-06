<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\InventoryLocationType;
use App\Models\InventoryLocation;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryWorkspace;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreInventoryRequisitionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
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
            'requisition_date' => ['required', 'date'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                'distinct',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'items.*.requested_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $activeBranchId = BranchContext::getActiveBranchId();
                $sourceLocationId = $this->input('source_inventory_location_id');
                $destinationLocationId = $this->input('destination_inventory_location_id');

                if (
                    ! is_string($activeBranchId)
                    || $activeBranchId === ''
                    || ! is_string($sourceLocationId)
                    || ! is_string($destinationLocationId)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                if ($sourceLocationId === $destinationLocationId) {
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'The destination location must be different from the source location.',
                    );
                }

                $locations = InventoryLocation::query()
                    ->whereIn('id', [$sourceLocationId, $destinationLocationId])
                    ->where('branch_id', $activeBranchId)
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('id');

                if (! $locations->has($sourceLocationId)) {
                    $validator->errors()->add(
                        'source_inventory_location_id',
                        'The source location must be active in the current branch.',
                    );
                }

                if (! $locations->has($destinationLocationId)) {
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'The destination location must be active in the current branch.',
                    );
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $inventoryLocationAccess = resolve(InventoryLocationAccess::class);
                $workspace = InventoryWorkspace::fromRequest($this);
                $workspaceTypes = $workspace->locationTypeValues();

                $canCreate = $workspaceTypes === []
                    ? $inventoryLocationAccess->canCreateRequisition($this->user(), $sourceLocationId, $destinationLocationId, $activeBranchId)
                    : $inventoryLocationAccess->canCreateRequisitionForTypes(
                        $this->user(),
                        $sourceLocationId,
                        $destinationLocationId,
                        $workspaceTypes,
                        $activeBranchId,
                    );

                if (! $canCreate) {
                    $validator->errors()->add(
                        'source_inventory_location_id',
                        'You can only request stock from the branch main store or locations you manage.',
                    );
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'You can only request stock for inventory locations you manage.',
                    );
                }

                if ($workspaceTypes === []) {
                    return;
                }

                $sourceLocation = $locations->get($sourceLocationId);
                $destinationLocation = $locations->get($destinationLocationId);

                if ($sourceLocation instanceof InventoryLocation && $sourceLocation->type !== InventoryLocationType::MAIN_STORE) {
                    $validator->errors()->add(
                        'source_inventory_location_id',
                        'Dedicated lab and pharmacy requisitions must be sourced from a main store location.',
                    );
                }

                if (
                    $destinationLocation instanceof InventoryLocation
                    && ! in_array($destinationLocation->type?->value, $workspaceTypes, true)
                ) {
                    $validator->errors()->add(
                        'destination_inventory_location_id',
                        'The destination location does not belong to this workspace.',
                    );
                }
            },
        ];
    }
}
