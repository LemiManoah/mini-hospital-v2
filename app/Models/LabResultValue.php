<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LabResultValue extends Model
{
    /** @use HasFactory<\Database\Factories\LabResultValueFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'lab_result_entry_id' => 'string',
        'lab_test_result_parameter_id' => 'string',
        'value_numeric' => 'float',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'display_value',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(LabResultEntry::class, 'lab_result_entry_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(LabTestResultParameter::class, 'lab_test_result_parameter_id');
    }

    protected function displayValue(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->value_numeric !== null) {
                return (string) $this->value_numeric;
            }

            return $this->value_text;
        });
    }
}
