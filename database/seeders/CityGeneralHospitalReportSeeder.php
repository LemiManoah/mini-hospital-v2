<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\BillingStatus;
use App\Enums\PayerType;
use App\Models\Appointment;
use App\Models\AppointmentCategory;
use App\Models\AppointmentMode;
use App\Models\Clinic;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

final class CityGeneralHospitalReportSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $registrar = $tenant instanceof Tenant ? $this->cityGeneralRegistrar($tenant) : null;

        if (! $tenant instanceof Tenant || ! $registrar instanceof User) {
            return;
        }

        /** @var Collection<string, FacilityBranch> $branches */
        $branches = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('branch_code', ['CGH-MAIN', 'CGH-ENT'])
            ->get()
            ->keyBy('branch_code');

        /** @var Collection<string, Patient> $patients */
        $patients = Patient::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('patient_number', [
                'CGH-PAT-1001',
                'CGH-PAT-1002',
                'CGH-PAT-1003',
                'CGH-PAT-1004',
                'CGH-PAT-1005',
            ])
            ->get()
            ->keyBy('patient_number');

        /** @var Collection<string, Clinic> $clinics */
        $clinics = Clinic::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('clinic_code', ['CGH-OPD-MAIN', 'CGH-OPD-ENT'])
            ->get()
            ->keyBy('clinic_code');

        /** @var Collection<string, Staff> $staff */
        $staff = Staff::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('email', [
                'dr.grace.namara@citygeneral.ug',
                'dr.patricia.nalukwago@citygeneral.ug',
            ])
            ->get()
            ->keyBy('email');

        if ($branches->isEmpty() || $patients->isEmpty() || $clinics->isEmpty() || $staff->isEmpty()) {
            return;
        }

        $reviewCategory = AppointmentCategory::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Review Visit',
            ],
            [
                'facility_branch_id' => $branches->get('CGH-MAIN')?->id,
                'clinic_id' => $clinics->get('CGH-OPD-MAIN')?->id,
                'description' => 'Follow-up and routine review bookings.',
                'is_active' => true,
                'created_by' => $registrar->id,
                'updated_by' => $registrar->id,
            ],
        );

        $newPatientCategory = AppointmentCategory::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'New Consultation',
            ],
            [
                'facility_branch_id' => $branches->get('CGH-MAIN')?->id,
                'clinic_id' => $clinics->get('CGH-OPD-MAIN')?->id,
                'description' => 'New outpatient consultation bookings.',
                'is_active' => true,
                'created_by' => $registrar->id,
                'updated_by' => $registrar->id,
            ],
        );

        $physicalMode = AppointmentMode::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Physical',
            ],
            [
                'description' => 'Patient expected on site for consultation.',
                'is_virtual' => false,
                'is_active' => true,
                'created_by' => $registrar->id,
                'updated_by' => $registrar->id,
            ],
        );

        $virtualMode = AppointmentMode::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Virtual Follow-up',
            ],
            [
                'description' => 'Remote follow-up appointment.',
                'is_virtual' => true,
                'is_active' => true,
                'created_by' => $registrar->id,
                'updated_by' => $registrar->id,
            ],
        );

        $today = now();
        $todayDate = $today->toDateString();

        $this->seedAppointment(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-MAIN'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1001'),
            doctor: $this->requireStaff($staff, 'dr.grace.namara@citygeneral.ug'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-MAIN'),
            category: $newPatientCategory,
            mode: $physicalMode,
            startTime: '09:00:00',
            endTime: '09:30:00',
            status: AppointmentStatus::SCHEDULED,
            reasonForVisit: 'Persistent fever review',
            chiefComplaint: 'Fever and body weakness',
            queueNumber: 1,
            appointmentDate: $todayDate,
            createdBy: $registrar->id,
        );

        $this->seedAppointment(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-MAIN'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1002'),
            doctor: $this->requireStaff($staff, 'dr.grace.namara@citygeneral.ug'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-MAIN'),
            category: $reviewCategory,
            mode: $physicalMode,
            startTime: '10:00:00',
            endTime: '10:20:00',
            status: AppointmentStatus::CONFIRMED,
            reasonForVisit: 'Asthma follow-up',
            chiefComplaint: 'Shortness of breath follow-up',
            queueNumber: 2,
            appointmentDate: $todayDate,
            createdBy: $registrar->id,
        );

        $this->seedAppointment(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-MAIN'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1005'),
            doctor: $this->requireStaff($staff, 'dr.grace.namara@citygeneral.ug'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-MAIN'),
            category: $reviewCategory,
            mode: $virtualMode,
            startTime: '11:30:00',
            endTime: '11:45:00',
            status: AppointmentStatus::CHECKED_IN,
            reasonForVisit: 'Blood pressure review',
            chiefComplaint: 'Follow-up for hypertension',
            queueNumber: 3,
            appointmentDate: $todayDate,
            createdBy: $registrar->id,
        );

        $this->seedAppointment(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-ENT'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1004'),
            doctor: $this->requireStaff($staff, 'dr.patricia.nalukwago@citygeneral.ug'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-ENT'),
            category: $reviewCategory,
            mode: $physicalMode,
            startTime: '14:00:00',
            endTime: '14:30:00',
            status: AppointmentStatus::COMPLETED,
            reasonForVisit: 'Wound review',
            chiefComplaint: 'Post dressing review',
            queueNumber: 1,
            appointmentDate: $todayDate,
            createdBy: $registrar->id,
        );

        $this->seedTodayRevenue(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-MAIN'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1002'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-MAIN'),
            doctor: $this->requireStaff($staff, 'dr.grace.namara@citygeneral.ug'),
            visitNumber: 'CGH-VIS-RPT-001',
            invoiceNumber: 'CGH-INV-RPT-001',
            receiptNumber: 'CGH-RCP-RPT-001',
            amount: 45000,
            paymentMethod: 'cash',
            referenceNumber: 'RPT-CASH-001',
            registeredAt: $today->copy()->setTime(9, 15),
            createdBy: $registrar->id,
        );

        $this->seedTodayRevenue(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-MAIN'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1003'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-MAIN'),
            doctor: $this->requireStaff($staff, 'dr.grace.namara@citygeneral.ug'),
            visitNumber: 'CGH-VIS-RPT-002',
            invoiceNumber: 'CGH-INV-RPT-002',
            receiptNumber: 'CGH-RCP-RPT-002',
            amount: 65000,
            paymentMethod: 'mobile_money',
            referenceNumber: 'RPT-MM-002',
            registeredAt: $today->copy()->setTime(11, 5),
            createdBy: $registrar->id,
        );

        $this->seedTodayRevenue(
            tenant: $tenant,
            branch: $this->requireBranch($branches, 'CGH-ENT'),
            patient: $this->requirePatient($patients, 'CGH-PAT-1004'),
            clinic: $this->requireClinic($clinics, 'CGH-OPD-ENT'),
            doctor: $this->requireStaff($staff, 'dr.patricia.nalukwago@citygeneral.ug'),
            visitNumber: 'CGH-VIS-RPT-003',
            invoiceNumber: 'CGH-INV-RPT-003',
            receiptNumber: 'CGH-RCP-RPT-003',
            amount: 30000,
            paymentMethod: 'card',
            referenceNumber: 'RPT-CARD-003',
            registeredAt: $today->copy()->setTime(14, 10),
            createdBy: $registrar->id,
        );
    }

    private function seedAppointment(
        Tenant $tenant,
        FacilityBranch $branch,
        Patient $patient,
        Staff $doctor,
        Clinic $clinic,
        AppointmentCategory $category,
        AppointmentMode $mode,
        string $startTime,
        string $endTime,
        AppointmentStatus $status,
        string $reasonForVisit,
        string $chiefComplaint,
        int $queueNumber,
        string $appointmentDate,
        string $createdBy,
    ): void {
        Appointment::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'appointment_date' => $appointmentDate,
                'start_time' => $startTime,
            ],
            [
                'facility_branch_id' => $branch->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinic->id,
                'appointment_category_id' => $category->id,
                'appointment_mode_id' => $mode->id,
                'end_time' => $endTime,
                'status' => $status->value,
                'reason_for_visit' => $reasonForVisit,
                'chief_complaint' => $chiefComplaint,
                'is_walk_in' => false,
                'queue_number' => $queueNumber,
                'checked_in_at' => in_array($status, [AppointmentStatus::CHECKED_IN, AppointmentStatus::IN_PROGRESS, AppointmentStatus::COMPLETED], true)
                    ? now()->setTimeFromTimeString($startTime)
                    : null,
                'completed_at' => $status === AppointmentStatus::COMPLETED
                    ? now()->setTimeFromTimeString($endTime)
                    : null,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ],
        );
    }

    private function seedTodayRevenue(
        Tenant $tenant,
        FacilityBranch $branch,
        Patient $patient,
        Clinic $clinic,
        Staff $doctor,
        string $visitNumber,
        string $invoiceNumber,
        string $receiptNumber,
        int|float $amount,
        string $paymentMethod,
        string $referenceNumber,
        CarbonInterface $registeredAt,
        string $createdBy,
    ): void {
        $visit = PatientVisit::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'visit_number' => $visitNumber,
            ],
            [
                'patient_id' => $patient->id,
                'facility_branch_id' => $branch->id,
                'clinic_id' => $clinic->id,
                'doctor_id' => $doctor->id,
                'visit_type' => 'outpatient',
                'status' => 'completed',
                'registered_at' => $registeredAt,
                'started_at' => $registeredAt->copy()->addMinutes(10),
                'completed_at' => $registeredAt->copy()->addMinutes(55),
                'notes' => 'Seeded report-ready encounter for same-day revenue.',
                'registered_by' => $createdBy,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ],
        );

        $payer = VisitPayer::query()->updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                'tenant_id' => $tenant->id,
                'billing_type' => PayerType::CASH->value,
            ],
        );

        VisitBilling::query()->updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                'tenant_id' => $tenant->id,
                'facility_branch_id' => $branch->id,
                'visit_payer_id' => $payer->id,
                'payer_type' => PayerType::CASH->value,
                'invoice_number' => $invoiceNumber,
                'gross_amount' => $amount,
                'paid_amount' => $amount,
                'balance_amount' => 0,
                'discount_amount' => 0,
                'status' => BillingStatus::FULLY_PAID->value,
                'billed_at' => $registeredAt,
                'settled_at' => $registeredAt->copy()->addHour(),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ],
        );

        Payment::query()->updateOrCreate(
            ['receipt_number' => $receiptNumber],
            [
                'tenant_id' => $tenant->id,
                'facility_branch_id' => $branch->id,
                'visit_billing_id' => $visit->billing->id ?? VisitBilling::query()->where('patient_visit_id', $visit->id)->value('id'),
                'patient_visit_id' => $visit->id,
                'payment_date' => $registeredAt->copy()->addHour(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'is_refund' => false,
            ],
        );
    }

    /**
     * @param  Collection<string, FacilityBranch>  $branches
     */
    private function requireBranch(Collection $branches, string $branchCode): FacilityBranch
    {
        $branch = $branches->get($branchCode);

        if (! $branch instanceof FacilityBranch) {
            throw new RuntimeException(sprintf('Missing City General branch [%s].', $branchCode));
        }

        return $branch;
    }

    /**
     * @param  Collection<string, Patient>  $patients
     */
    private function requirePatient(Collection $patients, string $patientNumber): Patient
    {
        $patient = $patients->get($patientNumber);

        if (! $patient instanceof Patient) {
            throw new RuntimeException(sprintf('Missing City General patient [%s].', $patientNumber));
        }

        return $patient;
    }

    /**
     * @param  Collection<string, Clinic>  $clinics
     */
    private function requireClinic(Collection $clinics, string $clinicCode): Clinic
    {
        $clinic = $clinics->get($clinicCode);

        if (! $clinic instanceof Clinic) {
            throw new RuntimeException(sprintf('Missing City General clinic [%s].', $clinicCode));
        }

        return $clinic;
    }

    /**
     * @param  Collection<string, Staff>  $staff
     */
    private function requireStaff(Collection $staff, string $email): Staff
    {
        $member = $staff->get($email);

        if (! $member instanceof Staff) {
            throw new RuntimeException(sprintf('Missing City General staff [%s].', $email));
        }

        return $member;
    }
}
