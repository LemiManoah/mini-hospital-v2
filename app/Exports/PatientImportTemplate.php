<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PatientImportTemplate implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            [
                'Jane',
                'Doe',
                'Mary',
                '1990-05-15',
                'female',
                '+254712345678',
                '',
                'jane.doe@example.com',
                'married',
                'O+',
                'Teacher',
                'christian',
                'John Doe',
                '+254798765432',
                'spouse',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'middle_name',
            'date_of_birth',
            'gender',
            'phone_number',
            'alternative_phone',
            'email',
            'marital_status',
            'blood_group',
            'occupation',
            'religion',
            'next_of_kin_name',
            'next_of_kin_phone',
            'next_of_kin_relationship',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 10,
            'F' => 18,
            'G' => 18,
            'H' => 28,
            'I' => 16,
            'J' => 12,
            'K' => 15,
            'L' => 12,
            'M' => 20,
            'N' => 18,
            'O' => 24,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
