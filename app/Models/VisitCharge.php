<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\VisitChargeStatus;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $visit_billing_id
 * @property-read string|null $patient_visit_id
 * @property-read string|null $source_id
 * @property-read string|null $source_type
 * @property-read string|null $charge_code
 * @property-read string $description
 * @property-read numeric-string|null $quantity
 * @property-read numeric-string|null $unit_price
 * @property-read numeric-string|null $line_total
 * @property-read VisitChargeStatus|null $status
 * @property-read CarbonInterface|null $charged_at
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read VisitBilling|null $billing
 * @property-read PatientVisit|null $visit
 * @property-read FacilityBranch|null $branch
 * @property-read Model|null $source
 */
final class VisitCharge extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_billing_id' => 'string',
        'patient_visit_id' => 'string',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'status' => VisitChargeStatus::class,
        'charged_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<VisitBilling, $this>
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(VisitBilling::class, 'visit_billing_id');
    }

    /**
     * @return BelongsTo<PatientVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
