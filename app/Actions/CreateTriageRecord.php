<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateTriageRecordDTO;
use App\Enums\VisitStatus;
use App\Models\PatientVisit;
use App\Models\TriageRecord;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class CreateTriageRecord
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(PatientVisit $visit, CreateTriageRecordDTO $data): TriageRecord
    {
        $staffId = Auth::user()?->staff_id;

        $triage = TriageRecord::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'nurse_id' => $staffId,
            'triage_datetime' => now(),
            'triage_grade' => $data->triageGrade,
            'attendance_type' => $data->attendanceType,
            'news_score' => $data->newsScore,
            'pews_score' => $data->pewsScore,
            'conscious_level' => $data->consciousLevel,
            'mobility_status' => $data->mobilityStatus,
            'chief_complaint' => $data->chiefComplaint,
            'history_of_presenting_illness' => $data->historyOfPresentingIllness,
            'assigned_clinic_id' => $data->assignedClinicId,
            'requires_priority' => $data->requiresPriority
                || in_array($data->triageGrade, ['red', 'yellow'], true),
            'is_pediatric' => $data->isPediatric,
            'poisoning_case' => $data->poisoningCase,
            'poisoning_agent' => $data->poisoningAgent,
            'snake_bite_case' => $data->snakeBiteCase,
            'referred_by' => $data->referredBy,
            'nurse_notes' => $data->nurseNotes,
        ]);

        if ($data->assignedClinicId !== null && $visit->clinic_id !== $data->assignedClinicId) {
            $visit->update(['clinic_id' => $data->assignedClinicId]);
        }

        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }

        $user = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'clinical',
            event: 'triage.recorded',
            subject: $triage,
            description: 'Triage record created.',
            tenantId: $visit->tenant_id,
            branchId: $visit->facility_branch_id,
            staffId: $staffId,
            newValues: [
                'visit_id' => $visit->id,
                'triage_id' => $triage->id,
                'triage_grade' => $triage->triage_grade,
                'attendance_type' => $triage->attendance_type,
            ],
            metadata: [
                'requires_priority' => $triage->requires_priority,
                'assigned_clinic_id' => $triage->assigned_clinic_id,
                'causer_user_id' => $user instanceof User ? $user->id : null,
            ],
        );

        return $triage;
    }
}
