<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Data\User\CreateUserDTO;
use App\Models\Activity;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = resolve(CreateUser::class);
    $staff = Staff::factory()->create();
    $role = Role::query()->create([
        'name' => 'Audit Test Role',
        'guard_name' => 'web',
    ]);
    $actor = User::factory()->create([
        'tenant_id' => $staff->tenant_id,
    ]);

    actingAs($actor);

    $user = $action->handle(
        new CreateUserDTO(
            staffId: $staff->id,
            email: 'example@email.com',
            roles: [$role->id],
        ),
        'password',
    );

    $activity = Activity::query()
        ->where('event', 'access.user.created')
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->first();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->staff_id)->toBe($staff->id)
        ->and($user->email)->toBe('example@email.com')
        ->and($user->password)->not->toBe('password')
        ->and($activity)->not->toBeNull()
        ->and($activity?->log_name)->toBe('access')
        ->and($activity?->causer_id)->toBe($actor->id)
        ->and($activity?->getProperty('new_values.roles'))->toBe(['Audit Test Role']);

    Event::assertDispatched(Registered::class);
});
