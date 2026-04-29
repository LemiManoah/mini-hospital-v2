<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NotificationController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $notifications = $user
            ->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (DatabaseNotification $n): array => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? null,
                'title' => $n->data['title'] ?? null,
                'message' => $n->data['message'] ?? null,
                'action_url' => $n->data['action_url'] ?? null,
                'resource_id' => $n->data['resource_id'] ?? null,
                'resource_type' => $n->data['resource_type'] ?? null,
                'occurred_at' => $n->data['occurred_at'] ?? null,
                'read_at' => $n->read_at?->toISOString(),
                'created_at' => $n->created_at?->toISOString(),
            ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $notification = $user
            ->notifications()
            ->findOrFail($notificationId);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function destroy(Request $request, string $notificationId): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $user
            ->notifications()
            ->findOrFail($notificationId)
            ->delete();

        return back();
    }
}
