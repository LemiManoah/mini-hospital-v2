<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\DeleteUser;
use App\Actions\UpdateUser;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.view', only: ['index']),
            new Middleware('permission:users.create', only: ['create', 'store']),
            new Middleware('permission:users.update', only: ['edit', 'update']),
            new Middleware('permission:users.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $users = User::query()
            ->with('roles')
            ->when(
                BranchContext::getActiveBranchId() !== null,
                static fn (Builder $query) => $query->where(function (Builder $userQuery): void {
                    $branchId = BranchContext::getActiveBranchId();

                    $userQuery->whereNull('staff_id')
                        ->orWhereHas('staff.branches', static function (Builder $staffQuery) use ($branchId): void {
                            $staffQuery->where('facility_branches.id', $branchId);
                        });
                })
            )
            ->when(
                $search !== '',
                static fn (Builder $query) => $query
                    ->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('user/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        $staff = Staff::query()
            ->forActiveBranch()
            ->with(['departments', 'position'])
            ->whereDoesntHave('user') // Only show staff without user accounts
            ->orderBy('first_name')
            ->get();

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('user/create', [
            'staff' => $staff,
            'roles' => $roles,
        ]);
    }

    public function store(CreateUserRequest $request, CreateUser $action): RedirectResponse
    {
        $action->handle(
            $request->validated(),
            $request->string('password')->value(),
        );

        return to_route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $this->authorizeManagedUser($user);

        $user->load('roles');

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('user/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateManagedUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $this->authorizeManagedUser($user);

        $action->handle($user, $request->validated());

        return to_route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(DeleteUserRequest $request, User $user, DeleteUser $action): RedirectResponse
    {
        $this->authorizeManagedUser($user);

        $action->handle($user);

        return to_route('users.index')->with('success', 'User deleted successfully.');
    }

    public function destroyCurrentUser(DeleteUserRequest $request, DeleteUser $action): RedirectResponse
    {
        $action->handle($request->user());

        return to_route('login')->with('success', 'Account deleted successfully.');
    }

    private function authorizeManagedUser(User $user): void
    {
        if ($user->staff_id === null) {
            return;
        }

        abort_unless(
            $user->staff()
                ->whereHas('branches', function (Builder $query): void {
                    $query->where('facility_branches.id', BranchContext::getActiveBranchId());
                })
                ->exists(),
            403,
            'You do not have access to this user in the active branch.',
        );
    }
}
