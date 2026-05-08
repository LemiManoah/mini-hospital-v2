<?php

declare(strict_types=1);

namespace App\Actions;

use App\Imports\PatientImport;
use App\Support\BranchScopedNumberGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

final readonly class ProcessPatientImport
{
    public function __construct(private BranchScopedNumberGenerator $numberGenerator) {}

    /**
     * @return array{
     *   imported: int,
     *   skipped: int,
     *   errors: list<array{row: int, name: string, messages: list<string>}>,
     *   previewRows: list<array{row: int, name: string, phoneNumber: string|null, email: string|null}>
     * }
     */
    public function handle(
        UploadedFile|string $file,
        string $tenantId,
        string $branchCode,
        string $userId,
        ?string $disk = null,
        bool $preview = false,
    ): array {
        $import = new PatientImport(
            tenantId: $tenantId,
            branchCode: $branchCode,
            userId: $userId,
            numberGenerator: $this->numberGenerator,
            preview: $preview,
        );

        Excel::import($import, $file, $disk);

        /** @var Collection<int, Failure> $failures */
        $failures = $import->failures();

        $errors = $failures
            ->groupBy(static fn (Failure $failure): int => $failure->row())
            ->map(function (Collection $group, mixed $row): array {
                /** @var Collection<int, Failure> $group */
                /** @var Failure|null $failure */
                $failure = $group->first();
                $messages = $group
                    ->flatMap(static fn (Failure $failure): array => $failure->errors())
                    ->filter(static fn (mixed $message): bool => is_string($message))
                    ->values()
                    ->all();

                return [
                    'row' => is_int($row) ? $row : 0,
                    'name' => $this->failureName($failure, is_int($row) ? $row : 0),
                    'messages' => array_values($messages),
                ];
            })
            ->values()
            ->all();

        return [
            'imported' => $import->getImportedCount(),
            'skipped' => $failures->unique(static fn (Failure $failure): int => $failure->row())->count(),
            'errors' => array_values($errors),
            'previewRows' => $import->previewRows(),
        ];
    }

    private function failureName(?Failure $failure, int $row): string
    {
        if (! $failure instanceof Failure) {
            return 'Row '.$row;
        }

        $values = $failure->values();
        $name = mb_trim(sprintf(
            '%s %s',
            $this->stringValue($values, 'first_name'),
            $this->stringValue($values, 'last_name'),
        ));
        $phoneNumber = mb_trim($this->stringValue($values, 'phone_number'));

        if ($name !== '' && $phoneNumber !== '') {
            return sprintf('%s (%s)', $name, $phoneNumber);
        }

        if ($name !== '') {
            return $name;
        }

        if ($phoneNumber !== '') {
            return $phoneNumber;
        }

        return 'Row '.$row;
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function stringValue(array $values, string $key): string
    {
        $value = $values[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}
