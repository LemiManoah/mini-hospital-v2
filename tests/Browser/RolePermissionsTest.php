<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\PermissionSeeder;

it('shows permission labels without group prefix on the create role page', function (): void {
    // prepare a couple of permissions so the page has content
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $page = visit('/roles/create');

    // verify that the group heading appears
    $page->assertSee('Countries');

    // the individual permissions should show only the action (no prefix)
    $page->assertSee('View');
    $page->assertSee('Create');
    $page->assertSee('Update');
    $page->assertSee('Delete');

    // and should *not* include the redundant prefix text
    $page->assertDontSee('Countries View');
    $page->assertDontSee('Countries Create');
});
