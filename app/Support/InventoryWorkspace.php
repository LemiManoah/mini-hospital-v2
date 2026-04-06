<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InventoryLocationType;
use App\Models\GoodsReceipt;
use App\Models\InventoryRequisition;
use Illuminate\Http\Request;

final readonly class InventoryWorkspace
{
    private function __construct(
        private string $key,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'laboratory.')) {
            return new self('laboratory');
        }

        if (is_string($routeName) && str_starts_with($routeName, 'pharmacy.')) {
            return new self('pharmacy');
        }

        return new self('inventory');
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return list<string>
     */
    public function locationTypeValues(): array
    {
        return match ($this->key) {
            'laboratory' => [InventoryLocationType::LABORATORY->value],
            'pharmacy' => [InventoryLocationType::PHARMACY->value],
            default => [],
        };
    }

    public function stockComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/stock/index',
            'pharmacy' => 'pharmacy/stock/index',
            default => 'inventory/stock-by-location/index',
        };
    }

    public function movementsComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/movements/index',
            'pharmacy' => 'pharmacy/movements/index',
            default => 'inventory/reports/movements/index',
        };
    }

    public function requisitionIndexComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/requisitions/index',
            'pharmacy' => 'pharmacy/requisitions/index',
            default => 'inventory/requisitions/index',
        };
    }

    public function requisitionCreateComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/requisitions/create',
            'pharmacy' => 'pharmacy/requisitions/create',
            default => 'inventory/requisitions/create',
        };
    }

    public function requisitionShowComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/requisitions/show',
            'pharmacy' => 'pharmacy/requisitions/show',
            default => 'inventory/requisitions/show',
        };
    }

    public function goodsReceiptIndexComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/receipts/index',
            'pharmacy' => 'pharmacy/receipts/index',
            default => 'inventory/goods-receipts/index',
        };
    }

    public function goodsReceiptCreateComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/receipts/create',
            'pharmacy' => 'pharmacy/receipts/create',
            default => 'inventory/goods-receipts/create',
        };
    }

    public function goodsReceiptShowComponent(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory/receipts/show',
            'pharmacy' => 'pharmacy/receipts/show',
            default => 'inventory/goods-receipts/show',
        };
    }

    public function requisitionShowRouteName(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory.requisitions.show',
            'pharmacy' => 'pharmacy.requisitions.show',
            default => 'inventory-requisitions.show',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function requisitionShowRouteParameters(InventoryRequisition $requisition): array
    {
        return match ($this->key) {
            'laboratory', 'pharmacy' => ['requisition' => $requisition],
            default => ['requisition' => $requisition],
        };
    }

    public function goodsReceiptShowRouteName(): string
    {
        return match ($this->key) {
            'laboratory' => 'laboratory.receipts.show',
            'pharmacy' => 'pharmacy.receipts.show',
            default => 'goods-receipts.show',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function goodsReceiptShowRouteParameters(GoodsReceipt $goodsReceipt): array
    {
        return match ($this->key) {
            'laboratory', 'pharmacy' => ['goods_receipt' => $goodsReceipt],
            default => ['goods_receipt' => $goodsReceipt],
        };
    }
}
