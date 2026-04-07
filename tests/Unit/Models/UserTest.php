<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect($user->toArray())->toHaveKeys([
        'id',
        'name',
        'avatar',
        'email',
        'email_verified_at',
        'two_factor_confirmed_at',
        'created_at',
        'updated_at',
    ]);
});
