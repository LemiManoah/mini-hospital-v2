import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

    const activePrescription =
        prescriptions.data.find(
            (prescription) => prescription.id === activePrescriptionId,
        ) ?? null;
    const patientGroups = groupPrescriptionsByPatient(prescriptions.data);

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
                            Review incoming prescriptions and dispense straight
                            from this queue when the medication is ready.
                        </p>
                        <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
                            <span>
                                Dispensing locations:{' '}
                                {dispensingLocations.length}
                            </span>
                            <span>
                                Patients in queue: {patientGroups.length}
                            </span>
                            <span>
                                Active prescriptions: {prescriptions.total}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient, visit, diagnosis, or drug..."
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
                            <Card key={group.key}>
                                <CardHeader className="gap-4">
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <CardTitle className="text-lg">
                                                    {group.patient?.full_name ??
                                                        'Unknown patient'}
                                                </CardTitle>
                                                <Badge variant="outline">
                                                    {group.prescriptions.length}{' '}
                                                    active
                                                    {group.prescriptions.length ===
                                                    1
                                                        ? ' prescription'
                                                        : ' prescriptions'}
                                                </Badge>
                                            </div>
                                            <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
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
                                        <div className="text-sm text-muted-foreground">
                                            Dispense per prescription below.
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {group.prescriptions.map((prescription) => (
                                        <section
                                            key={prescription.id}
                                            className="space-y-3 rounded-xl border p-4"
                                        >
                                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge
                                                            variant="outline"
                                                            className={badgeTone(
                                                                prescription.status,
                                                            )}
                                                        >
                                                            {prescription.status_label ??
                                                                'Unknown'}
                                                        </Badge>
                                                        <Badge
                                                            variant="outline"
                                                            className={badgeTone(
                                                                prescription
                                                                    .availability
                                                                    .status,
                                                            )}
                                                        >
                                                            {
                                                                prescription
                                                                    .availability
                                                                    .label
                                                            }
                                                        </Badge>
                                                    </div>
                                                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                                        <span>
                                                            Visit:{' '}
                                                            {prescription.visit_number ??
                                                                '-'}
                                                        </span>
                                                        <span>
                                                            Prescribed:{' '}
                                                            {prescription.prescription_date
                                                                ? new Date(
                                                                      prescription.prescription_date,
                                                                  ).toLocaleString()
                                                                : '-'}
                                                        </span>
                                                        <span>
                                                            Doctor:{' '}
                                                            {prescription
                                                                .prescribed_by
                                                                ?.name ?? '-'}
                                                        </span>
                                                    </div>
                                                    {prescription.primary_diagnosis ? (
                                                        <p className="text-sm">
                                                            Diagnosis:{' '}
                                                            <span className="text-muted-foreground">
                                                                {
                                                                    prescription.primary_diagnosis
                                                                }
                                                            </span>
                                                        </p>
                                                    ) : null}
                                                    {prescription.pharmacy_notes ? (
                                                        <p className="text-sm text-muted-foreground">
                                                            {
                                                                prescription.pharmacy_notes
                                                            }
                                                        </p>
                                                    ) : null}
                                                </div>

                                                <div className="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                                                    <div className="text-right text-sm text-muted-foreground">
                                                        <div>
                                                            Ready lines:{' '}
                                                            {
                                                                prescription
                                                                    .availability
                                                                    .ready_items
                                                            }
                                                        </div>
                                                        <div>
                                                            Partial:{' '}
                                                            {
                                                                prescription
                                                                    .availability
                                                                    .partial_items
                                                            }
                                                        </div>
                                                        <div>
                                                            Out of stock:{' '}
                                                            {
                                                                prescription
                                                                    .availability
                                                                    .out_of_stock_items
                                                            }
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            type="button"
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
                                                        <Button
                                                            variant="outline"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/pharmacy/prescriptions/${prescription.id}`}
                                                            >
                                                                Review
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="space-y-3">
                                                {prescription.items.map((item) => (
                                                    <div
                                                        key={item.id}
                                                        className="flex flex-col gap-2 rounded-lg border p-3 lg:flex-row lg:items-center lg:justify-between"
                                                    >
                                                        <div className="space-y-1">
                                                            <div className="font-medium">
                                                                {item.item_name ??
                                                                    item.generic_name ??
                                                                    'Medication'}
                                                            </div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {item.dosage} /{' '}
                                                                {item.frequency}{' '}
                                                                / {item.route} /
                                                                Qty{' '}
                                                                {item.quantity}
                                                            </div>
                                                            {item.instructions ? (
                                                                <div className="text-sm text-muted-foreground">
                                                                    {
                                                                        item.instructions
                                                                    }
                                                                </div>
                                                            ) : null}
                                                        </div>
                                                        <div className="flex flex-wrap items-center gap-2 text-sm">
                                                            <Badge
                                                                variant="outline"
                                                                className={badgeTone(
                                                                    item.status,
                                                                )}
                                                            >
                                                                {item.status_label ??
                                                                    'Pending'}
                                                            </Badge>
                                                            <Badge
                                                                variant="outline"
                                                                className={badgeTone(
                                                                    item.stock_status,
                                                                )}
                                                            >
                                                                {item.stock_status_label ??
                                                                    'Unknown'}
                                                            </Badge>
                                                            <span className="text-muted-foreground">
                                                                Available:{' '}
                                                                {item.available_quantity?.toFixed(
                                                                    3,
                                                                ) ?? '0.000'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </section>
                                    ))}
                                </CardContent>
                            </Card>
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
