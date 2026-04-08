<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Patient;
use App\Models\PatientVisit;

final class BranchScopedNumberGenerator
{
    public function nextPatientNumber(?string $branchName): string
    {
        $prefix = sprintf('%s-PAT', $this->branchInitials($branchName, 'HSP'));

        $latestSequence = Patient::query()
            ->where('patient_number', 'like', sprintf('%s-%%', $prefix))
            ->pluck('patient_number')
            ->reduce(
                fn (int $carry, mixed $patientNumber): int => max(
                    $carry,
                    $this->extractSequence(
                        is_string($patientNumber) ? $patientNumber : '',
                        sprintf('/^%s-(\d+)$/', preg_quote($prefix, '/')),
                    ),
                ),
                1000,
            );

        return sprintf('%s-%04d', $prefix, $latestSequence + 1);
    }

    public function nextVisitNumber(?string $branchName): string
    {
        $prefix = sprintf('%s-VIS', $this->branchInitials($branchName, 'HSP'));
        $year = now()->format('Y');

        $latestSequence = PatientVisit::query()
            ->where('visit_number', 'like', sprintf('%s-%s%%', $prefix, $year))
            ->pluck('visit_number')
            ->reduce(
                fn (int $carry, mixed $visitNumber): int => max(
                    $carry,
                    $this->extractSequence(
                        is_string($visitNumber) ? $visitNumber : '',
                        sprintf('/^%s-%s(\d{3})$/', preg_quote($prefix, '/'), $year),
                    ),
                ),
                0,
            );

        return sprintf('%s-%s%03d', $prefix, $year, $latestSequence + 1);
    }

    private function extractSequence(string $value, string $pattern): int
    {
        if (preg_match($pattern, $value, $matches) !== 1) {
            return 0;
        }

        return (int) ($matches[1] ?? 0);
    }

    private function branchInitials(?string $branchName, string $fallback): string
    {
        $name = mb_strtoupper(mb_trim((string) $branchName));
        if ($name === '') {
            return $fallback;
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= mb_substr($part, 0, 1);

            if (mb_strlen($initials) >= 3) {
                break;
            }
        }

        return mb_str_pad(mb_substr($initials, 0, 3), 3, 'X');
    }
}
