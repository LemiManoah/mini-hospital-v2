import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    HeartPulse,
    NotebookPen,
    Stethoscope,
    UserRound,
} from 'lucide-react';
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
    visit: {
        id: string;
        visit_number: string;
        visit_type: string;
        status: string;
        registered_at: string | null;
        clinic?: { name?: string | null } | null;
        doctor?: { first_name: string; last_name: string } | null;
        patient?: {
            id: string;
            first_name: string;
            middle_name?: string | null;
            last_name: string;
        } | null;
        triage?: object | null;
    };
    canViewPatient: boolean;
    canViewTriage: boolean;
    canViewConsultation: boolean;
};

export function VisitHeader({
    visit,
    canViewPatient,
    canViewTriage,
    canViewConsultation,
}: VisitHeaderProps) {
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div className="space-y-2">
                <div className="flex items-center gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Visit {formatDate(visit.registered_at)}
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
                            <UserRound className="mr-2 h-4 w-4" />
                            Patient Profile
                        </Link>
                    </Button>
                ) : null}
                {canViewTriage ? (
                    <Button asChild>
                        <Link href={`/triage/${visit.id}`}>
                            <HeartPulse className="mr-2 h-4 w-4" />
                            {visit.triage ? 'Open Triage Page' : 'Start Triage'}
                        </Link>
                    </Button>
                ) : null}
                {visit.triage && canViewConsultation ? (
                    <Button variant="outline" asChild>
                        <Link href={`/doctors/consultations/${visit.id}`}>
                            <NotebookPen className="mr-2 h-4 w-4" />
                            Open Consultation
                        </Link>
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
