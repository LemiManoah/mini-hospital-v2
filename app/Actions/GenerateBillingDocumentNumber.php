<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillingDocumentType;
use App\Enums\BillingSequenceResetPeriod;
use App\Models\BillingDocumentSequence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class GenerateBillingDocumentNumber
{
    public function handle(BillingDocumentType $documentType, string $tenantId, ?string $branchId = null): string
    {
        return DB::transaction(function () use ($documentType, $tenantId, $branchId): string {
            $periodKey = $this->periodKey(BillingSequenceResetPeriod::Yearly);
            $userId = Auth::id();

            $sequence = BillingDocumentSequence::query()
                ->where('tenant_id', $tenantId)
                ->where('facility_branch_id', $branchId)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $sequence instanceof BillingDocumentSequence) {
                $sequence = BillingDocumentSequence::query()->create([
                    'tenant_id' => $tenantId,
                    'facility_branch_id' => $branchId,
                    'document_type' => $documentType,
                    'prefix' => $documentType->defaultPrefix(),
                    'next_number' => 1,
                    'padding' => 6,
                    'reset_period' => BillingSequenceResetPeriod::Yearly,
                    'current_period_key' => $periodKey,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            $resetPeriod = $sequence->reset_period ?? BillingSequenceResetPeriod::Yearly;
            $periodKey = $this->periodKey($resetPeriod);

            if ($resetPeriod !== BillingSequenceResetPeriod::Never && $sequence->current_period_key !== $periodKey) {
                $sequence->forceFill([
                    'next_number' => 1,
                    'current_period_key' => $periodKey,
                ]);
            }

            $number = (int) $sequence->next_number;

            $sequence->forceFill([
                'next_number' => $number + 1,
                'updated_by' => $userId,
            ])->save();

            $parts = array_filter([
                $sequence->prefix,
                $periodKey,
                mb_str_pad((string) $number, (int) $sequence->padding, '0', STR_PAD_LEFT),
            ]);

            return implode('-', $parts);
        });
    }

    private function periodKey(BillingSequenceResetPeriod $resetPeriod): ?string
    {
        return match ($resetPeriod) {
            BillingSequenceResetPeriod::Never => null,
            BillingSequenceResetPeriod::Yearly => now()->format('Y'),
            BillingSequenceResetPeriod::Monthly => now()->format('Ym'),
            BillingSequenceResetPeriod::Daily => now()->format('Ymd'),
        };
    }
}
