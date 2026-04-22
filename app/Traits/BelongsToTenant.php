<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            $user = Auth::user();
            $tenantId = $user instanceof User ? $user->tenantId() : null;

            if (empty($model->getAttribute('tenant_id')) && is_string($tenantId) && $tenantId !== '') {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }
}
