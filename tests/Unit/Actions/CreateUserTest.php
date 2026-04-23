<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Data\User\CreateUserDTO;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = resolve(CreateUser::class);
    $staff = Staff::factory()->create();

    $user = $action->handle(
        new CreateUserDTO(
            staffId: $staff->id,
            email: 'example@email.com',
            roles: [],
        ),
        'password',
    );

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->staff_id)->toBe($staff->id)
        ->and($user->email)->toBe('example@email.com')
        ->and($user->password)->not->toBe('password');

    Event::assertDispatched(Registered::class);
});
