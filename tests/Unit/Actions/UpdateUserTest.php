<?php

declare(strict_types=1);

use App\Actions\UpdateUser;
use App\Data\User\UpdateUserDTO;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('may update a user', function (): void {
    $user = User::factory()->create([
        'email' => 'old@email.com',
    ]);

    $action = resolve(UpdateUser::class);

    $action->handle($user, new UpdateUserDTO(
        email: 'updated@email.com',
        roles: null,
    ));

    expect($user->refresh()->email)->toBe('updated@email.com');
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
