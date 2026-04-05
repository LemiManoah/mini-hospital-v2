<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case Receipt = 'receipt';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Issue = 'issue';
    case Dispense = 'dispense';
    case AdjustmentGain = 'adjustment_gain';
    case AdjustmentLoss = 'adjustment_loss';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';
    case Expiry = 'expiry';
    case Damage = 'damage';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Opening Balance',
            self::Receipt => 'Receipt',
            self::TransferOut => 'Transfer Out',
            self::TransferIn => 'Transfer In',
            self::Issue => 'Issue',
            self::Dispense => 'Dispense',
            self::AdjustmentGain => 'Adjustment Gain',
            self::AdjustmentLoss => 'Adjustment Loss',
            self::ReturnIn => 'Return In',
            self::ReturnOut => 'Return Out',
            self::Expiry => 'Expiry',
            self::Damage => 'Damage',
        };
    }
}
