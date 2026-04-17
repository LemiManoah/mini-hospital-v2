import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { type PharmacyTreatmentPlanShowPageProps } from '@/types/pharmacy';
import { Head, Link } from '@inertiajs/react';

const badgeTone = (value: string | null | undefined): string => {
    switch (value) {
        case 'active':
        case 'due_today':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'upcoming':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'overdue':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'completed':
            return 'border-slate-200 bg-slate-50 text-slate-700';
        case 'cancelled':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
};

export default function PharmacyTreatmentPlanShowPage({
    navigation,
    treatmentPlan,
}: PharmacyTreatmentPlanShowPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: 'Treatment Plans', href: '/pharmacy/treatment-plans' },
        { title: treatmentPlan.visit_number ?? 'Treatment Plan', href: `/pharmacy/treatment-plans/${treatmentPlan.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Treatment Plan ${treatmentPlan.visit_number ?? ''}`} />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold">
                                {treatmentPlan.patient?.full_name ?? 'Treatment Plan'}
                            </h1>
                            <Badge
                                variant="outline"
                                className={badgeTone(treatmentPlan.status)}
                            >
                                {treatmentPlan.status_label ?? 'Unknown'}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span>Visit: {treatmentPlan.visit_number ?? '-'}</span>
                            <span>
                                Patient No:{' '}
                                {treatmentPlan.patient?.patient_number ?? '-'}
                            </span>
                            <span>
                                Doctor:{' '}
                                {treatmentPlan.prescribed_by?.name ?? '-'}
                            </span>
                            <span>
                                Next refill:{' '}
                                {treatmentPlan.next_refill_date ?? '-'}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {treatmentPlan.next_pending_cycle ? (
                            <Button asChild>
                                <Link
                                    href={`/pharmacy/treatment-plans/${treatmentPlan.id}/cycles/${treatmentPlan.next_pending_cycle.id}/dispense`}
                                >
                                    Dispense Next Cycle
                                </Link>
                            </Button>
                        ) : null}
                        <Button variant="outline" asChild>
                            <Link href="/pharmacy/treatment-plans">Back To Plans</Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Plan Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm md:grid-cols-2">
                        <div>
                            Start date: {treatmentPlan.start_date ?? '-'}
                        </div>
                        <div>
                            Frequency: {treatmentPlan.frequency_unit_label ?? '-'} /{' '}
                            {treatmentPlan.frequency_interval}
                        </div>
                        <div>
                            Cycles: {treatmentPlan.completed_cycles} of{' '}
                            {treatmentPlan.total_authorized_cycles}
                        </div>
                        <div>
                            Next refill: {treatmentPlan.next_refill_date ?? '-'}
                        </div>
                        {treatmentPlan.notes ? (
                            <div className="text-muted-foreground md:col-span-2">
                                {treatmentPlan.notes}
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Cycle Quantities</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Medication</TableHead>
                                    <TableHead>Per Cycle</TableHead>
                                    <TableHead>Total Authorized</TableHead>
                                    <TableHead>Cycles Done</TableHead>
                                    <TableHead>Cycles Remaining</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {treatmentPlan.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            {item.generic_name ?? item.item_name ?? 'Medication'}
                                        </TableCell>
                                        <TableCell>
                                            {item.quantity_per_cycle.toFixed(3)}
                                        </TableCell>
                                        <TableCell>
                                            {item.authorized_total_quantity?.toFixed(3) ?? '-'}
                                        </TableCell>
                                        <TableCell>{item.completed_cycles ?? '-'}</TableCell>
                                        <TableCell>{item.remaining_cycles ?? '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Cycle History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cycle</TableHead>
                                    <TableHead>Scheduled</TableHead>
                                    <TableHead>State</TableHead>
                                    <TableHead>Completed</TableHead>
                                    <TableHead>Dispense Record</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {treatmentPlan.cycles.map((cycle) => (
                                    <TableRow key={cycle.id}>
                                        <TableCell>{cycle.cycle_number}</TableCell>
                                        <TableCell>{cycle.scheduled_for ?? '-'}</TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={badgeTone(cycle.state ?? cycle.status)}
                                            >
                                                {(cycle.state ?? cycle.status_label ?? 'Unknown')
                                                    .replace('_', ' ')
                                                    .replace(/\b\w/g, (match) =>
                                                        match.toUpperCase(),
                                                    )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {cycle.completed_at
                                                ? new Date(
                                                      cycle.completed_at,
                                                  ).toLocaleString()
                                                : '-'}
                                        </TableCell>
                                        <TableCell>
                                            {cycle.dispensing_record ? (
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link
                                                        href={`/pharmacy/dispenses/${cycle.dispensing_record.id}`}
                                                    >
                                                        {cycle.dispensing_record.dispense_number}
                                                    </Link>
                                                </Button>
                                            ) : (
                                                '-'
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
