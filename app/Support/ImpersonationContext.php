<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class ImpersonationContext
{
    private const string SESSION_REAL_USER_ID = 'impersonation.real_user_id';

    private const string SESSION_TARGET_USER_ID = 'impersonation.target_user_id';

    private const string SESSION_STARTED_AT = 'impersonation.started_at';

    public static function start(Request $request, User $realUser, User $targetUser): void
    {
        $request->session()->put([
            self::SESSION_REAL_USER_ID => $realUser->id,
            self::SESSION_TARGET_USER_ID => $targetUser->id,
            self::SESSION_STARTED_AT => now()->toISOString(),
        ]);
    }

    public static function stop(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_REAL_USER_ID,
            self::SESSION_TARGET_USER_ID,
            self::SESSION_STARTED_AT,
        ]);

        $request->attributes->remove('impersonation.real_user');
        $request->attributes->remove('impersonation.target_user');
    }

    public static function isActive(Request $request): bool
    {
        return self::realUserId($request) !== null
            && self::targetUserId($request) !== null;
    }

    public static function realUserId(Request $request): ?string
    {
        $userId = $request->session()->get(self::SESSION_REAL_USER_ID);

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    public static function targetUserId(Request $request): ?string
    {
        $userId = $request->session()->get(self::SESSION_TARGET_USER_ID);

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    public static function startedAt(Request $request): ?string
    {
        $startedAt = $request->session()->get(self::SESSION_STARTED_AT);

        return is_string($startedAt) && $startedAt !== '' ? $startedAt : null;
    }

    public static function realUser(Request $request): ?User
    {
        $cachedUser = $request->attributes->get('impersonation.real_user');

        if ($cachedUser instanceof User) {
            return $cachedUser;
        }

        $realUserId = self::realUserId($request);

        if ($realUserId === null) {
            return null;
        }

        $user = User::query()->find($realUserId);

        if ($user instanceof User) {
            $request->attributes->set('impersonation.real_user', $user);
        }

        return $user;
    }

    public static function targetUser(Request $request): ?User
    {
        $cachedUser = $request->attributes->get('impersonation.target_user');

        if ($cachedUser instanceof User) {
            return $cachedUser;
        }

        $targetUserId = self::targetUserId($request);

        if ($targetUserId === null) {
            return null;
        }

        $user = User::query()->find($targetUserId);

        if ($user instanceof User) {
            $request->attributes->set('impersonation.target_user', $user);
        }

        return $user;
    }
}
