<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Policies\FacilityBranchPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::unguard();

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(FacilityBranch::class, FacilityBranchPolicy::class);
    }
}
