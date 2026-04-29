<?php

declare(strict_types=1);

use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LabResultReleasedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

function createUserWithBranch(): array
{
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    return [$user, $branch];
}

function seedNotification(User $user, bool $read = false): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => LabResultReleasedNotification::class,
        'data' => [
            'type' => 'lab_result_released',
            'title' => 'Lab result released',
            'message' => '"CBC" result is approved and ready for review.',
            'action_url' => '/visits/123',
            'resource_id' => Str::uuid()->toString(),
            'resource_type' => 'lab_request_item',
            'occurred_at' => now()->toISOString(),
        ],
        'read_at' => $read ? now() : null,
    ]);
}

it('requires authentication to view notifications', function (): void {
    $this->get('/notifications')->assertRedirect('/login');
});

it('shows the notifications index page to an authenticated user', function (): void {
    [$user, $branch] = createUserWithBranch();

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->get('/notifications')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('notifications/index')
            ->has('notifications')
        );
});

it('returns the current users notifications only', function (): void {
    [$user, $branch] = createUserWithBranch();
    [$otherUser] = createUserWithBranch();

    seedNotification($user);
    seedNotification($otherUser);

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->get('/notifications')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('notifications/index')
            ->where('notifications.total', 1)
        );
});

it('marks a single notification as read', function (): void {
    [$user, $branch] = createUserWithBranch();
    $notification = seedNotification($user, read: false);

    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->post(sprintf('/notifications/%s/mark-as-read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('prevents marking another users notification as read', function (): void {
    [$user, $branch] = createUserWithBranch();
    [$otherUser] = createUserWithBranch();
    $notification = seedNotification($otherUser, read: false);

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->post(sprintf('/notifications/%s/mark-as-read', $notification->id))
        ->assertNotFound();
});

it('marks all unread notifications as read', function (): void {
    [$user, $branch] = createUserWithBranch();

    seedNotification($user, read: false);
    seedNotification($user, read: false);

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->post('/notifications/mark-all-read')
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('deletes a notification', function (): void {
    [$user, $branch] = createUserWithBranch();
    $notification = seedNotification($user);

    expect($user->notifications()->count())->toBe(1);

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->delete('/notifications/'.$notification->id)
        ->assertRedirect();

    expect($user->notifications()->count())->toBe(0);
});

it('prevents deleting another users notification', function (): void {
    [$user, $branch] = createUserWithBranch();
    [$otherUser] = createUserWithBranch();
    $notification = seedNotification($otherUser);

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->delete('/notifications/'.$notification->id)
        ->assertNotFound();
});

it('includes unread_notifications_count in shared props', function (): void {
    [$user, $branch] = createUserWithBranch();

    seedNotification($user, read: false);
    seedNotification($user, read: false);
    seedNotification($user, read: true);

    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branch->id])
        ->get('/notifications')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('unread_notifications_count', 2)
        );
});
