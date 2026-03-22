import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type VitalSign } from '@/types/patient';
import { Link } from '@inertiajs/react';
import { HeartPulse, NotebookPen } from 'lucide-react';
import {
    findLabel,
    formatDateTime,
    triageGradeClasses,
    vitalSummaryItems,
} from './visit-show-utils';

type ClinicalTriage = {
    triage_grade: string;
    triage_datetime: string;
    chief_complaint: string;
    history_of_presenting_illness?: string | null;
    nurse_notes?: string | null;
    nurse?: { first_name: string; last_name: string } | null;
    assignedClinic?: { name?: string | null } | null;
    assigned_clinic?: { name?: string | null } | null;
    vitalSigns?: VitalSign[];
    vital_signs?: VitalSign[];
};

type VisitClinicalTabProps = {
    visit: { id: string };
    triage: ClinicalTriage | null | undefined;
    consultation:
        | {
              started_at: string;
              primary_diagnosis?: string | null;
              doctor?: { first_name: string; last_name: string } | null;
          }
        | null
        | undefined;
    triageGrades: { value: string; label: string }[];
    canViewTriage: boolean;
    canViewConsultation: boolean;
};

export function VisitClinicalTab({
    visit,
    triage,
    consultation,
    triageGrades,
    canViewTriage,
    canViewConsultation,
}: VisitClinicalTabProps) {
    const latestVital = (triage?.vitalSigns ?? triage?.vital_signs ?? [])[0];

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Triage Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {!triage ? (
                        <div className="rounded-lg border border-dashed px-4 py-6 text-sm text-muted-foreground">
                            Triage is now managed in the dedicated triage
                            workspace for this visit.
                        </div>
                    ) : (
                        <>
                            <div className="flex flex-wrap items-center gap-3">
                                <span
                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${triageGradeClasses(triage.triage_grade)}`}
                                >
                                    {findLabel(
                                        triageGrades,
                                        triage.triage_grade,
                                    )}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    Recorded{' '}
                                    {formatDateTime(triage.triage_datetime)}
                                </span>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Chief Complaint
                                    </p>
                                    <p className="font-medium">
                                        {triage.chief_complaint}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Nurse
                                    </p>
                                    <p className="font-medium">
                                        {triage.nurse
                                            ? `${triage.nurse.first_name} ${triage.nurse.last_name}`
                                            : 'Unknown'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Assigned Clinic
                                    </p>
                                    <p className="font-medium">
                                        {triage.assignedClinic?.name ||
                                            triage.assigned_clinic?.name ||
                                            'Not assigned'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Latest Vitals
                                    </p>
                                    <p className="font-medium">
                                        {latestVital
                                            ? formatDateTime(
                                                  latestVital.recorded_at,
                                              )
                                            : 'Not yet captured'}
                                    </p>
                                </div>
                            </div>
                            <div className="grid gap-3 rounded-lg border p-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        History of Presenting Illness
                                    </p>
                                    <p className="font-medium">
                                        {triage.history_of_presenting_illness ||
                                            'Not documented'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Notes
                                    </p>
                                    <p className="font-medium">
                                        {triage.nurse_notes || 'Not documented'}
                                    </p>
                                </div>
                            </div>
                            {latestVital ? (
                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    {vitalSummaryItems(latestVital).map(
                                        (item) => (
                                            <div
                                                key={item.label}
                                                className="rounded-lg border p-3"
                                            >
                                                <p className="text-sm text-muted-foreground">
                                                    {item.label}
                                                </p>
                                                <p className="font-medium">
                                                    {item.value}
                                                </p>
                                            </div>
                                        ),
                                    )}
                                </div>
                            ) : null}
                        </>
                    )}
                    {canViewTriage ? (
                        <div className="flex justify-end">
                            <Button asChild>
                                <Link href={`/triage/${visit.id}`}>
                                    <HeartPulse className="mr-2 h-4 w-4" />
                                    {triage
                                        ? 'Continue in Triage Workspace'
                                        : 'Open Triage Workspace'}
                                </Link>
                            </Button>
                        </div>
                    ) : null}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Consultation Snapshot</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    {consultation ? (
                        <>
                            <div>
                                <p className="text-muted-foreground">Started</p>
                                <p className="font-medium">
                                    {formatDateTime(consultation.started_at)}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">
                                    Clinician
                                </p>
                                <p className="font-medium">
                                    {consultation.doctor
                                        ? `${consultation.doctor.first_name} ${consultation.doctor.last_name}`
                                        : 'Assigned clinician'}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">
                                    Primary Diagnosis
                                </p>
                                <p className="font-medium">
                                    {consultation.primary_diagnosis ||
                                        'Not documented yet'}
                                </p>
                            </div>
                            {canViewConsultation ? (
                                <div className="flex justify-end">
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={`/doctors/consultations/${visit.id}`}
                                        >
                                            <NotebookPen className="mr-2 h-4 w-4" />
                                            Continue Consultation
                                        </Link>
                                    </Button>
                                </div>
                            ) : null}
                        </>
                    ) : (
                        <p className="text-muted-foreground">
                            Consultation has not been started yet. Use the
                            dedicated doctors workspace after triage.
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
