<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use App\Models\InventoryRequisition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ApproveInventoryRequisitionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'approval_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_requisition_item_id' => ['required', 'string'],
            'items.*.approved_quantity' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<int, Closure(Validator):void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $requisition = $this->route('requisition');
                $items = $this->input('items');

                if (
                    ! $requisition instanceof InventoryRequisition
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $requisitionItems = $requisition->items()->get()->keyBy('id');

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $lineId = $item['inventory_requisition_item_id'] ?? null;
                    $approvedQuantity = $item['approved_quantity'] ?? null;
                    if (! is_string($lineId)) {
                        continue;
                    }
                    if (! is_numeric($approvedQuantity)) {
                        continue;
                    }

                    $line = $requisitionItems->get($lineId);
                    if ($line === null) {
                        $validator->errors()->add(
                            sprintf('items.%s.inventory_requisition_item_id', $index),
                            'One of the requisition lines is invalid.',
                        );

                        continue;
                    }

                    if ((float) $approvedQuantity > (float) $line->requested_quantity) {
                        $validator->errors()->add(
                            sprintf('items.%s.approved_quantity', $index),
                            'Approved quantity cannot exceed the requested quantity.',
                        );
                    }
                }
            },
        ];
    }
}
