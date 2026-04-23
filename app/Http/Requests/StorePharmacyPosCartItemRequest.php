<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePharmacyPosCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', 'string', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array{
     *   inventory_item_id: string,
     *   quantity: int|float|numeric-string,
     *   unit_price: int|float|numeric-string|null,
     *   discount_amount: int|float|numeric-string|null,
     *   notes: string|null
     * }
     */
    public function itemAttributes(): array
    {
        /** @var array{
         *   inventory_item_id: string,
         *   quantity: int|float|numeric-string,
         *   unit_price: int|float|numeric-string,
         *   discount_amount?: int|float|numeric-string|null,
         *   notes?: string|null
         * } $validated
         */
        $validated = $this->validated();

        return [
            'inventory_item_id' => $validated['inventory_item_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'discount_amount' => $validated['discount_amount'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
