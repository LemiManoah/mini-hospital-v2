import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type FilterDefaults = Record<string, string>;

type DateRangeFilters<TDefaults extends FilterDefaults> = {
    from_date?: string | null;
    to_date?: string | null;
} & Partial<Record<keyof TDefaults, string | null>>;

interface UseDateRangeQueryFiltersOptions<TDefaults extends FilterDefaults> {
    route: string;
    filters: DateRangeFilters<TDefaults>;
    defaults: TDefaults;
    only?: string[];
    debounceMs?: number;
}

function resolveExtraValues<TDefaults extends FilterDefaults>(
    filters: DateRangeFilters<TDefaults>,
    defaults: TDefaults,
): TDefaults {
    return Object.keys(defaults).reduce((carry, key) => {
        carry[key] = filters[key] ?? defaults[key];

        return carry;
    }, {} as TDefaults);
}

export function useDateRangeQueryFilters<TDefaults extends FilterDefaults>({
    route,
    filters,
    defaults,
    only = ['filters'],
    debounceMs = 250,
}: UseDateRangeQueryFiltersOptions<TDefaults>) {
    const [fromDate, setFromDate] = useState(filters.from_date ?? '');
    const [toDate, setToDate] = useState(filters.to_date ?? '');
    const [values, setValues] = useState<TDefaults>(
        resolveExtraValues(filters, defaults),
    );

    const defaultKeys = Object.keys(defaults) as Array<keyof TDefaults>;
    const serverFilterSignature = JSON.stringify({
        fromDate: filters.from_date ?? '',
        toDate: filters.to_date ?? '',
        values: resolveExtraValues(filters, defaults),
    });
    const localFilterSignature = JSON.stringify({
        fromDate,
        toDate,
        values,
    });

    useEffect(() => {
        setFromDate(filters.from_date ?? '');
        setToDate(filters.to_date ?? '');
        setValues(resolveExtraValues(filters, defaults));
    }, [serverFilterSignature]);

    useEffect(() => {
        if (localFilterSignature === serverFilterSignature) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            const payload: Record<string, string | undefined> = {
                from_date: fromDate || undefined,
                to_date: toDate || undefined,
            };

            defaultKeys.forEach((key) => {
                const value = values[key];

                payload[String(key)] =
                    value === '' || value === defaults[key] ? undefined : value;
            });

            router.get(route, payload, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
            });
        }, debounceMs);

        return () => window.clearTimeout(timeoutId);
    }, [
        debounceMs,
        defaults,
        defaultKeys,
        fromDate,
        localFilterSignature,
        only,
        route,
        serverFilterSignature,
        toDate,
        values,
    ]);

    function setValue<TKey extends keyof TDefaults>(
        key: TKey,
        value: TDefaults[TKey],
    ): void {
        setValues((currentValues) => ({
            ...currentValues,
            [key]: value,
        }));
    }

    return {
        fromDate,
        setFromDate,
        toDate,
        setToDate,
        values,
        setValue,
    };
}
