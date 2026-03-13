import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type VisitShowPageProps } from '@/types/patient';
import { Form, Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    CalendarClock,
    CreditCard,
    Stethoscope,
    UserRound,
} from 'lucide-react';

function formatDate(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClasses(status: string): string {
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

export default function VisitShow({
    visit,
    availableTransitions,
}: VisitShowPageProps) {
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');
    const insurer =
        visit.payer?.insuranceCompany?.name ??
        visit.payer?.insurance_company?.name;
    const packageName =
        visit.payer?.insurancePackage?.name ??
        visit.payer?.insurance_package?.name;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Active Visits', href: '/visits' },
        { title: visit.visit_number, href: `/visits/${visit.id}` },
    ];

    const timeline = [
        {
            label: 'Registered',
            value: formatDateTime(visit.registered_at ?? visit.created_at),
        },
        { label: 'In Progress', value: formatDateTime(visit.started_at) },
        { label: 'Completed', value: formatDateTime(visit.completed_at) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Visit ${visit.visit_number}`} />

            <div className="m-4 space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                <Stethoscope className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold">
                                    Visit {visit.visit_number}
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {visit.visit_type.replaceAll('_', ' ')} for{' '}
                                    {patientName || 'Unknown patient'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(visit.status)}`}
                            >
                                {visit.status.replaceAll('_', ' ')}
                            </span>
                            <span>
                                Branch: {visit.branch?.name || 'Not assigned'}
                            </span>
                            <span>
                                Clinic: {visit.clinic?.name || 'Not assigned'}
                            </span>
                            <span>
                                Doctor:{' '}
                                {visit.doctor
                                    ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                    : 'Not assigned'}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/visits">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Active Visits
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/patients/${visit.patient?.id}`}>
                                Open Patient
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Visit Overview</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Visit Number
                                    </p>
                                    <p className="font-medium">
                                        {visit.visit_number}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Visit Type
                                    </p>
                                    <p className="font-medium">
                                        {visit.visit_type.replaceAll('_', ' ')}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Emergency
                                    </p>
                                    <p className="font-medium">
                                        {visit.is_emergency ? 'Yes' : 'No'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Registered At
                                    </p>
                                    <p className="font-medium">
                                        {formatDateTime(
                                            visit.registered_at ??
                                                visit.created_at,
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Registered By
                                    </p>
                                    <p className="font-medium">
                                        {visit.registeredBy?.name || 'Unknown'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Completed At
                                    </p>
                                    <p className="font-medium">
                                        {formatDateTime(visit.completed_at)}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Patient Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Patient
                                    </p>
                                    <p className="font-medium">
                                        {patientName || 'Unknown'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        MRN
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.patient_number || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Gender
                                    </p>
                                    <p className="font-medium capitalize">
                                        {visit.patient?.gender || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Date of Birth
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.date_of_birth
                                            ? formatDate(
                                                  visit.patient.date_of_birth,
                                              )
                                            : visit.patient?.age
                                              ? `${visit.patient.age} ${visit.patient.age_units}`
                                              : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Phone
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.phone_number || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Blood Group
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.blood_group || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Address
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.address
                                            ? `${visit.patient.address.city}${visit.patient.address.district ? `, ${visit.patient.address.district}` : ''}`
                                            : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Country
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.country?.country_name ||
                                            'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Next of Kin
                                    </p>
                                    <p className="font-medium">
                                        {visit.patient?.next_of_kin_name ||
                                            'N/A'}
                                        {visit.patient?.next_of_kin_phone
                                            ? ` • ${visit.patient.next_of_kin_phone}`
                                            : ''}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Visit Timeline</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {timeline.map((entry) => (
                                    <div
                                        key={entry.label}
                                        className="flex items-start gap-3 rounded-lg border p-3"
                                    >
                                        <div className="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                            <CalendarClock className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="font-medium">
                                                {entry.label}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {entry.value}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Payer Snapshot</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                        <CreditCard className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Billing Type
                                        </p>
                                        <p className="font-medium capitalize">
                                            {visit.payer?.billing_type ??
                                                'cash'}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Insurer
                                    </p>
                                    <p className="font-medium">
                                        {insurer || 'Not applicable'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Package
                                    </p>
                                    <p className="font-medium">
                                        {packageName || 'Not applicable'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Quick Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {availableTransitions.length > 0 ? (
                                    availableTransitions.map((transition) => (
                                        <Form
                                            key={transition.value}
                                            method="patch"
                                            action={`/visits/${visit.id}/status`}
                                        >
                                            <input
                                                type="hidden"
                                                name="status"
                                                value={transition.value}
                                            />
                                            <Button
                                                type="submit"
                                                className="w-full justify-start"
                                                variant={
                                                    transition.value ===
                                                    'cancelled'
                                                        ? 'destructive'
                                                        : 'default'
                                                }
                                            >
                                                <Activity className="mr-2 h-4 w-4" />
                                                {transition.label}
                                            </Button>
                                        </Form>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No further status actions are available
                                        for this visit.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Next Build Areas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm text-muted-foreground">
                                <div className="rounded-md border px-3 py-2">
                                    <UserRound className="mr-2 inline h-4 w-4" />
                                    Triage record and vitals
                                </div>
                                <div className="rounded-md border px-3 py-2">
                                    <Stethoscope className="mr-2 inline h-4 w-4" />
                                    Consultation notes and orders
                                </div>
                                <div className="rounded-md border px-3 py-2">
                                    <CreditCard className="mr-2 inline h-4 w-4" />
                                    Charges, billing, and payments
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
