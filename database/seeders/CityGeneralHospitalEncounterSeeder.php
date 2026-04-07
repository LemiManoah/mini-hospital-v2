<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\RecalculateVisitBilling;
use App\Actions\SyncFacilityServiceOrderCharge;
use App\Actions\SyncLabRequestCharge;
use App\Enums\BillingStatus;
use App\Enums\ConsultationOutcome;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\LabBillingStatus;
use App\Enums\LabRequestItemStatus;
use App\Enums\LabRequestStatus;
use App\Enums\PayerType;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Clinic;
use App\Models\Consultation;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\FacilityServiceOrder;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResultEntry;
use App\Models\LabResultValue;
use App\Models\LabTestCatalog;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

final class CityGeneralHospitalEncounterSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $registrar = $tenant instanceof Tenant ? $this->cityGeneralRegistrar($tenant) : null;

        if (! $tenant instanceof Tenant) {
            return;
        }

        $branches = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('branch_code');

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

        $clinics = Clinic::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('clinic_code', [
                'CGH-OPD-MAIN',
                'CGH-TREAT-MAIN',
                'CGH-OPD-ENT',
                'CGH-TREAT-ENT',
            ])
            ->get()
            ->keyBy('clinic_code');

        $staff = Staff::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('email', [
                'dr.grace.namara@citygeneral.ug',
                'dr.samuel.kirabo@citygeneral.ug',
                'dr.patricia.nalukwago@citygeneral.ug',
                'esther.mugerwa@citygeneral.ug',
                'joel.ssekimpi@citygeneral.ug',
                'lillian.nabukeera@citygeneral.ug',
            ])
            ->get()
            ->keyBy('email');

        $tests = LabTestCatalog::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('test_code', [
                'CGH-LAB-CBC',
                'CGH-LAB-MAL',
                'CGH-LAB-UA',
                'CGH-LAB-CRP',
            ])
            ->get()
            ->keyBy('test_code');

        $services = FacilityService::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('service_code', [
                'CGH-SVC-NEB',
                'CGH-SVC-DRESS',
                'CGH-SVC-IV',
            ])
            ->get()
            ->keyBy('service_code');

        if (
            $branches->isEmpty()
            || $patients->isEmpty()
            || $clinics->isEmpty()
            || $staff->isEmpty()
            || $tests->isEmpty()
            || $services->isEmpty()
        ) {
            return;
        }

        foreach ($this->encounterBlueprints() as $scenario) {
            $patient = $patients->get($scenario['patient_number']);
            $branch = $branches->get($scenario['branch_code']);
            $clinic = $clinics->get($scenario['clinic_code']);
            $doctor = $staff->get($scenario['doctor_email']);
            if (! $patient instanceof Patient) {
                continue;
            }

            if (! $branch instanceof FacilityBranch) {
                continue;
            }

            if (! $clinic instanceof Clinic) {
                continue;
            }

            if (! $doctor instanceof Staff) {
                continue;
            }

            $visit = PatientVisit::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'visit_number' => $scenario['visit_number'],
                ],
                [
                    'patient_id' => $patient->id,
                    'facility_branch_id' => $branch->id,
                    'clinic_id' => $clinic->id,
                    'doctor_id' => $doctor->id,
                    'visit_type' => $scenario['visit_type']->value,
                    'status' => $scenario['status']->value,
                    'is_emergency' => $scenario['is_emergency'],
                    'notes' => $scenario['notes'],
                    'registered_at' => $scenario['registered_at'],
                    'started_at' => $scenario['started_at'],
                    'completed_at' => $scenario['completed_at'],
                    'registered_by' => $registrar?->id,
                    'created_by' => $registrar?->id,
                    'updated_by' => $registrar?->id,
                ],
            );

            $payer = VisitPayer::query()->updateOrCreate(
                ['patient_visit_id' => $visit->id],
                [
                    'tenant_id' => $tenant->id,
                    'billing_type' => PayerType::CASH->value,
                    'created_by' => $registrar?->id,
                    'updated_by' => $registrar?->id,
                ],
            );

            $billing = VisitBilling::query()->updateOrCreate(
                ['patient_visit_id' => $visit->id],
                [
                    'tenant_id' => $tenant->id,
                    'facility_branch_id' => $branch->id,
                    'visit_payer_id' => $payer->id,
                    'payer_type' => PayerType::CASH->value,
                    'invoice_number' => $scenario['invoice_number'],
                    'status' => BillingStatus::PENDING->value,
                    'created_by' => $registrar?->id,
                    'updated_by' => $registrar?->id,
                ],
            );

            $consultation = $this->syncConsultation($tenant->id, $visit, $doctor, $branch->id, $scenario['consultation']);

            if ($scenario['lab_request'] !== null) {
                $this->syncLabRequest(
                    $tenant->id,
                    $visit,
                    $consultation,
                    $branch->id,
                    $doctor,
                    $this->requireStaff($staff, $scenario['lab_request']['workflow_staff_email']),
                    $tests,
                    $scenario['lab_request'],
                );
            }

            foreach ($scenario['service_orders'] as $orderData) {
                $this->syncFacilityServiceOrder(
                    $tenant->id,
                    $visit,
                    $consultation,
                    $branch->id,
                    $doctor,
                    $this->requireStaff($staff, $orderData['performed_by_email']),
                    $this->requireService($services, $orderData['service_code']),
                    $orderData,
                );
            }

            resolve(RecalculateVisitBilling::class)->handle($billing->fresh() ?? $billing);
            $this->syncPayment($tenant->id, $branch->id, $visit, $billing->fresh() ?? $billing, $registrar?->id, $scenario['payment']);
            resolve(RecalculateVisitBilling::class)->handle($billing->fresh() ?? $billing);
        }
    }

    /**
     * @param  Collection<string, Staff>  $staff
     */
    private function requireStaff(Collection $staff, string $email): Staff
    {
        $member = $staff->get($email);

        if (! $member instanceof Staff) {
            throw new RuntimeException(sprintf('Missing staff record for [%s].', $email));
        }

        return $member;
    }

    /**
     * @param  Collection<string, FacilityService>  $services
     */
    private function requireService(Collection $services, string $serviceCode): FacilityService
    {
        $service = $services->get($serviceCode);

        if (! $service instanceof FacilityService) {
            throw new RuntimeException(sprintf('Missing facility service [%s].', $serviceCode));
        }

        return $service;
    }

    /**
     * @param  Collection<string, LabTestCatalog>  $tests
     */
    private function requireTest(Collection $tests, string $testCode): LabTestCatalog
    {
        $test = $tests->get($testCode);

        if (! $test instanceof LabTestCatalog) {
            throw new RuntimeException(sprintf('Missing lab test [%s].', $testCode));
        }

        return $test;
    }

    private function syncConsultation(
        string $tenantId,
        PatientVisit $visit,
        Staff $doctor,
        string $branchId,
        ?array $consultationData,
    ): ?Consultation {
        if ($consultationData === null) {
            return null;
        }

        return Consultation::query()->updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'doctor_id' => $doctor->id,
                'started_at' => $consultationData['started_at'],
                'completed_at' => $consultationData['completed_at'],
                'chief_complaint' => $consultationData['chief_complaint'],
                'history_of_present_illness' => $consultationData['history_of_present_illness'],
                'objective_findings' => $consultationData['objective_findings'],
                'assessment' => $consultationData['assessment'],
                'plan' => $consultationData['plan'],
                'primary_diagnosis' => $consultationData['primary_diagnosis'],
                'primary_icd10_code' => $consultationData['primary_icd10_code'],
                'outcome' => $consultationData['outcome']->value,
                'follow_up_instructions' => $consultationData['follow_up_instructions'],
                'follow_up_days' => $consultationData['follow_up_days'],
                'is_referred' => false,
            ],
        );
    }

    private function syncLabRequest(
        string $tenantId,
        PatientVisit $visit,
        ?Consultation $consultation,
        string $branchId,
        Staff $doctor,
        Staff $workflowStaff,
        Collection $tests,
        array $requestData,
    ): void {
        $labRequest = LabRequest::query()->updateOrCreate(
            [
                'visit_id' => $visit->id,
                'consultation_id' => $consultation?->id,
            ],
            [
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'requested_by' => $doctor->id,
                'request_date' => $requestData['request_date'],
                'clinical_notes' => $requestData['clinical_notes'],
                'priority' => $requestData['priority']->value,
                'status' => $requestData['status']->value,
                'diagnosis_code' => $consultation?->primary_icd10_code,
                'is_stat' => $requestData['priority'] === Priority::STAT,
                'billing_status' => $requestData['billing_status']->value,
                'completed_at' => $requestData['status'] === LabRequestStatus::COMPLETED
                    ? collect($requestData['tests'])->pluck('completed_at')->filter()->max()
                    : null,
            ],
        );

        foreach ($requestData['tests'] as $testData) {
            $test = $this->requireTest($tests, $testData['test_code']);

            $item = LabRequestItem::query()->updateOrCreate(
                [
                    'request_id' => $labRequest->id,
                    'test_id' => $test->id,
                ],
                [
                    'status' => $testData['status']->value,
                    'price' => $test->base_price,
                    'is_external' => false,
                    'received_by' => $testData['status'] !== LabRequestItemStatus::PENDING ? $workflowStaff->id : null,
                    'received_at' => $testData['status'] !== LabRequestItemStatus::PENDING ? $requestData['request_date']->copy()->addMinutes(20) : null,
                    'result_entered_by' => $testData['result'] !== null ? $workflowStaff->id : null,
                    'result_entered_at' => $testData['result'] !== null ? $testData['completed_at']?->copy()->subMinutes(20) : null,
                    'reviewed_by' => $testData['result'] !== null ? $workflowStaff->id : null,
                    'reviewed_at' => $testData['result'] !== null ? $testData['completed_at']?->copy()->subMinutes(10) : null,
                    'approved_by' => $testData['result'] !== null ? $workflowStaff->id : null,
                    'approved_at' => $testData['result'] !== null ? $testData['completed_at'] : null,
                    'completed_at' => $testData['completed_at'],
                ],
            );

            if ($testData['result'] !== null) {
                $this->syncLabResultEntry($item, $workflowStaff, $testData['result']);
            }
        }

        resolve(SyncLabRequestCharge::class)->handle($labRequest->fresh(['items.test', 'visit.payer']) ?? $labRequest);
    }

    private function syncLabResultEntry(LabRequestItem $item, Staff $workflowStaff, array $resultData): void
    {
        $item->loadMissing('test.resultParameters');

        $entry = LabResultEntry::query()->updateOrCreate(
            ['lab_request_item_id' => $item->id],
            [
                'entered_by' => $workflowStaff->id,
                'entered_at' => $item->result_entered_at,
                'reviewed_by' => $workflowStaff->id,
                'reviewed_at' => $item->reviewed_at,
                'approved_by' => $workflowStaff->id,
                'approved_at' => $item->approved_at,
                'released_by' => $workflowStaff->id,
                'released_at' => $item->completed_at,
                'result_notes' => $resultData['notes'],
            ],
        );

        $parameters = $item->test?->resultParameters
            ? $item->test->resultParameters->keyBy('label')
            : collect();

        foreach ($resultData['values'] as $index => $valueData) {
            $parameter = $parameters->get($valueData['label']);
            $value = $valueData['value'];

            LabResultValue::query()->updateOrCreate(
                [
                    'lab_result_entry_id' => $entry->id,
                    'label' => $valueData['label'],
                ],
                [
                    'lab_test_result_parameter_id' => $parameter?->id,
                    'value_numeric' => is_numeric($value) ? (float) $value : null,
                    'value_text' => is_numeric($value) ? null : (string) $value,
                    'unit' => $parameter?->unit,
                    'reference_range' => $parameter?->reference_range,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    private function syncFacilityServiceOrder(
        string $tenantId,
        PatientVisit $visit,
        ?Consultation $consultation,
        string $branchId,
        Staff $doctor,
        Staff $performedBy,
        FacilityService $service,
        array $orderData,
    ): void {
        $order = FacilityServiceOrder::query()->updateOrCreate(
            [
                'visit_id' => $visit->id,
                'facility_service_id' => $service->id,
            ],
            [
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'consultation_id' => $consultation?->id,
                'ordered_by' => $doctor->id,
                'status' => $orderData['status']->value,
                'ordered_at' => $orderData['ordered_at'],
                'performed_by' => $orderData['completed_at'] !== null || $orderData['status'] === FacilityServiceOrderStatus::IN_PROGRESS
                    ? $performedBy->id
                    : null,
                'completed_at' => $orderData['completed_at'],
            ],
        );

        resolve(SyncFacilityServiceOrderCharge::class)->handle($order->fresh(['service', 'visit.payer']) ?? $order);
    }

    private function syncPayment(
        string $tenantId,
        string $branchId,
        PatientVisit $visit,
        VisitBilling $billing,
        ?string $userId,
        ?array $paymentData,
    ): void {
        if ($paymentData === null) {
            return;
        }

        Payment::query()->updateOrCreate(
            ['receipt_number' => $paymentData['receipt_number']],
            [
                'tenant_id' => $tenantId,
                'facility_branch_id' => $branchId,
                'visit_billing_id' => $billing->id,
                'patient_visit_id' => $visit->id,
                'payment_date' => $paymentData['payment_date'],
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'],
                'reference_number' => $paymentData['reference_number'],
                'is_refund' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );
    }

    private function encounterBlueprints(): array
    {
        return [
            [
                'visit_number' => 'CGH-VIS-2026001',
                'patient_number' => 'CGH-PAT-1001',
                'branch_code' => 'CGH-MAIN',
                'clinic_code' => 'CGH-OPD-MAIN',
                'doctor_email' => 'dr.grace.namara@citygeneral.ug',
                'invoice_number' => 'CGH-INV-0001',
                'visit_type' => VisitType::OPD_CONSULTATION,
                'status' => VisitStatus::COMPLETED,
                'is_emergency' => false,
                'notes' => 'Walk-in fever case from Kampala Central.',
                'registered_at' => now()->subDays(4)->setTime(8, 10),
                'started_at' => now()->subDays(4)->setTime(8, 35),
                'completed_at' => now()->subDays(4)->setTime(11, 15),
                'consultation' => [
                    'started_at' => now()->subDays(4)->setTime(8, 40),
                    'completed_at' => now()->subDays(4)->setTime(9, 25),
                    'chief_complaint' => 'Fever, chills, headache, and generalized body weakness for 2 days.',
                    'history_of_present_illness' => 'Symptoms started suddenly after travel and worsened overnight. No vomiting or convulsions reported.',
                    'objective_findings' => 'Temp 38.6C, mild dehydration, no respiratory distress.',
                    'assessment' => 'Likely uncomplicated malaria with mild dehydration.',
                    'plan' => 'Confirm malaria, assess CBC, rehydrate, and start treatment.',
                    'primary_diagnosis' => 'Uncomplicated malaria',
                    'primary_icd10_code' => 'B54',
                    'outcome' => ConsultationOutcome::DISCHARGED,
                    'follow_up_instructions' => 'Return in 48 hours if fever persists or earlier if symptoms worsen.',
                    'follow_up_days' => 2,
                ],
                'lab_request' => [
                    'request_date' => now()->subDays(4)->setTime(9, 0),
                    'clinical_notes' => 'Rule out malaria and assess baseline blood count before outpatient treatment.',
                    'priority' => Priority::URGENT,
                    'status' => LabRequestStatus::COMPLETED,
                    'billing_status' => LabBillingStatus::PAID,
                    'workflow_staff_email' => 'lillian.nabukeera@citygeneral.ug',
                    'tests' => [
                        [
                            'test_code' => 'CGH-LAB-MAL',
                            'status' => LabRequestItemStatus::COMPLETED,
                            'completed_at' => now()->subDays(4)->setTime(10, 5),
                            'result' => [
                                'notes' => 'Antigen detected.',
                                'values' => [
                                    ['label' => 'Result', 'value' => 'Positive'],
                                ],
                            ],
                        ],
                        [
                            'test_code' => 'CGH-LAB-CBC',
                            'status' => LabRequestItemStatus::COMPLETED,
                            'completed_at' => now()->subDays(4)->setTime(10, 15),
                            'result' => [
                                'notes' => 'Mild thrombocytopenia noted.',
                                'values' => [
                                    ['label' => 'Hemoglobin', 'value' => 12.8],
                                    ['label' => 'WBC', 'value' => 6.1],
                                    ['label' => 'Platelets', 'value' => 146],
                                ],
                            ],
                        ],
                    ],
                ],
                'service_orders' => [
                    [
                        'service_code' => 'CGH-SVC-IV',
                        'status' => FacilityServiceOrderStatus::COMPLETED,
                        'ordered_at' => now()->subDays(4)->setTime(9, 10),
                        'completed_at' => now()->subDays(4)->setTime(9, 30),
                        'performed_by_email' => 'esther.mugerwa@citygeneral.ug',
                    ],
                ],
                'payment' => [
                    'receipt_number' => 'CGH-RCP-0001',
                    'payment_date' => now()->subDays(4)->setTime(11, 20),
                    'amount' => 78000,
                    'payment_method' => 'cash',
                    'reference_number' => 'POS-0001',
                ],
            ],
            [
                'visit_number' => 'CGH-VIS-2026002',
                'patient_number' => 'CGH-PAT-1002',
                'branch_code' => 'CGH-MAIN',
                'clinic_code' => 'CGH-OPD-MAIN',
                'doctor_email' => 'dr.grace.namara@citygeneral.ug',
                'invoice_number' => 'CGH-INV-0002',
                'visit_type' => VisitType::OUTPATIENT,
                'status' => VisitStatus::IN_PROGRESS,
                'is_emergency' => true,
                'notes' => 'Shortness of breath with wheeze after dust exposure.',
                'registered_at' => now()->subDays(1)->setTime(14, 5),
                'started_at' => now()->subDays(1)->setTime(14, 20),
                'completed_at' => null,
                'consultation' => [
                    'started_at' => now()->subDays(1)->setTime(14, 25),
                    'completed_at' => null,
                    'chief_complaint' => 'Chest tightness and wheezing since this afternoon.',
                    'history_of_present_illness' => 'Known asthma, used inhaler at home with limited relief.',
                    'objective_findings' => 'Audible wheeze, speaking in short sentences, oxygen saturation 94% on room air.',
                    'assessment' => 'Acute asthma exacerbation under treatment.',
                    'plan' => 'Nebulize, observe response, and run CBC/CRP if symptoms persist.',
                    'primary_diagnosis' => 'Acute asthma exacerbation',
                    'primary_icd10_code' => 'J45',
                    'outcome' => ConsultationOutcome::FOLLOW_UP_REQUIRED,
                    'follow_up_instructions' => 'Continue observation in outpatient bay.',
                    'follow_up_days' => 1,
                ],
                'lab_request' => [
                    'request_date' => now()->subDays(1)->setTime(15, 0),
                    'clinical_notes' => 'Check inflammatory markers if symptoms fail to settle after nebulization.',
                    'priority' => Priority::ROUTINE,
                    'status' => LabRequestStatus::IN_PROGRESS,
                    'billing_status' => LabBillingStatus::PENDING,
                    'workflow_staff_email' => 'lillian.nabukeera@citygeneral.ug',
                    'tests' => [
                        [
                            'test_code' => 'CGH-LAB-CBC',
                            'status' => LabRequestItemStatus::IN_PROGRESS,
                            'completed_at' => null,
                            'result' => null,
                        ],
                        [
                            'test_code' => 'CGH-LAB-CRP',
                            'status' => LabRequestItemStatus::PENDING,
                            'completed_at' => null,
                            'result' => null,
                        ],
                    ],
                ],
                'service_orders' => [
                    [
                        'service_code' => 'CGH-SVC-NEB',
                        'status' => FacilityServiceOrderStatus::IN_PROGRESS,
                        'ordered_at' => now()->subDays(1)->setTime(14, 35),
                        'completed_at' => null,
                        'performed_by_email' => 'esther.mugerwa@citygeneral.ug',
                    ],
                ],
                'payment' => null,
            ],
            [
                'visit_number' => 'CGH-VIS-2026003',
                'patient_number' => 'CGH-PAT-1003',
                'branch_code' => 'CGH-MAIN',
                'clinic_code' => 'CGH-OPD-MAIN',
                'doctor_email' => 'dr.grace.namara@citygeneral.ug',
                'invoice_number' => 'CGH-INV-0003',
                'visit_type' => VisitType::NEW,
                'status' => VisitStatus::COMPLETED,
                'is_emergency' => false,
                'notes' => 'Burning urination and lower abdominal discomfort.',
                'registered_at' => now()->subDays(2)->setTime(10, 0),
                'started_at' => now()->subDays(2)->setTime(10, 20),
                'completed_at' => now()->subDays(2)->setTime(12, 10),
                'consultation' => [
                    'started_at' => now()->subDays(2)->setTime(10, 25),
                    'completed_at' => now()->subDays(2)->setTime(11, 5),
                    'chief_complaint' => 'Painful urination for 3 days.',
                    'history_of_present_illness' => 'No flank pain or vomiting. Increased frequency reported.',
                    'objective_findings' => 'Afebrile, suprapubic tenderness present.',
                    'assessment' => 'Likely lower urinary tract infection.',
                    'plan' => 'Do urinalysis and treat based on findings.',
                    'primary_diagnosis' => 'Urinary tract infection',
                    'primary_icd10_code' => 'N39.0',
                    'outcome' => ConsultationOutcome::DISCHARGED,
                    'follow_up_instructions' => 'Increase fluids and return if symptoms fail to improve in 72 hours.',
                    'follow_up_days' => 3,
                ],
                'lab_request' => [
                    'request_date' => now()->subDays(2)->setTime(10, 45),
                    'clinical_notes' => 'Evaluate for pyuria and urinary glucose or protein abnormalities.',
                    'priority' => Priority::ROUTINE,
                    'status' => LabRequestStatus::COMPLETED,
                    'billing_status' => LabBillingStatus::PAID,
                    'workflow_staff_email' => 'lillian.nabukeera@citygeneral.ug',
                    'tests' => [
                        [
                            'test_code' => 'CGH-LAB-UA',
                            'status' => LabRequestItemStatus::COMPLETED,
                            'completed_at' => now()->subDays(2)->setTime(11, 35),
                            'result' => [
                                'notes' => 'Findings support uncomplicated UTI.',
                                'values' => [
                                    ['label' => 'Protein', 'value' => 'Trace'],
                                    ['label' => 'Glucose', 'value' => 'Negative'],
                                    ['label' => 'Leukocytes', 'value' => '8 - 10 /hpf'],
                                ],
                            ],
                        ],
                    ],
                ],
                'service_orders' => [],
                'payment' => [
                    'receipt_number' => 'CGH-RCP-0003',
                    'payment_date' => now()->subDays(2)->setTime(12, 15),
                    'amount' => 20000,
                    'payment_method' => 'mobile_money',
                    'reference_number' => 'MM-274901',
                ],
            ],
            [
                'visit_number' => 'CGH-VIS-2026004',
                'patient_number' => 'CGH-PAT-1004',
                'branch_code' => 'CGH-ENT',
                'clinic_code' => 'CGH-OPD-ENT',
                'doctor_email' => 'dr.patricia.nalukwago@citygeneral.ug',
                'invoice_number' => 'CGH-INV-0004',
                'visit_type' => VisitType::FOLLOW_UP,
                'status' => VisitStatus::COMPLETED,
                'is_emergency' => false,
                'notes' => 'Post-laceration dressing review after school injury.',
                'registered_at' => now()->subDays(3)->setTime(9, 15),
                'started_at' => now()->subDays(3)->setTime(9, 45),
                'completed_at' => now()->subDays(3)->setTime(11, 0),
                'consultation' => [
                    'started_at' => now()->subDays(3)->setTime(9, 50),
                    'completed_at' => now()->subDays(3)->setTime(10, 10),
                    'chief_complaint' => 'Scheduled wound review.',
                    'history_of_present_illness' => 'Healing well, no fever or discharge from the wound site.',
                    'objective_findings' => 'Clean healing wound over right forearm.',
                    'assessment' => 'Healing traumatic laceration.',
                    'plan' => 'Repeat dressing and continue home wound care.',
                    'primary_diagnosis' => 'Follow-up wound care',
                    'primary_icd10_code' => 'Z48.0',
                    'outcome' => ConsultationOutcome::FOLLOW_UP_REQUIRED,
                    'follow_up_instructions' => 'Return in 4 days for final review.',
                    'follow_up_days' => 4,
                ],
                'lab_request' => null,
                'service_orders' => [
                    [
                        'service_code' => 'CGH-SVC-DRESS',
                        'status' => FacilityServiceOrderStatus::COMPLETED,
                        'ordered_at' => now()->subDays(3)->setTime(10, 0),
                        'completed_at' => now()->subDays(3)->setTime(10, 20),
                        'performed_by_email' => 'joel.ssekimpi@citygeneral.ug',
                    ],
                ],
                'payment' => [
                    'receipt_number' => 'CGH-RCP-0004',
                    'payment_date' => now()->subDays(3)->setTime(11, 5),
                    'amount' => 15000,
                    'payment_method' => 'cash',
                    'reference_number' => 'POS-0004',
                ],
            ],
            [
                'visit_number' => 'CGH-VIS-2026005',
                'patient_number' => 'CGH-PAT-1005',
                'branch_code' => 'CGH-MAIN',
                'clinic_code' => 'CGH-OPD-MAIN',
                'doctor_email' => 'dr.grace.namara@citygeneral.ug',
                'invoice_number' => 'CGH-INV-0005',
                'visit_type' => VisitType::OUTPATIENT,
                'status' => VisitStatus::REGISTERED,
                'is_emergency' => false,
                'notes' => 'Awaiting clinician review for blood pressure follow-up.',
                'registered_at' => now()->setTime(8, 55),
                'started_at' => null,
                'completed_at' => null,
                'consultation' => null,
                'lab_request' => null,
                'service_orders' => [],
                'payment' => null,
            ],
        ];
    }
}
