<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'string',
                Rule::exists('suppliers', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'items.*.quantity_ordered' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
