import { AllergyAlert } from '@/components/allergy-alert';
import { Button } from '@/components/ui/button';
import { type PatientVisit } from '@/types/patient';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { statusClasses } from './visit-show-utils';

function formatDate(date: string | null | undefined): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

type VisitHeaderProps = {
    visit: PatientVisit;
    canViewPatient: boolean;
    canViewTriage: boolean;
    canViewConsultation: boolean;
    canPrintSummary?: boolean;
};

export function VisitHeader({
    visit,
    canViewPatient,
    canViewTriage,
    canViewConsultation,
    canPrintSummary = false,
}: VisitHeaderProps) {
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');

    const allergies = (
        visit.patient?.activeAllergies ?? visit.patient?.allergies
    )?.map((a) => ({
        id: a.id,
        allergen_name: a.allergen?.name || 'Unknown',
        severity: a.severity || 'unknown',
        reaction: a.reaction,
    }));

    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div className="space-y-2">
                <div className="flex items-center gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Visit {formatDate(visit.registered_at)}
                        </h1>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <span>
                                {visit.visit_type.replaceAll('_', ' ')} for{' '}
                                {patientName || 'Unknown patient'}
                            </span>
                            <AllergyAlert allergies={allergies} />
                        </div>
                    </div>
                </div>
                <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                    <span
                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(visit.status)}`}
                    >
                        {visit.status.replaceAll('_', ' ')}
                    </span>
                    <span>Clinic: {visit.clinic?.name || 'Not assigned'}</span>
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
                {canViewPatient ? (
                    <Button variant="outline" asChild>
                        <Link href={`/patients/${visit.patient?.id}`}>
                            Patient Profile
                        </Link>
                    </Button>
                ) : null}
                {canViewTriage ? (
                    <Button asChild>
                        <Link href={`/triage/${visit.id}`}>
                            {visit.triage ? 'Open Triage Page' : 'Start Triage'}
                        </Link>
                    </Button>
                ) : null}
                {visit.triage && canViewConsultation ? (
                    <Button variant="outline" asChild>
                        <Link href={`/doctors/consultations/${visit.id}`}>
                            Open Consultation
                        </Link>
                    </Button>
                ) : null}
                {canPrintSummary ? (
                    <Button variant="outline" asChild>
                        <a
                            href={`/visits/${visit.id}/summary/print`}
                            target="_blank"
                            rel="noreferrer"
                        >
                            Print Summary
                        </a>
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
