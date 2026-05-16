<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class FacilityServiceImportTemplate implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [[
            'SVC-CONS-001',
            'General Consultation',
            'other',
            'Routine outpatient consultation.',
            '0.00',
            '25000.00',
            'true',
            'true',
        ]];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'service_code',
            'name',
            'category',
            'description',
            'cost_price',
            'unit_price',
            'is_billable',
            'is_active',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 28,
            'C' => 18,
            'D' => 36,
            'E' => 14,
            'F' => 14,
            'G' => 12,
            'H' => 12,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
