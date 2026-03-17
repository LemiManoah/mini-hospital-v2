<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonImmutable;

final readonly class ResolveDateRange
{
    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    public function handle(
        ?string $fromDate = null,
        ?string $toDate = null,
        int $defaultSpanDays = 0,
    ): array {
        $today = CarbonImmutable::today();
        $from = $fromDate !== null && $fromDate !== ''
            ? CarbonImmutable::parse($fromDate)
            : $today;
        $to = $toDate !== null && $toDate !== ''
            ? CarbonImmutable::parse($toDate)
            : $from->addDays($defaultSpanDays);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from->startOfDay(),
            'to' => $to->endOfDay(),
        ];
    }
}
