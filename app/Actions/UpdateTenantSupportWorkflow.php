<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use InvalidArgumentException;

final readonly class UpdateTenantSupportWorkflow
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Tenant $tenant, array $attributes, ?User $actor = null): Tenant
    {
        $status = $attributes['status'] ?? null;
        $priority = $attributes['priority'] ?? null;

        throw_if(! is_string($status) || ! is_string($priority), InvalidArgumentException::class, 'Tenant support workflow requires string status and priority values.');

        $oldValues = [
            'support_status' => $this->attributeValue($tenant, 'support_status'),
            'support_priority' => $this->attributeValue($tenant, 'support_priority'),
            'support_follow_up_at' => $tenant->support_follow_up_at?->toISOString(),
            'support_last_contacted_at' => $tenant->support_last_contacted_at?->toISOString(),
        ];

        $tenant->update([
            'support_status' => $status,
            'support_priority' => $priority,
            'support_follow_up_at' => $attributes['follow_up_at'] ?? null,
            'support_last_contacted_at' => $attributes['last_contacted_at'] ?? null,
        ]);

        $tenant = $tenant->refresh();

        $this->recordAuditActivity->handle(
            logName: 'support',
            event: 'support.workflow_updated',
            subject: $tenant,
            description: 'Support workflow updated.',
            actor: $actor,
            tenantId: $tenant->id,
            reason: $this->nullableText($attributes['reason'] ?? null),
            oldValues: $oldValues,
            newValues: [
                'support_status' => $status,
                'support_priority' => $priority,
                'support_follow_up_at' => $tenant->support_follow_up_at?->toISOString(),
                'support_last_contacted_at' => $tenant->support_last_contacted_at?->toISOString(),
            ],
        );

        return $tenant;
    }

    private function attributeValue(Tenant $tenant, string $attribute): mixed
    {
        $value = $tenant->getAttributeValue($attribute);

        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function nullableText(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
