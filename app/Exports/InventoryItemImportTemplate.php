<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\InventoryItemType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final readonly class InventoryItemImportTemplate implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function __construct(private InventoryItemType $itemType) {}

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        if ($this->itemType === InventoryItemType::DRUG) {
            return [[
                'Paracetamol',
                'Panadol',
                'analgesic',
                '500mg',
                'tablet',
                'tab',
                'QMC-MAIN-STORE',
                '1500',
                'PCM-2026-001',
                '2027-12-31',
                '120.00',
                '800',
                '1200',
                '300.00',
                'GSK',
                'true',
                'false',
                '',
                'Analgesic, Antipyretic',
                'Routine first-line analgesic and antipyretic.',
                'true',
            ]];
        }

        return [[
            'Examination Gloves',
            'box',
            'QMC-MAIN-STORE',
            '50',
            'GLV-2026-001',
            '',
            '18000.00',
            '120',
            '200',
            '',
            'SafeTouch',
            'false',
            'Single-use gloves for OPD, treatment room, and laboratory work.',
            'true',
        ]];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        if ($this->itemType === InventoryItemType::DRUG) {
            return [
                'generic_name',
                'brand_name',
                'category',
                'strength',
                'dosage_form',
                'unit',
                'inventory_location',
                'quantity_on_hand',
                'batch_number',
                'expiry_date',
                'unit_cost',
                'minimum_stock_level',
                'reorder_level',
                'default_selling_price',
                'manufacturer',
                'expires',
                'is_controlled',
                'schedule_class',
                'therapeutic_classes',
                'description',
                'is_active',
            ];
        }

        return [
            'name',
            'unit',
            'inventory_location',
            'quantity_on_hand',
            'batch_number',
            'expiry_date',
            'unit_cost',
            'minimum_stock_level',
            'reorder_level',
            'default_selling_price',
            'manufacturer',
            'expires',
            'description',
            'is_active',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 24,
            'B' => 18,
            'C' => 18,
            'D' => 16,
            'E' => 16,
            'F' => 12,
            'G' => 18,
            'H' => 16,
            'I' => 20,
            'J' => 20,
            'K' => 18,
            'L' => 12,
            'M' => 14,
            'N' => 16,
            'O' => 24,
            'P' => 36,
            'Q' => 12,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
