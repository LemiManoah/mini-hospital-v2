<?php

declare(strict_types=1);

use App\Actions\RegisterWorkspace;
use App\Data\Onboarding\CreateWorkspaceRegistrationDTO;
use App\Enums\FacilityLevel;
use App\Models\Role;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

it('registers a workspace from a typed dto and assigns the admin role', function (): void {
    Event::fake([Registered::class]);

    $package = SubscriptionPackage::factory()->create();
    Role::query()->create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $result = resolve(RegisterWorkspace::class)->handle(
        new CreateWorkspaceRegistrationDTO(
            ownerName: 'Grace Hopper',
            workspaceName: 'Acme Hospital',
            email: 'owner@example.com',
            subscriptionPackageId: (string) $package->id,
            facilityLevel: FacilityLevel::HOSPITAL->value,
            countryId: null,
            domain: 'acme-hospital.test',
        ),
        'password123',
    );

    expect($result['tenant']->name)->toBe('Acme Hospital')
        ->and($result['staff']->tenant_id)->toBe($result['tenant']->id)
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->tenant_id)->toBe($result['tenant']->id)
        ->and($result['user']->staff_id)->toBe($result['staff']->id)
        ->and(Hash::check('password123', $result['user']->password))->toBeTrue()
        ->and($result['user']->hasRole('admin'))->toBeTrue();

    Event::assertDispatched(Registered::class);
});
