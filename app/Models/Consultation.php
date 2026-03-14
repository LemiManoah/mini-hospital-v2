<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class Consultation extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\ConsultationFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'facility_branch_id',
        'visit_id',
        'doctor_id',
        'started_at',
        'completed_at',
        'chief_complaint',
        'history_of_present_illness',
        'review_of_systems',
        'past_medical_history_summary',
        'family_history',
        'social_history',
        'subjective_notes',
        'objective_findings',
        'assessment',
        'plan',
        'primary_diagnosis',
        'primary_icd10_code',
        'secondary_diagnoses',
        'outcome',
        'follow_up_instructions',
        'follow_up_days',
        'is_referred',
        'referred_to_department',
        'referred_to_facility',
        'referral_reason',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_id' => 'string',
        'doctor_id' => 'string',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'secondary_diagnoses' => 'array',
        'follow_up_days' => 'integer',
        'is_referred' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }
}
