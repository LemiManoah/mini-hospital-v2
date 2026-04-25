import { type VitalSign } from '@/types/patient';

export function formatDate(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function formatDateTime(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatAge(
    age: number | null | undefined,
    ageUnits: string | null | undefined,
): string {
    if (age === null || age === undefined) {
        return 'N/A';
    }

    const unitLabel =
        {
            year: 'year',
            years: 'year',
            month: 'month',
            months: 'month',
            day: 'day',
            days: 'day',
        }[ageUnits ?? ''] ??
        ageUnits ??
        'unit';

    return `${age} ${unitLabel}${age === 1 ? '' : 's'}`;
}

export function formatMoney(
    amount: number | string | null | undefined,
): string {
    const value =
        typeof amount === 'number' ? amount : Number.parseFloat(amount ?? '0');

    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isNaN(value) ? 0 : value);
}

export function statusClasses(status: string): string {
    return (
        {
            registered:
                'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
            in_progress:
                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
            awaiting_payment:
                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            completed:
                'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
            cancelled:
                'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
        }[status] ?? 'bg-zinc-100 text-zinc-800'
    );
}

export function triageGradeClasses(grade: string): string {
    return (
        {
            red: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            yellow: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            green: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            black: 'bg-zinc-900 text-zinc-50 dark:bg-zinc-100 dark:text-zinc-900',
        }[grade] ?? 'bg-zinc-100 text-zinc-800'
    );
}

export function billingStatusClasses(status: string): string {
    return (
        {
            pending:
                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            partial_paid:
                'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
            fully_paid:
                'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
            insurance_pending:
                'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
            waived: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
            refunded:
                'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-950 dark:text-fuchsia-200',
            written_off:
                'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
        }[status] ?? 'bg-zinc-100 text-zinc-800'
    );
}

export function findLabel(
    options: { value: string; label: string }[],
    value: string | null | undefined,
): string {
    return options.find((option) => option.value === value)?.label ?? 'N/A';
}

function measurement(value: number | null | undefined, suffix: string): string {
    return value === null || value === undefined ? 'N/A' : `${value} ${suffix}`;
}

export function vitalSummaryItems(vital: VitalSign | undefined) {
    if (!vital) return [];

    const items: { label: string; value: string }[] = [];
    const pushNumeric = (
        label: string,
        value: number | null | undefined,
        suffix: string,
    ) => {
        if (value === null || value === undefined) {
            return;
        }

        items.push({ label, value: measurement(value, suffix) });
    };
    const pushText = (label: string, value: string | null | undefined) => {
        if (!value) {
            return;
        }

        items.push({ label, value });
    };

    if (vital.temperature !== null) {
        items.push({
            label: 'Temperature',
            value: `${vital.temperature} ${vital.temperature_unit === 'celsius' ? 'C' : 'F'}`,
        });
    }

    pushNumeric('Pulse', vital.pulse_rate, 'bpm');
    pushNumeric('Respiratory', vital.respiratory_rate, '/min');

    if (vital.systolic_bp !== null || vital.diastolic_bp !== null) {
        items.push({
            label: 'Blood Pressure',
            value:
                vital.systolic_bp === null || vital.diastolic_bp === null
                    ? 'Incomplete'
                    : `${vital.systolic_bp}/${vital.diastolic_bp} mmHg`,
        });
    }

    pushNumeric('MAP', vital.map, 'mmHg');
    pushNumeric('SpO2', vital.oxygen_saturation, '%');
    pushText(
        'Supplemental Oxygen',
        vital.on_supplemental_oxygen ? 'Yes' : null,
    );
    pushText('Oxygen Delivery', vital.oxygen_delivery_method);
    pushNumeric('Oxygen Flow', vital.oxygen_flow_rate, 'L/min');
    pushNumeric(
        'Blood Glucose',
        vital.blood_glucose,
        vital.blood_glucose_unit === 'mmol_l' ? 'mmol/L' : 'mg/dL',
    );
    pushNumeric('Pain', vital.pain_score, '/10');
    pushNumeric('Height', vital.height_cm, 'cm');
    pushNumeric('Weight', vital.weight_kg, 'kg');
    pushNumeric('BMI', vital.bmi, '');
    pushNumeric('Head Circumference', vital.head_circumference_cm, 'cm');
    pushNumeric('Chest Circumference', vital.chest_circumference_cm, 'cm');
    pushNumeric('MUAC', vital.muac_cm, 'cm');
    pushText('Capillary Refill', vital.capillary_refill);

    return items;
}
