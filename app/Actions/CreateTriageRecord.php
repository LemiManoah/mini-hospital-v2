<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VisitStatus;
use App\Models\PatientVisit;
use App\Models\TriageRecord;
use Illuminate\Support\Facades\Auth;

final readonly class CreateTriageRecord
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    public function handle(PatientVisit $visit, array $data): TriageRecord
    {
        $staffId = Auth::user()?->staff_id;

        $triage = TriageRecord::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'nurse_id' => $staffId,
            'triage_datetime' => now(),
            'triage_grade' => $data['triage_grade'],
            'attendance_type' => $data['attendance_type'],
            'news_score' => $data['news_score'] ?? null,
            'pews_score' => $data['pews_score'] ?? null,
            'conscious_level' => $data['conscious_level'],
            'mobility_status' => $data['mobility_status'],
            'chief_complaint' => $data['chief_complaint'],
            'history_of_presenting_illness' => $data['history_of_presenting_illness'] ?? null,
            'assigned_clinic_id' => $data['assigned_clinic_id'] ?? null,
            'requires_priority' => ! empty($data['requires_priority'])
                || in_array($data['triage_grade'], ['red', 'yellow'], true),
            'is_pediatric' => ! empty($data['is_pediatric']),
            'poisoning_case' => ! empty($data['poisoning_case']),
            'poisoning_agent' => $data['poisoning_agent'] ?? null,
            'snake_bite_case' => ! empty($data['snake_bite_case']),
            'referred_by' => $data['referred_by'] ?? null,
            'nurse_notes' => $data['nurse_notes'] ?? null,
        ]);

        if (! empty($data['assigned_clinic_id']) && $visit->clinic_id !== $data['assigned_clinic_id']) {
            $visit->update(['clinic_id' => $data['assigned_clinic_id']]);
        }

        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }

        return $triage;
    }
}
