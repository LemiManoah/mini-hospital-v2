<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateConsultationDTO;
use App\Enums\ConsultationType;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateConsultation
{
    public function __construct(
        private SyncConsultationCharge $syncConsultationCharge,
        private TransitionPatientVisitStatus $transitionStatus,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(PatientVisit $visit, CreateConsultationDTO $data): Consultation
    {
        $user = Auth::user();
        $doctorId = $visit->doctor_id ?? ($user instanceof User ? $user->staffId() : null);
        $consultationType = $data->consultationType ?? ConsultationType::defaultForVisit($visit);

        return DB::transaction(function () use ($visit, $data, $doctorId, $consultationType): Consultation {
            $consultation = Consultation::query()->create([
                'tenant_id' => $visit->tenant_id,
                'facility_branch_id' => $visit->facility_branch_id,
                'visit_id' => $visit->id,
                'doctor_id' => $doctorId,
                'consultation_type' => $consultationType,
                'started_at' => now(),
                'chief_complaint' => $this->chiefComplaint($visit, $data),
                'history_of_present_illness' => $data->historyOfPresentIllness,
                'review_of_systems' => $data->reviewOfSystems,
                'past_medical_history_summary' => $data->pastMedicalHistorySummary,
                'family_history' => $data->familyHistory,
                'social_history' => $data->socialHistory,
                'subjective_notes' => $data->subjectiveNotes,
                'objective_findings' => $data->objectiveFindings,
                'assessment' => $data->assessment,
                'plan' => $data->plan,
                'primary_diagnosis' => $data->primaryDiagnosis,
                'primary_icd10_code' => $data->primaryIcd10Code,
                'is_referred' => false,
            ]);

            if ($visit->doctor_id === null && is_string($doctorId) && $doctorId !== '') {
                $visit->update(['doctor_id' => $doctorId]);
            }

            $this->syncConsultationCharge->handle($consultation);

            if ($visit->status === VisitStatus::REGISTERED) {
                $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
            }

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'clinical',
                event: 'consultation.started',
                subject: $consultation,
                description: 'Consultation started.',
                tenantId: $consultation->tenant_id,
                branchId: $consultation->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $doctorId,
                newValues: [
                    'consultation_id' => $consultation->id,
                    'visit_id' => $consultation->visit_id,
                    'doctor_id' => $consultation->doctor_id,
                    'consultation_type' => $consultationType->value,
                    'started_at' => $consultation->started_at->toISOString(),
                ],
            );

            return $consultation;
        });
    }

    private function chiefComplaint(PatientVisit $visit, CreateConsultationDTO $data): string
    {
        $complaint = $data->chiefComplaint;

        if ($complaint !== null) {
            return $complaint;
        }

        /** @var string|null $chiefComplaint */
        $chiefComplaint = $visit->triage?->getAttribute('chief_complaint');

        return $chiefComplaint ?? '';
    }
}
