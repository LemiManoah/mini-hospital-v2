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
        $sampleCode = match ($this->policyType) {
            BillableItemType::DRUG => 'DRUG-0001',
            BillableItemType::TEST => 'FBC',
            default => 'CONS-GP',
        };

        $sampleDescription = match ($this->policyType) {
            BillableItemType::DRUG => 'Paracetamol 500mg tablet',
            BillableItemType::TEST => 'Full Blood Count',
            default => 'General Consultation',
        };

        return [
            [
                '',
                $sampleCode,
                $sampleDescription,
                '35000.00',
                'fixed',
                '10000.00',
                now()->toDateString(),
                '',
                'active',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'charge_master_id',
            'charge_master_code',
            'charge_master_description',
            'price',
            'copay_type',
            'copay_value',
            'effective_from',
            'effective_to',
            'status',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 24,
            'C' => 38,
            'D' => 16,
            'E' => 16,
            'F' => 16,
            'G' => 18,
            'H' => 18,
            'I' => 14,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
