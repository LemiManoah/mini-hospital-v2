<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\KinRelationship;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Models\Patient;
use App\Support\BranchScopedNumberGenerator;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class PatientImport implements SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    private int $importedCount = 0;

    /**
     * @var array<string, true>
     */
    private array $seenPhoneNumbers = [];

    public function __construct(
        private readonly string $tenantId,
        private readonly string $branchName,
        private readonly string $userId,
        private readonly BranchScopedNumberGenerator $numberGenerator,
    ) {}

    public function model(array $row): Patient
    {
        $this->importedCount++;

        return new Patient([
            'tenant_id' => $this->tenantId,
            'patient_number' => $this->numberGenerator->nextPatientNumber($this->branchName, $this->tenantId),
            'first_name' => $this->str($row['first_name'] ?? null),
            'last_name' => $this->str($row['last_name'] ?? null),
            'middle_name' => $this->str($row['middle_name'] ?? null),
            'date_of_birth' => $this->date($row['date_of_birth'] ?? null),
            'gender' => $this->lower($row['gender'] ?? null),
            'phone_number' => $this->str($row['phone_number'] ?? null),
            'alternative_phone' => $this->str($row['alternative_phone'] ?? null),
            'email' => $this->lower($row['email'] ?? null),
            'marital_status' => $this->lower($row['marital_status'] ?? null),
            'blood_group' => $this->str($row['blood_group'] ?? null),
            'occupation' => $this->str($row['occupation'] ?? null),
            'religion' => $this->lower($row['religion'] ?? null),
            'next_of_kin_name' => $this->str($row['next_of_kin_name'] ?? null),
            'next_of_kin_phone' => $this->str($row['next_of_kin_phone'] ?? null),
            'next_of_kin_relationship' => $this->lower($row['next_of_kin_relationship'] ?? null),
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['required', new Enum(Gender::class)],
            'phone_number' => [
                'required',
                'max:20',
                Rule::unique('patients', 'phone_number')->where('tenant_id', $this->tenantId),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $phoneNumber = $this->normalizedPhoneNumber($value);

                    if ($phoneNumber === null) {
                        return;
                    }

                    if (array_key_exists($phoneNumber, $this->seenPhoneNumbers)) {
                        $fail('This phone number appears more than once in the uploaded file.');

                        return;
                    }

                    $this->seenPhoneNumbers[$phoneNumber] = true;
                },
            ],
            'alternative_phone' => ['nullable', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients', 'email')->where('tenant_id', $this->tenantId)],
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'blood_group' => ['nullable', new Enum(BloodGroup::class)],
            'occupation' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', new Enum(Religion::class)],
            'next_of_kin_name' => ['nullable', 'string', 'max:100'],
            'next_of_kin_phone' => ['nullable', 'max:20'],
            'next_of_kin_relationship' => ['nullable', new Enum(KinRelationship::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'gender.Illuminate\Validation\Rules\Enum' => 'Gender must be: male or female.',
            'marital_status.Illuminate\Validation\Rules\Enum' => 'Marital status must be: single, married, divorced, widowed, or separated.',
            'blood_group.Illuminate\Validation\Rules\Enum' => 'Blood group must be: A+, A-, B+, B-, AB+, AB-, O+, O-, or unknown.',
            'religion.Illuminate\Validation\Rules\Enum' => 'Religion must be: christian, muslim, hindu, buddhist, other, or unknown.',
            'next_of_kin_relationship.Illuminate\Validation\Rules\Enum' => 'Kin relationship must be: spouse, parent, child, sibling, other, or unknown.',
            'phone_number.unique' => 'This phone number is already registered to another patient.',
            'email.unique' => 'This email address is already in use.',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function prepareForValidation(array $row, int $index): array
    {
        return [
            ...$row,
            'first_name' => $this->str($row['first_name'] ?? null),
            'last_name' => $this->str($row['last_name'] ?? null),
            'middle_name' => $this->str($row['middle_name'] ?? null),
            'date_of_birth' => $this->date($row['date_of_birth'] ?? null),
            'gender' => $this->lower($row['gender'] ?? null),
            'phone_number' => $this->str($row['phone_number'] ?? null),
            'alternative_phone' => $this->str($row['alternative_phone'] ?? null),
            'email' => $this->lower($row['email'] ?? null),
            'marital_status' => $this->lower($row['marital_status'] ?? null),
            'blood_group' => $this->str($row['blood_group'] ?? null),
            'occupation' => $this->str($row['occupation'] ?? null),
            'religion' => $this->lower($row['religion'] ?? null),
            'next_of_kin_name' => $this->str($row['next_of_kin_name'] ?? null),
            'next_of_kin_phone' => $this->str($row['next_of_kin_phone'] ?? null),
            'next_of_kin_relationship' => $this->lower($row['next_of_kin_relationship'] ?? null),
        ];
    }

    private function str(mixed $value): ?string
    {
        $trimmed = mb_trim((string) ($value ?? ''));

        return $trimmed !== '' ? $trimmed : null;
    }

    private function lower(mixed $value): ?string
    {
        $trimmed = mb_strtolower(mb_trim((string) ($value ?? '')));

        return $trimmed !== '' ? $trimmed : null;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        return $this->str($value);
    }

    private function normalizedPhoneNumber(mixed $value): ?string
    {
        $phoneNumber = $this->str($value);

        return $phoneNumber !== null ? mb_strtolower($phoneNumber) : null;
    }
}
