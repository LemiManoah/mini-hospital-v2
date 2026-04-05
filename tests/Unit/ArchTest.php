<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('strict application classes')
    ->expect([
        'App\Actions',
        'App\Policies',
        'App\Rules',
        'App\Services',
        'App\Support',
    ])
    ->classes()
    ->not->toHaveProtectedMethods()
    ->not->toBeAbstract()
    ->toUseStrictTypes()
    ->toUseStrictEquality()
    ->toBeFinal();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

//
