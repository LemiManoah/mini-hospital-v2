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
     *   errors: list<array{row: int, name: string, messages: list<string>}>
     * }
     */
    public function handle(
        UploadedFile $file,
        string $tenantId,
        string $branchName,
        string $userId,
    ): array {
        $import = new PatientImport(
            tenantId: $tenantId,
            branchName: $branchName,
            userId: $userId,
            numberGenerator: $this->numberGenerator,
        );

        Excel::import($import, $file);

        $failures = $import->failures();

        $errors = $failures
            ->groupBy(fn (Failure $f): int => $f->row())
            ->map(function (Collection $group, mixed $row): array {
                /** @var Failure|null $failure */
                $failure = $group->first();

                return [
                    'row' => (int) $row,
                    'name' => $this->failureName($failure, (int) $row),
                    'messages' => $group->flatMap(fn (Failure $f): array => $f->errors())->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'imported' => $import->getImportedCount(),
            'skipped' => $failures->unique(fn (Failure $f): int => $f->row())->count(),
            'errors' => $errors,
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
            (string) ($values['first_name'] ?? ''),
            (string) ($values['last_name'] ?? ''),
        ));
        $phoneNumber = mb_trim((string) ($values['phone_number'] ?? ''));

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
}
