import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type PharmacyPatientSummary,
    type PharmacyQueuePageProps,
    type PharmacyQueuePrescription,
} from '@/types/pharmacy';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { DispenseModal } from './components/dispense-modal';

const badgeTone = (value: string | null | undefined): string => {
    switch (value) {
        case 'ready':
        case 'dispensed':
        case 'fully_dispensed':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'partial':
        case 'partially_dispensed':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'out_of_stock':
        case 'cancelled':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
};

type QueuePatientGroup = {
    key: string;
    patient: PharmacyPatientSummary | null;
    prescriptions: PharmacyQueuePrescription[];
};

const groupPrescriptionsByPatient = (
    rows: PharmacyQueuePrescription[],
): QueuePatientGroup[] => {
    const groups = new Map<string, QueuePatientGroup>();

    rows.forEach((prescription) => {
        const key =
            prescription.patient?.id ??
            `${prescription.visit_id}:${prescription.id}`;
        const existing = groups.get(key);

        if (existing) {
            existing.prescriptions.push(prescription);
            return;
        }

        groups.set(key, {
            key,
            patient: prescription.patient,
            prescriptions: [prescription],
        });
    });

    return Array.from(groups.values());
};

export default function PharmacyQueuePage({
    navigation,
    prescriptions,
    filters,
    statusOptions,
    dispensingLocations,
    availableBatchBalances,
    pharmacyPolicy,
}: PharmacyQueuePageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [activePrescriptionId, setActivePrescriptionId] = useState<
        string | null
    >(null);

    useEffect(() => {
        if (
            search === (filters.search ?? '') &&
            status === (filters.status ?? 'all')
        ) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                navigation.queue_href ?? '/pharmacy/queue',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['prescriptions', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, filters.status, navigation.queue_href, search, status]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.queue_title ?? 'Pharmacy Queue',
            href: navigation.queue_href ?? '/pharmacy/queue',
        },
    ];

    const patientGroups = groupPrescriptionsByPatient(prescriptions.data);
    const activePrescription =
        prescriptions.data.find(
            (prescription) => prescription.id === activePrescriptionId,
        ) ?? null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pharmacy Queue" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold">
                            {navigation.queue_title ?? 'Pharmacy Queue'}
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Open pending prescriptions and dispense from this
                            screen.
                        </p>
                        <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                            <span>
                                Patients in queue: {patientGroups.length}
                            </span>
                            <span>
                                Active prescriptions: {prescriptions.total}
                            </span>
                            <span>
                                Dispensing locations:{' '}
                                {dispensingLocations.length}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient, visit, or drug..."
                            className="min-w-72"
                        />
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger className="w-full sm:w-48">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All queue statuses
                                </SelectItem>
                                {statusOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {patientGroups.length === 0 ? (
                    <Card>
                        <CardContent className="py-14 text-center text-sm text-muted-foreground">
                            No prescriptions matched this pharmacy queue.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {patientGroups.map((group) => (
                            <div
                                key={group.key}
                                className="overflow-hidden rounded-lg border border-border/60 bg-background"
                            >
                                <div className="border-b border-sidebar-border/20 bg-sidebar/10 px-3 pt-1.5 pb-2">
                                    <div className="flex flex-col gap-2 lg:flex-row lg:items-baseline lg:justify-between">
                                        <div className="space-y-0.5">
                                            <h2 className="text-base font-semibold">
                                                {group.patient?.full_name ??
                                                    'Unknown patient'}
                                            </h2>
                                            <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                                <span>
                                                    Patient No:{' '}
                                                    {group.patient
                                                        ?.patient_number ?? '-'}
                                                </span>
                                                <span>
                                                    Phone:{' '}
                                                    {group.patient
                                                        ?.phone_number ?? '-'}
                                                </span>
                                            </div>
                                        </div>
                                        <Badge variant="outline">
                                            {group.prescriptions.length} active
                                            {group.prescriptions.length === 1
                                                ? ' prescription'
                                                : ' prescriptions'}
                                        </Badge>
                                    </div>
                                </div>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    Medication
                                                </TableHead>
                                                <TableHead>
                                                    Quantity
                                                </TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Stock</TableHead>
                                                <TableHead>
                                                    Prescribed
                                                </TableHead>
                                                <TableHead>Doctor</TableHead>
                                                <TableHead className="text-right">
                                                    Action
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {group.prescriptions.flatMap(
                                                (prescription) =>
                                                    prescription.items.map(
                                                        (
                                                            item,
                                                            itemIndex,
                                                        ) => (
                                                            <TableRow
                                                                key={`${prescription.id}-${item.id}`}
                                                            >
                                                                <TableCell className="max-w-[320px]">
                                                                    <div className="flex flex-col gap-1">
                                                                        <span className="font-medium">
                                                                            {item.item_name ??
                                                                                item.generic_name ??
                                                                                'Medication'}
                                                                        </span>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            {[
                                                                                item.dosage,
                                                                                item.frequency,
                                                                                item.route,
                                                                            ].join(
                                                                                ' / ',
                                                                            )}
                                                                        </span>
                                                                        {itemIndex ===
                                                                        0 ? (
                                                                            <span className="text-xs text-muted-foreground">
                                                                                Visit{' '}
                                                                                {prescription.visit_number ??
                                                                                    '-'}
                                                                            </span>
                                                                        ) : null}
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell>
                                                                    <div className="flex flex-col gap-1">
                                                                        <span>
                                                                            Ordered:{' '}
                                                                            {item.quantity.toFixed(
                                                                                3,
                                                                            )}
                                                                        </span>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            Remaining:{' '}
                                                                            {item.remaining_quantity.toFixed(
                                                                                3,
                                                                            )}
                                                                        </span>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge
                                                                        variant="outline"
                                                                        className={badgeTone(
                                                                            item.status ??
                                                                                prescription.status,
                                                                        )}
                                                                    >
                                                                        {item.status_label ??
                                                                            prescription.status_label ??
                                                                            'Pending'}
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell>
                                                                    <div className="flex flex-col gap-1">
                                                                        <Badge
                                                                            variant="outline"
                                                                            className={badgeTone(
                                                                                item.stock_status,
                                                                            )}
                                                                        >
                                                                            {item.stock_status_label ??
                                                                                'Unknown'}
                                                                        </Badge>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            Available:{' '}
                                                                            {item.available_quantity?.toFixed(
                                                                                3,
                                                                            ) ??
                                                                                '0.000'}
                                                                        </span>
                                                                        {item.locally_dispensed_quantity >
                                                                        0 ? (
                                                                            <span className="text-xs text-muted-foreground">
                                                                                Local so far:{' '}
                                                                                {item.locally_dispensed_quantity.toFixed(
                                                                                    3,
                                                                                )}
                                                                            </span>
                                                                        ) : null}
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell className="text-sm text-muted-foreground">
                                                                    {prescription.prescription_date
                                                                        ? new Date(
                                                                              prescription.prescription_date,
                                                                          ).toLocaleString()
                                                                        : '-'}
                                                                </TableCell>
                                                                <TableCell className="text-sm text-muted-foreground">
                                                                    {prescription
                                                                        .prescribed_by
                                                                        ?.name ??
                                                                        '-'}
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {itemIndex ===
                                                                    0 ? (
                                                                        prescription.active_treatment_plan ? (
                                                                            <Button
                                                                                type="button"
                                                                                size="sm"
                                                                                variant="outline"
                                                                                asChild
                                                                            >
                                                                                <Link
                                                                                    href={`/pharmacy/treatment-plans/${prescription.active_treatment_plan.id}`}
                                                                                >
                                                                                    Open Plan
                                                                                </Link>
                                                                            </Button>
                                                                        ) : (
                                                                            <div className="flex justify-end gap-2">
                                                                                <Button
                                                                                    type="button"
                                                                                    size="sm"
                                                                                    variant="outline"
                                                                                    asChild
                                                                                >
                                                                                    <Link
                                                                                        href={`/pharmacy/prescriptions/${prescription.id}/treatment-plans/create`}
                                                                                    >
                                                                                        Treatment Plan
                                                                                    </Link>
                                                                                </Button>
                                                                                <Button
                                                                                    type="button"
                                                                                    size="sm"
                                                                                    onClick={() =>
                                                                                        setActivePrescriptionId(
                                                                                            prescription.id,
                                                                                        )
                                                                                    }
                                                                                    disabled={
                                                                                        dispensingLocations.length ===
                                                                                        0
                                                                                    }
                                                                                >
                                                                                    Dispense
                                                                                </Button>
                                                                            </div>
                                                                        )
                                                                    ) : null}
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    ),
                                            )}
                                            {group.prescriptions.map((prescription) =>
                                                prescription.active_treatment_plan ? (
                                                    <TableRow
                                                        key={`${prescription.id}-treatment-plan`}
                                                        className="bg-muted/20"
                                                    >
                                                        <TableCell
                                                            colSpan={7}
                                                            className="text-sm text-muted-foreground"
                                                        >
                                                            Staged treatment active.
                                                            Next refill:{' '}
                                                            {prescription.active_treatment_plan.next_refill_date ??
                                                                '-'}{' '}
                                                            / Cycles:{' '}
                                                            {prescription.active_treatment_plan.completed_cycles}
                                                            {' of '}
                                                            {prescription.active_treatment_plan.total_authorized_cycles}
                                                        </TableCell>
                                                    </TableRow>
                                                ) : null,
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {(prescriptions.prev_page_url ?? prescriptions.next_page_url) ? (
                    <div className="flex items-center justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            asChild={Boolean(prescriptions.prev_page_url)}
                            disabled={!prescriptions.prev_page_url}
                        >
                            {prescriptions.prev_page_url ? (
                                <Link href={prescriptions.prev_page_url}>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            asChild={Boolean(prescriptions.next_page_url)}
                            disabled={!prescriptions.next_page_url}
                        >
                            {prescriptions.next_page_url ? (
                                <Link href={prescriptions.next_page_url}>
                                    Next
                                </Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                ) : null}
            </div>

            {activePrescription ? (
                <DispenseModal
                    open
                    onOpenChange={(open) => !open && setActivePrescriptionId(null)}
                    prescription={activePrescription}
                    dispensingLocations={dispensingLocations}
                    availableBatchBalances={availableBatchBalances}
                    pharmacyPolicy={pharmacyPolicy}
                />
            ) : null}
        </AppLayout>
    );
}
