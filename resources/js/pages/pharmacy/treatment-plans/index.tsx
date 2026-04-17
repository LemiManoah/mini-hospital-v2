import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { type PharmacyTreatmentPlansIndexPageProps } from '@/types/pharmacy';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

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

export default function PharmacyTreatmentPlansIndexPage({
    navigation,
    plans,
    filters,
    statusOptions,
    dueOptions,
}: PharmacyTreatmentPlansIndexPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [due, setDue] = useState(filters.due ?? 'all');

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                '/pharmacy/treatment-plans',
                {
                    search: search || undefined,
                    status: status === 'all' ? undefined : status,
                    due: due === 'all' ? undefined : due,
                },
                { preserveState: true, replace: true },
            );
        }, 350);

        return () => window.clearTimeout(timeout);
    }, [search, status, due]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        { title: 'Treatment Plans', href: '/pharmacy/treatment-plans' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Treatment Plans" />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold">Treatment Plans</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage staged dispensing, due refills, and completed
                            refill cycles.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Start a new plan from a patient&apos;s active
                            prescription in the pharmacy queue.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={navigation.queue_href ?? '/pharmacy/queue'}>
                            Start Treatment Plan
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search patient, visit, or drug..."
                        className="w-72"
                    />
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={due} onValueChange={setDue}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All schedules" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All schedules</SelectItem>
                            {dueOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Patient</TableHead>
                                <TableHead>Visit</TableHead>
                                <TableHead>Medicines</TableHead>
                                <TableHead>Frequency</TableHead>
                                <TableHead>Next Refill</TableHead>
                                <TableHead>Cycles</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {plans.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="py-8 text-center text-sm text-muted-foreground"
                                    >
                                        No treatment plans matched the current
                                        filters. Use Start Treatment Plan to
                                        begin one from the pharmacy queue.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                plans.data.map((plan) => (
                                    <TableRow key={plan.id}>
                                        <TableCell>
                                            <div>{plan.patient_name ?? '-'}</div>
                                            {plan.patient_number ? (
                                                <div className="text-xs text-muted-foreground">
                                                    {plan.patient_number}
                                                </div>
                                            ) : null}
                                        </TableCell>
                                        <TableCell>{plan.visit_number ?? '-'}</TableCell>
                                        <TableCell className="max-w-[260px]">
                                            <div className="flex flex-col gap-1">
                                                {plan.item_names.map((itemName) => (
                                                    <span key={`${plan.id}-${itemName}`}>
                                                        {itemName}
                                                    </span>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {plan.frequency_unit_label ?? 'Cycle'} /{' '}
                                            {plan.frequency_interval}
                                        </TableCell>
                                        <TableCell>{plan.next_refill_date ?? '-'}</TableCell>
                                        <TableCell>
                                            {plan.completed_cycles} of{' '}
                                            {plan.total_authorized_cycles}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className={badgeTone(plan.status)}
                                                >
                                                    {plan.status_label ?? 'Unknown'}
                                                </Badge>
                                                <Badge
                                                    variant="outline"
                                                    className={badgeTone(plan.due_state)}
                                                >
                                                    {plan.due_state
                                                        .replace('_', ' ')
                                                        .replace(/\b\w/g, (match) =>
                                                            match.toUpperCase(),
                                                        )}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link
                                                    href={`/pharmacy/treatment-plans/${plan.id}`}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
