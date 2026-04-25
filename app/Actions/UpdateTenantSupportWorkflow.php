<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use InvalidArgumentException;

final readonly class UpdateTenantSupportWorkflow
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Tenant $tenant, array $attributes): Tenant
    {
        $status = $attributes['status'] ?? null;
        $priority = $attributes['priority'] ?? null;

        throw_if(! is_string($status) || ! is_string($priority), InvalidArgumentException::class, 'Tenant support workflow requires string status and priority values.');

        $tenant->update([
            'support_status' => $status,
            'support_priority' => $priority,
            'support_follow_up_at' => $attributes['follow_up_at'] ?? null,
            'support_last_contacted_at' => $attributes['last_contacted_at'] ?? null,
        ]);

        return $tenant->refresh();
    }
}
