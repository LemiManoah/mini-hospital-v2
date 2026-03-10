<?php

declare(strict_types=1);

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AllergenController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacilitySwitcherController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffPositionController;
use App\Http\Controllers\SubscriptionPackageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return Inertia::render('modules');
    }

    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('modules', fn () => Inertia::render('modules'))->name('modules');
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');

    // Facility Switcher (Support Mode)
    Route::get('facility-switcher', [FacilitySwitcherController::class, 'index'])->name('facility-switcher.index');
    Route::post('facility-switcher/{tenantId}', [FacilitySwitcherController::class, 'switch'])->name('facility-switcher.switch');

    // Users Management...
    Route::resource('users', UserController::class)->except(['show']);

    // Roles & Permissions...
    Route::resource('roles', RoleController::class)->except(['show']);

    // Phase 1 Foundation...
    Route::resource('allergens', AllergenController::class)->except(['show']);
    Route::resource('addresses', AddressController::class)->except(['show']);
    Route::resource('currencies', CurrencyController::class)->except(['show']);
    Route::resource('subscription-packages', SubscriptionPackageController::class)->except(['show']);
    Route::resource('staff-positions', StaffPositionController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('staff', StaffController::class)->except(['show']);
});

Route::middleware('auth')->group(function (): void {
    // User Account Management
    Route::delete('user-account', [UserController::class, 'destroyCurrentUser'])->name('user.destroy-account');

    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
