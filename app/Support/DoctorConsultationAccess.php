<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class DoctorConsultationAccess
{

    public function resolveStaffId(bool $allowPrivilegedWithoutStaff = false): ?string
    {
        $user = Auth::user();
        $staffId = $user?->staff_id;

        if ($allowPrivilegedWithoutStaff && $this->isPrivilegedUser($user)) {
            return is_string($staffId) && $staffId !== '' ? $staffId : null;
        }

        abort_if(! is_string($staffId) || $staffId === '', 403, 'Your user account is not linked to a staff profile.');

        return $staffId;
    }

    public function authorizeVisit(PatientVisit $visit, ?string $staffId): void
    {
        abort_unless($this->canAccessVisit($visit, $staffId), 403, 'You do not have access to this consultation workspace.');
    }

    public function canAccessVisit(PatientVisit $visit, ?string $staffId): bool
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

        if ($this->isPrivilegedUser(Auth::user())) {
            return true;
        }

        if (! is_string($staffId) || $staffId === '') {
            return false;
        }

        if ($visit->doctor_id === $staffId || $visit->doctor_id === null) {
            return true;
        }

        return $visit->consultation()
            ->where('doctor_id', $staffId)
            ->exists();
    }

    public function isPrivilegedUser(?User $user = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->is_support || $user->hasRole('super_admin') || $user->hasRole('admin');
    }
}
