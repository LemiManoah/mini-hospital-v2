<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Onboarding\CreateOnboardingStaffMemberDTO;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class BootstrapOnboardingStaffMember
{
    public function __construct(
        private CreateStaff $createStaff,
    ) {}

    public function handle(Tenant $tenant, User $user, CreateOnboardingStaffMemberDTO $data): void
    {
        $mainBranch = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_main_branch', true)
            ->first();

        throw_unless($mainBranch instanceof FacilityBranch, RuntimeException::class, 'A primary branch is required before onboarding staff.');

        DB::transaction(function () use ($tenant, $user, $data, $mainBranch): void {
            $this->createStaff->handle(
                $data->toCreateStaffPayload($tenant->id, $mainBranch->id, $user->id),
            );

            $tenant->update([
                'updated_by' => $user->id,
                'onboarding_completed_at' => now(),
                'onboarding_current_step' => 'complete',
            ]);
        });
    }
}
