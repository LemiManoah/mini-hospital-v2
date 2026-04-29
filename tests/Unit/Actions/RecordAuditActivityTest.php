<?php

declare(strict_types=1);

use App\Actions\RecordAuditActivity;
use App\Enums\GeneralStatus;
use App\Models\Activity;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

it('records audit activity with uuid subject and causer metadata', function (): void {
    $package = SubscriptionPackage::query()->create([
        'name' => 'Audit Package',
        'users' => 20,
        'price' => 0,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Audit Tenant',
        'domain' => 'audit-tenant.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => 'hospital',
    ]);

    $currency = Currency::query()->create([
        'code' => 'AUD',
        'name' => 'Audit Currency',
        'symbol' => 'A$',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Audit Branch',
        'branch_code' => 'AUD1',
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => false,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-AUD-1',
        'first_name' => 'Audit',
        'last_name' => 'Patient',
        'gender' => 'male',
        'phone_number' => '+256700000999',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $request = Request::create('/audit-test', 'POST', server: [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Pest Audit Test',
    ]);

    app()->instance('request', $request);

    resolve(RecordAuditActivity::class)->handle(
        logName: 'clinical',
        event: 'patient.updated',
        subject: $patient,
        description: 'Patient demographics updated.',
        actor: $user,
        tenantId: $tenant->id,
        branchId: $branch->id,
        reason: 'Registration correction',
        oldValues: ['phone_number' => '+256700000111'],
        newValues: ['phone_number' => '+256700000999'],
        metadata: ['trace_id' => (string) Str::uuid()],
    );

    $activity = Activity::query()->where('event', 'patient.updated')->first();

    expect($activity)->not()->toBeNull()
        ->and($activity?->log_name)->toBe('clinical')
        ->and($activity?->subject_type)->toBe(Patient::class)
        ->and($activity?->subject_id)->toBe($patient->id)
        ->and($activity?->causer_type)->toBe(User::class)
        ->and($activity?->causer_id)->toBe($user->id)
        ->and($activity?->tenant_id)->toBe($tenant->id)
        ->and($activity?->branch_id)->toBe($branch->id)
        ->and($activity?->ip_address)->toBe('127.0.0.1')
        ->and($activity?->user_agent)->toBe('Pest Audit Test')
        ->and($activity?->getProperty('reason'))->toBe('Registration correction')
        ->and($activity?->getProperty('old_values.phone_number'))->toBe('+256700000111')
        ->and($activity?->getProperty('new_values.phone_number'))->toBe('+256700000999');
});
