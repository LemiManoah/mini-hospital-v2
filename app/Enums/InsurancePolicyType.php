<?php

declare(strict_types=1);

namespace App\Enums;

enum InsurancePolicyType: string
{
    case PHARMACY = 'pharmacy';
    case LAB = 'lab';
    case SERVICES = 'services';

    public static function fromBillableItemType(BillableItemType $itemType): ?self
    {
        return match ($itemType) {
            BillableItemType::DRUG => self::PHARMACY,
            BillableItemType::TEST => self::LAB,
            BillableItemType::SERVICE => self::SERVICES,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PHARMACY => 'Pharmacy',
            self::LAB => 'Lab',
            self::SERVICES => 'Services',
        };
    }

    public function itemType(): BillableItemType
    {
        return match ($this) {
            self::PHARMACY => BillableItemType::DRUG,
            self::LAB => BillableItemType::TEST,
            self::SERVICES => BillableItemType::SERVICE,
        };
    }
}
