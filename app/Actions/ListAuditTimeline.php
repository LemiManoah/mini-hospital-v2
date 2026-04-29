<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Activity;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class ListAuditTimeline
{
    /**
     * @param  iterable<Model|null>  $subjects
     * @param  list<string>  $logNames
     * @return array<int, array{
     *   id: string,
     *   log_name: string|null,
     *   event: string|null,
     *   title: string,
     *   description: string|null,
     *   actor: string|null,
     *   reason: string|null,
     *   created_at: string|null
     * }>
     */
    public function handle(
        iterable $subjects,
        ?string $tenantId = null,
        array $logNames = [],
        int $limit = 20,
    ): array {
        /** @var Collection<int, Model> $resolvedSubjects */
        $resolvedSubjects = collect($subjects)
            ->filter(static fn (mixed $subject): bool => $subject instanceof Model && $subject->getKey() !== null)
            ->values();

        if ($resolvedSubjects->isEmpty()) {
            return [];
        }

        $activity = Activity::query()
            ->when(
                $tenantId !== null && $tenantId !== '',
                static fn ($query) => $query->where('tenant_id', $tenantId),
            )
            ->when(
                $logNames !== [],
                static fn ($query) => $query->inLog($logNames),
            )
            ->where(function ($query) use ($resolvedSubjects): void {
                foreach ($resolvedSubjects as $subject) {
                    $query->orWhere(function ($subjectQuery) use ($subject): void {
                        $subjectQuery
                            ->where('subject_type', $subject::class)
                            ->where('subject_id', (string) $subject->getKey());
                    });
                }
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return $activity
            ->map(fn (Activity $entry): array => [
                'id' => (string) $entry->getKey(),
                'log_name' => $entry->log_name,
                'event' => $entry->event,
                'title' => $this->title($entry),
                'description' => $entry->description,
                'actor' => $this->actor($entry),
                'reason' => $this->reason($entry),
                'created_at' => $entry->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function title(Activity $activity): string
    {
        if (is_string($activity->description) && $activity->description !== '') {
            return $activity->description;
        }

        $event = $activity->event;

        if (! is_string($event) || $event === '') {
            return 'Audit event';
        }

        return (string) Str::of($event)
            ->replace(['.', '_'], ' ')
            ->title();
    }

    private function actor(Activity $activity): ?string
    {
        $causer = $activity->causer;

        if ($causer instanceof User) {
            $causer->loadMissing('staff');

            return $this->staffName($causer->staff) ?? $causer->email;
        }

        return $this->staffName($activity->staff);
    }

    private function staffName(?Staff $staff): ?string
    {
        if (! $staff instanceof Staff) {
            return null;
        }

        return mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name));
    }

    private function reason(Activity $activity): ?string
    {
        $reason = $activity->getProperty('reason');

        if (is_string($reason) && $reason !== '') {
            return $reason;
        }

        $notes = $activity->getProperty('metadata.notes');

        return is_string($notes) && $notes !== ''
            ? $notes
            : null;
    }
}
