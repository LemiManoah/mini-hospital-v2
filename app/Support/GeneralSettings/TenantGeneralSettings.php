<?php

declare(strict_types=1);

namespace App\Support\GeneralSettings;

use App\Models\TenantGeneralSetting;

final class TenantGeneralSettings
{
    /**
     * @return array<string, bool|string|null>
     */
    public function resolved(string $tenantId): array
    {
        /** @var array<string, string|null> $stored */
        $stored = TenantGeneralSetting::query()
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->all();

        return GeneralSettingsRegistry::resolveValues($stored);
    }

    public function boolean(string $tenantId, string $field): bool
    {
        return (bool) ($this->resolved($tenantId)[$field] ?? false);
    }

    public function value(string $tenantId, string $field): bool|string|null
    {
        return $this->resolved($tenantId)[$field] ?? null;
    }
}
