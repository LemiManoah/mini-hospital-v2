<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PatientVisit;
use Illuminate\Support\Facades\Auth;

final class DoctorConsultationAccess
{

    public function resolveStaffId(): string
    {
        $staffId = Auth::user()?->staff_id;
        abort_if(! is_string($staffId) || $staffId === '', 403, 'Your user account is not linked to a staff profile.');

        return $staffId;
    }

    public function authorizeVisit(PatientVisit $visit, string $staffId): void
    {
        abort_unless($this->canAccessVisit($visit, $staffId), 403, 'You do not have access to this consultation workspace.');
    }

    public function canAccessVisit(PatientVisit $visit, string $staffId): bool
    {
        if ($visit->facility_branch_id !== BranchContext::getActiveBranchId()) {
            return false;
        }

        if ($visit->status->value === 'completed' || $visit->status->value === 'cancelled') {
            return false;
        }

        if (! $visit->triage()->exists()) {
            return false;
        }

        if ($visit->doctor_id === $staffId || $visit->doctor_id === null) {
            return true;
        }

        return $visit->consultation()
            ->where('doctor_id', $staffId)
            ->exists();
    }
}
