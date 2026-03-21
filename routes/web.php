<?php

declare(strict_types=1);

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AllergenController;
use App\Http\Controllers\AppointmentCategoryController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentModeController;
use App\Http\Controllers\BranchSwitcherController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorConsultationController;
use App\Http\Controllers\DoctorConsultationFacilityServiceOrderController;
use App\Http\Controllers\DoctorConsultationImagingRequestController;
use App\Http\Controllers\DoctorConsultationLabRequestController;
use App\Http\Controllers\DoctorConsultationPrescriptionController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\DoctorScheduleExceptionController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FacilityServiceController;
use App\Http\Controllers\FacilityBranchController;
use App\Http\Controllers\FacilitySwitcherController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\InsurancePackageController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PatientAllergyController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientVisitController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffPositionController;
use App\Http\Controllers\SubscriptionActivationController;
use App\Http\Controllers\SubscriptionPackageController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use App\Http\Controllers\VisitPaymentController;
use App\Http\Controllers\VisitTriageController;
use App\Http\Controllers\VisitVitalSignController;
use App\Http\Controllers\WorkspaceRegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user?->tenant !== null && ! $user->tenant->isOnboardingComplete()) {
            return to_route('onboarding.show');
        }

        return Inertia::render('modules');
    }

    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'ensure.active.branch'])->group(function (): void {
    Route::get('modules', fn () => Inertia::render('modules'))
        ->middleware('permission:dashboard.view')
        ->name('modules');
    Route::get('dashboard', fn () => Inertia::render('dashboard'))
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::middleware('support.only')
        ->prefix('facility-switcher')
        ->name('facility-switcher.')
        ->group(function (): void {
            Route::get('/', [FacilitySwitcherController::class, 'index'])->name('index');
            Route::get('/{tenant}', [FacilitySwitcherController::class, 'show'])->name('show');
            Route::post('/{tenantId}', [FacilitySwitcherController::class, 'switch'])->name('switch');
            Route::post('/{tenant}/activate-subscription', [FacilitySwitcherController::class, 'activateSubscription'])->name('activate-subscription');
            Route::post('/{tenant}/mark-subscription-past-due', [FacilitySwitcherController::class, 'markSubscriptionPastDue'])->name('mark-subscription-past-due');
            Route::post('/{tenant}/complete-onboarding', [FacilitySwitcherController::class, 'completeOnboarding'])->name('complete-onboarding');
            Route::post('/{tenant}/reopen-onboarding', [FacilitySwitcherController::class, 'reopenOnboarding'])->name('reopen-onboarding');
        });

    Route::get('branch-switcher', [BranchSwitcherController::class, 'index'])->name('branch-switcher.index');
    Route::post('branch-switcher/{branchId}', [BranchSwitcherController::class, 'switch'])->name('branch-switcher.switch');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('allergens', AllergenController::class)->except(['show']);
    Route::resource('appointment-categories', AppointmentCategoryController::class)->except(['show']);
    Route::resource('appointment-modes', AppointmentModeController::class)->except(['show']);
    Route::resource('appointments/schedules', DoctorScheduleController::class)
        ->except(['show'])
        ->names('appointments.schedules');
    Route::resource('appointments/exceptions', DoctorScheduleExceptionController::class)
        ->except(['show'])
        ->names('appointments.exceptions');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/my', [AppointmentController::class, 'myAppointments'])->name('appointments.my');
    Route::get('appointments/queue', [AppointmentController::class, 'queue'])->name('appointments.queue');
    Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'markNoShow'])->name('appointments.no-show');
    Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::resource('addresses', AddressController::class)->except(['show']);
    Route::resource('currencies', CurrencyController::class)->except(['show']);
    Route::resource('subscription-packages', SubscriptionPackageController::class)->except(['show']);
    Route::resource('staff-positions', StaffPositionController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('facility-branches', FacilityBranchController::class)->except(['show']);
    Route::resource('clinics', ClinicController::class)->except(['show']);
    Route::resource('insurance-companies', InsuranceCompanyController::class)->except(['show']);
    Route::resource('insurance-packages', InsurancePackageController::class)->except(['show']);
    Route::resource('units', UnitController::class)->except(['show']);
    Route::get('patients/returning', [PatientController::class, 'returning'])->name('patients.returning');
    Route::get('visits', [PatientVisitController::class, 'index'])->name('visits.index');
    Route::get('visits/{visit}', [PatientVisitController::class, 'show'])->name('visits.show');
    Route::post('visits/{visit}/payments', [VisitPaymentController::class, 'store'])->name('visits.payments.store');
    Route::patch('visits/{visit}/status', [PatientVisitController::class, 'updateStatus'])->name('visits.update-status');
    Route::get('doctors/consultations', [DoctorConsultationController::class, 'index'])->name('doctors.consultations.index');
    Route::get('doctors/consultations/{visit}', [DoctorConsultationController::class, 'show'])->name('doctors.consultations.show');
    Route::post('doctors/consultations/{visit}', [DoctorConsultationController::class, 'store'])->name('doctors.consultations.store');
    Route::put('doctors/consultations/{visit}', [DoctorConsultationController::class, 'update'])->name('doctors.consultations.update');
    Route::get('triage', [TriageController::class, 'index'])->name('triage.index');
    Route::get('triage/{visit}', [TriageController::class, 'show'])->name('triage.show');
    Route::post('doctors/consultations/{visit}/lab-requests', [DoctorConsultationLabRequestController::class, 'store'])->name('doctors.consultations.lab-requests.store');
    Route::post('doctors/consultations/{visit}/imaging-requests', [DoctorConsultationImagingRequestController::class, 'store'])->name('doctors.consultations.imaging-requests.store');
    Route::post('doctors/consultations/{visit}/prescriptions', [DoctorConsultationPrescriptionController::class, 'store'])->name('doctors.consultations.prescriptions.store');
    Route::post('doctors/consultations/{visit}/facility-service-orders', [DoctorConsultationFacilityServiceOrderController::class, 'store'])->name('doctors.consultations.facility-service-orders.store');
    Route::post('visits/{visit}/triage', [VisitTriageController::class, 'store'])->name('visits.triage.store');
    Route::post('visits/{visit}/vitals', [VisitVitalSignController::class, 'store'])->name('visits.vitals.store');
    Route::resource('patients', PatientController::class);
    Route::resource('staff', StaffController::class)->except(['show']);
    Route::resource('drugs', DrugController::class)->except(['show']);
    Route::resource('facility-services', FacilityServiceController::class)->except(['show']);
    Route::resource('patients.allergies', PatientAllergyController::class);
    Route::post('patients/{patient}/visits', [PatientVisitController::class, 'store'])->name('patients.visits.store');
    Route::patch('visits/{visit}/mark-in-progress', [PatientVisitController::class, 'markInProgress'])->name('visits.mark-in-progress');
});

