import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PharmacyPrescriptionShowPageProps } from '@/types/pharmacy';
import { Head, Link } from '@inertiajs/react';
import { Printer } from 'lucide-react';

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

export default function PharmacyPrescriptionShowPage({
    navigation,
    prescription,
}: PharmacyPrescriptionShowPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.queue_title ?? 'Pharmacy Queue',
            href: navigation.queue_href ?? '/pharmacy/queue',
        },
        {
            title: prescription.visit_number ?? 'Prescription',
            href: `/pharmacy/prescriptions/${prescription.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Prescription ${prescription.visit_number ?? ''}`} />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold">
                                {prescription.patient?.full_name ??
                                    'Prescription'}
                            </h1>
                            <Badge
                                variant="outline"
                                className={badgeTone(prescription.status)}
                            >
                                {prescription.status_label ?? 'Unknown'}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span>
                                Visit: {prescription.visit_number ?? '-'}
                            </span>
                            <span>
                                Patient No:{' '}
                                {prescription.patient?.patient_number ?? '-'}
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
                                {prescription.prescribed_by?.name ?? '-'}
                            </span>
                        </div>
                        {prescription.primary_diagnosis ? (
                            <p className="text-sm">
                                Diagnosis:{' '}
                                <span className="text-muted-foreground">
                                    {prescription.primary_diagnosis}
                                </span>
                            </p>
                        ) : null}
                        {prescription.pharmacy_notes ? (
                            <p className="text-sm text-muted-foreground">
                                {prescription.pharmacy_notes}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={`/prescriptions/${prescription.id}/print`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Printer className="mr-2 h-4 w-4" />
                                Print Prescription
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={
                                    navigation.queue_href ?? '/pharmacy/queue'
                                }
                            >
                                Back To Queue
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Medication Lines</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {prescription.items.map((item) => (
                            <div
                                key={item.id}
                                className="flex flex-col gap-3 rounded-lg border p-4 lg:flex-row lg:items-start lg:justify-between"
                            >
                                <div className="space-y-1">
                                    <div className="font-medium">
                                        {item.item_name ??
                                            item.generic_name ??
                                            'Medication'}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {item.dosage} / {item.frequency} /{' '}
                                        {item.route} / {item.duration_days} days
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Ordered: {item.quantity.toFixed(3)} /
                                        Remaining:{' '}
                                        {item.remaining_quantity.toFixed(3)} /
                                        Available:{' '}
                                        {item.available_quantity?.toFixed(3) ??
                                            '0.000'}
                                    </div>
                                    {item.locally_dispensed_quantity > 0 ? (
                                        <div className="text-sm text-muted-foreground">
                                            Already dispensed locally:{' '}
                                            {item.locally_dispensed_quantity.toFixed(
                                                3,
                                            )}
                                        </div>
                                    ) : null}
                                    {item.external_pharmacy ? (
                                        <div className="text-sm text-muted-foreground">
                                            A previous remainder for this line
                                            was handled through an external
                                            pharmacy.
                                        </div>
                                    ) : null}
                                    {item.instructions ? (
                                        <div className="text-sm text-muted-foreground">
                                            {item.instructions}
                                        </div>
                                    ) : null}
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Badge
                                        variant="outline"
                                        className={badgeTone(item.status)}
                                    >
                                        {item.status_label ?? 'Pending'}
                                    </Badge>
                                    <Badge
                                        variant="outline"
                                        className={badgeTone(item.stock_status)}
                                    >
                                        {item.stock_status_label ?? 'Unknown'}
                                    </Badge>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Dispensing</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="text-sm text-muted-foreground">
                            Use the pharmacy queue to dispense this prescription
                            directly. The queue modal is now the main dispense
                            workflow.
                        </div>

                        {prescription.dispensing_records &&
                        prescription.dispensing_records.length > 0 ? (
                            <div className="space-y-3 border-t pt-4">
                                <div className="font-medium">
                                    Existing Dispense Records
                                </div>
                                {prescription.dispensing_records.map(
                                    (record) => (
                                        <div
                                            key={record.id}
                                            className="flex flex-col gap-2 rounded-lg border p-3 lg:flex-row lg:items-center lg:justify-between"
                                        >
                                            <div className="space-y-1 text-sm">
                                                <div className="font-medium">
                                                    {record.dispense_number}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {record.inventory_location
                                                        ?.name ?? '-'}{' '}
                                                    /{' '}
                                                    {record.dispensed_at
                                                        ? new Date(
                                                              record.dispensed_at,
                                                          ).toLocaleString()
                                                        : '-'}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    Prepared by{' '}
                                                    {record.dispensed_by ?? '-'}
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className={badgeTone(
                                                        record.status,
                                                    )}
                                                >
                                                    {record.status_label ??
                                                        'Unknown'}
                                                </Badge>
                                                <Button
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/pharmacy/dispenses/${record.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    ),
                                )}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                No dispense has been posted for this
                                prescription yet.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
