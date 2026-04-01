import { AllergenModal } from '@/components/allergen-modal';
import { AllergyAlert } from '@/components/allergy-alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import VisitStartDialog from '@/components/visit-start-dialog';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type PatientShowPageProps } from '@/types/patient';
import { Head, Link } from '@inertiajs/react';
import { Calendar, Edit, Plus, User } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Patient Profile', href: '#' },
];

function formatDate(date: string | null): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date: string | null): string {
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

export default function PatientShow({
    patient,
    stats,
    visitTypes,
    clinics,
    doctors,
    companies,
    packages,
    hasActiveVisit,
    allergens,
    severityOptions,
    reactionOptions,
}: PatientShowPageProps) {
    const { hasPermission } = usePermissions();
    const [allergenModalOpen, setAllergenModalOpen] = useState(false);
    const fullName = [
        patient.first_name,
        patient.middle_name,
        patient.last_name,
    ]
        .filter(Boolean)
        .join(' ');
    const canStartVisit = hasPermission('visits.create');
    const canEditPatient = hasPermission('patients.update');
    const canOpenVisit = hasPermission('visits.view');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Patient: ${fullName}`} />

            <div className="m-4 space-y-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div className="flex items-center gap-3">
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-semibold">
                                    {fullName}
                                </h1>
                                <AllergyAlert allergies={patient.allergies?.map(a => ({
                                    id: a.id,
                                    allergen_name: a.allergen?.name || 'Unknown',
                                    severity: a.severity || 'unknown',
                                    reaction: a.reaction,
                                }))} />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                MRN: {patient.patient_number}
                            </p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        {canStartVisit ? (
                            <VisitStartDialog
                                patientId={patient.id}
                                patientName={fullName}
                                visitTypes={visitTypes}
                                clinics={clinics}
                                doctors={doctors}
                                companies={companies}
                                packages={packages}
                                disabled={hasActiveVisit}
                                trigger={
                                    <Button disabled={hasActiveVisit}>
                                        {hasActiveVisit
                                            ? 'Active Visit Exists'
                                            : 'Start Visit'}
                                    </Button>
                                }
                            />
                        ) : null}
                        {canEditPatient ? (
                            <Button variant="outline" asChild>
                                <Link href={`/patients/${patient.id}/edit`}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Edit Patient
                                </Link>
                            </Button>
                        ) : null}
                        <Button variant="outline" onClick={() => setAllergenModalOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Record Allergy
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Patient Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Gender
                                    </p>
                                    <p className="capitalize">
                                        {patient.gender}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Date of Birth
                                    </p>
                                    <p>
                                        {patient.date_of_birth
                                            ? formatDate(patient.date_of_birth)
                                            : patient.age
                                              ? `${patient.age} ${patient.age_units}`
                                              : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Blood Group
                                    </p>
                                    <p>
                                        {patient.blood_group || 'Not specified'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Phone
                                    </p>
                                    <p>{patient.phone_number}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Address
                                    </p>
                                    <p>
                                        {patient.address
                                            ? `${patient.address.city}${patient.address.district ? `, ${patient.address.district}` : ''}`
                                            : 'Not specified'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Next of Kin
                                    </p>
                                    <p>
                                        {patient.next_of_kin_name ||
                                            'Not specified'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Visit History</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {patient.visits.length > 0 ? (
                                    patient.visits.map((visit) => {
                                        const payer = visit.payer;
                                        const company =
                                            payer?.insuranceCompany?.name ??
                                            payer?.insurance_company?.name;
                                        const packageName =
                                            payer?.insurancePackage?.name ??
                                            payer?.insurance_package?.name;

                                        return (
                                            <div
                                                key={visit.id}
                                                className="rounded-lg border p-4"
                                            >
                                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div className="space-y-1">
                                                        <p className="font-medium">
                                                            {visit.visit_number}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {visit.visit_type.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )}{' '}
                                                            on{' '}
                                                            {formatDateTime(
                                                                visit.registered_at ??
                                                                    visit.created_at,
                                                            )}
                                                        </p>
                                                        <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                            <span>
                                                                Clinic:{' '}
                                                                {visit.clinic
                                                                    ?.name ||
                                                                    'Not assigned'}
                                                            </span>
                                                            <span>
                                                                Doctor:{' '}
                                                                {visit.doctor
                                                                    ? `${visit.doctor.first_name} ${visit.doctor.last_name}`
                                                                    : 'Not assigned'}
                                                            </span>
                                                            <span>
                                                                Payer:{' '}
                                                                {payer?.billing_type ??
                                                                    'cash'}
                                                            </span>
                                                            {company ? (
                                                                <span>
                                                                    Insurance:{' '}
                                                                    {company}
                                                                    {packageName
                                                                        ? ` / ${packageName}`
                                                                        : ''}
                                                                </span>
                                                            ) : null}
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {canOpenVisit ? (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={`/visits/${visit.id}`}
                                                                >
                                                                    Open Visit
                                                                </Link>
                                                            </Button>
                                                        ) : null}
                                                        <span
                                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(visit.status)}`}
                                                        >
                                                            {visit.status.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-10 text-center">
                                        <Calendar className="mb-3 h-10 w-10 text-muted-foreground" />
                                        <p className="text-sm text-muted-foreground">
                                            No visits recorded yet.
                                        </p>
                                        {canStartVisit ? (
                                            <VisitStartDialog
                                                patientId={patient.id}
                                                patientName={fullName}
                                                visitTypes={visitTypes}
                                                clinics={clinics}
                                                doctors={doctors}
                                                companies={companies}
                                                packages={packages}
                                                disabled={hasActiveVisit}
                                                trigger={
                                                    <Button
                                                        className="mt-4"
                                                        size="sm"
                                                        disabled={
                                                            hasActiveVisit
                                                        }
                                                    >
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        Start First Visit
                                                    </Button>
                                                }
                                            />
                                        ) : null}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Visit Statistics</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        Total Visits
                                    </span>
                                    <span className="text-2xl font-semibold">
                                        {stats.total_visits}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        Completed
                                    </span>
                                    <span className="text-2xl font-semibold text-green-600">
                                        {stats.completed_visits}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        Emergency
                                    </span>
                                    <span className="text-2xl font-semibold text-red-600">
                                        {stats.emergency_visits}
                                    </span>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Last Visit
                                    </p>
                                    <p>
                                        {stats.last_visit
                                            ? formatDate(stats.last_visit)
                                            : 'No visits yet'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Current Allergies</CardTitle>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setAllergenModalOpen(true)}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {patient.allergies.length > 0 ? (
                                    <div className="space-y-2">
                                        {patient.allergies.map((allergy) => (
                                            <div
                                                key={allergy.id}
                                                className="rounded-md border px-3 py-2 text-sm"
                                            >
                                                {allergy.allergen?.name ||
                                                    'Unknown'}
                                                {allergy.severity
                                                    ? ` (${allergy.severity})`
                                                    : ''}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No allergies recorded.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <AllergenModal
                    open={allergenModalOpen}
                    onOpenChange={setAllergenModalOpen}
                    patientId={patient.id}
                    allergens={allergens}
                    severityOptions={severityOptions}
                    reactionOptions={reactionOptions}
                />
            </div>
        </AppLayout>
    );
}
