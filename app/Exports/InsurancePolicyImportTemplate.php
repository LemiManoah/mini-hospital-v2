<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\BillableItemType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final readonly class InsurancePolicyImportTemplate implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function __construct(private BillableItemType $policyType) {}

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return match ($this->policyType) {
            BillableItemType::DRUG => [
                [
                    'Paracetamol',
                    '500mg',
                    'tablet',
                    'Panadol',
                    '120.00',
                    now()->toDateString(),
                    '',
                    'active',
                ],
            ],
            BillableItemType::TEST => [
                [
                    'FBC',
                    'Full Blood Count',
                    '25000.00',
                    now()->toDateString(),
                    '',
                    'active',
                ],
            ],
            default => [
                [
                    'CONS-GP',
                    'General Consultation',
                    '35000.00',
                    now()->toDateString(),
                    '',
                    'active',
                ],
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return match ($this->policyType) {
            BillableItemType::DRUG => [
                'generic_name',
                'strength',
                'dosage_form',
                'brand_name',
                'price',
                'effective_from',
                'effective_to',
                'status',
            ],
            BillableItemType::TEST => [
                'test_code',
                'test_name',
                'price',
                'effective_from',
                'effective_to',
                'status',
            ],
            default => [
                'service_code',
                'service_name',
                'price',
                'effective_from',
                'effective_to',
                'status',
            ],
        };
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return match ($this->policyType) {
            BillableItemType::DRUG => [
                'A' => 28,
                'B' => 16,
                'C' => 18,
                'D' => 24,
                'E' => 16,
                'F' => 18,
                'G' => 18,
                'H' => 14,
            ],
            BillableItemType::TEST, BillableItemType::SERVICE => [
                'A' => 18,
                'B' => 34,
                'C' => 16,
                'D' => 18,
                'E' => 18,
                'F' => 14,
            ],
            default => [
                'A' => 18,
                'B' => 34,
                'C' => 16,
                'D' => 18,
                'E' => 18,
                'F' => 14,
            ],
        };
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
