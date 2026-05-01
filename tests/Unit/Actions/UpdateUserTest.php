<?php

declare(strict_types=1);

use App\Actions\UpdateUser;
use App\Data\User\UpdateUserDTO;
use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('may update a user', function (): void {
    $user = User::factory()->create([
        'email' => 'old@email.com',
    ]);
    $actor = User::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    actingAs($actor);

    $action = resolve(UpdateUser::class);

    $action->handle($user, new UpdateUserDTO(
        email: 'updated@email.com',
        roles: null,
    ));

    $activity = Activity::query()
        ->where('event', 'access.user.updated')
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->first();

    expect($user->refresh()->email)->toBe('updated@email.com')
        ->and($activity)->not->toBeNull()
        ->and($activity?->causer_id)->toBe($actor->id);
});

it('resets email verification and sends notification when email changes', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@email.com',
        'email_verified_at' => now(),
    ]);

    expect($user->email_verified_at)->not->toBeNull();

    $action = resolve(UpdateUser::class);

    $action->handle($user, new UpdateUserDTO(
        email: 'new@email.com',
        roles: null,
    ));

    expect($user->refresh()->email)->toBe('new@email.com')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('keeps email verification and does not send notification when email stays the same', function (): void {
    Notification::fake();

    $verifiedAt = now();

    $user = User::factory()->create([
        'email' => 'same@email.com',
        'email_verified_at' => $verifiedAt,
    ]);

    $action = resolve(UpdateUser::class);

    $action->handle($user, new UpdateUserDTO(
        email: 'same@email.com',
        roles: null,
    ));

    expect($user->refresh()->email_verified_at)->not->toBeNull()
        ->and($user->email)->toBe('same@email.com');

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

it('records role changes when updating a user roles', function (): void {
    $roleOne = Role::query()->create([
        'name' => 'Role One',
        'guard_name' => 'web',
    ]);
    $roleTwo = Role::query()->create([
        'name' => 'Role Two',
        'guard_name' => 'web',
    ]);
    $user = User::factory()->create();
    $user->assignRole($roleOne);

    $actor = User::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    actingAs($actor);

    resolve(UpdateUser::class)->handle($user, new UpdateUserDTO(
        email: $user->email,
        roles: [$roleTwo->id],
    ));

    $activity = Activity::query()
        ->where('event', 'access.user.roles_changed')
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->getProperty('old_values.roles'))->toBe(['Role One'])
        ->and($activity?->getProperty('new_values.roles'))->toBe(['Role Two']);
});