Route::middleware('auth')->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::patch('onboarding/profile', [OnboardingController::class, 'updateProfile'])->name('onboarding.profile.update');
    Route::post('onboarding/branch', [OnboardingController::class, 'storeBranch'])->name('onboarding.branch.store');
    Route::post('onboarding/departments', [OnboardingController::class, 'storeDepartments'])->name('onboarding.departments.store');
    Route::post('onboarding/staff', [OnboardingController::class, 'storeStaff'])->name('onboarding.staff.store');
    Route::get('subscription/activate', [SubscriptionActivationController::class, 'show'])->name('subscription.activate.show');
    Route::post('subscription/activate', [SubscriptionActivationController::class, 'store'])->name('subscription.activate.store');
    Route::get('subscription/checkout', [SubscriptionActivationController::class, 'checkout'])->name('subscription.checkout.show');
    Route::post('subscription/checkout/success', [SubscriptionActivationController::class, 'success'])->name('subscription.checkout.success');
    Route::post('subscription/checkout/failure', [SubscriptionActivationController::class, 'failure'])->name('subscription.checkout.failure');
    Route::delete('user-account', [UserController::class, 'destroyCurrentUser'])->name('user.destroy-account');
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    Route::get('create-workspace', [WorkspaceRegistrationController::class, 'create'])->name('workspace-register.create');
    Route::post('create-workspace', [WorkspaceRegistrationController::class, 'store'])->name('workspace-register.store');
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
