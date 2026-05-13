import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { ShieldCheck, WalletCards } from 'lucide-react';

type PatientPayerIndicatorProps = {
    payerType?: 'cash' | 'insurance' | string | null;
    insuranceCompanyName?: string | null;
    insurancePackageName?: string | null;
    unpaidBalance?: number | null;
    className?: string;
};

export function PatientPayerIndicator({
    payerType,
    insuranceCompanyName,
    insurancePackageName,
    unpaidBalance,
    className,
}: PatientPayerIndicatorProps) {
    const isInsurance = payerType === 'insurance';
    const hasUnpaidCashBalance = !isInsurance && (unpaidBalance ?? 0) > 0;
    const label = isInsurance
        ? insuranceLabel(insuranceCompanyName, insurancePackageName)
        : cashLabel(unpaidBalance);
    const Icon = isInsurance ? ShieldCheck : WalletCards;
    const colorClasses = isInsurance
        ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300'
        : hasUnpaidCashBalance
          ? 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-900 dark:bg-yellow-950 dark:text-yellow-300'
          : 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300';

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    aria-label={label}
                    className={cn(
                        'inline-flex size-5 shrink-0 items-center justify-center rounded-full border',
                        colorClasses,
                        className,
                    )}
                    role="img"
                    tabIndex={0}
                >
                    <Icon className="size-3.5" />
                </span>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function insuranceLabel(
    insuranceCompanyName?: string | null,
    insurancePackageName?: string | null,
): string {
    if (insuranceCompanyName && insurancePackageName) {
        return `Insurance: ${insuranceCompanyName} - ${insurancePackageName}`;
    }

    if (insuranceCompanyName) {
        return `Insurance: ${insuranceCompanyName}`;
    }

    if (insurancePackageName) {
        return `Insurance package: ${insurancePackageName}`;
    }

    return 'Insurance patient';
}

function cashLabel(unpaidBalance?: number | null): string {
    if ((unpaidBalance ?? 0) > 0) {
        return `Cash patient - unpaid balance: ${formatAmount(unpaidBalance ?? 0)}`;
    }

    return 'Cash patient';
}

function formatAmount(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}
