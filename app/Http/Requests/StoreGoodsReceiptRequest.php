<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreGoodsReceiptRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => [
                'required',
                'string',
                Rule::exists('purchase_orders', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'inventory_location_id' => [
                'required',
                'string',
                Rule::exists('inventory_locations', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'receipt_number' => ['required', 'string', 'max:50'],
            'receipt_date' => ['required', 'date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_delivery_note' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'string', 'exists:purchase_order_items,id'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'items.*.quantity_received' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
