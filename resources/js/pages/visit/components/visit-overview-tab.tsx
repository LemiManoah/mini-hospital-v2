import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CalendarClock } from 'lucide-react';
import { formatDate, formatDateTime } from './visit-show-utils';

type VisitOverviewTabProps = {
    visit: {
        visit_number: string;
        visit_type: string;
        is_emergency: boolean;
        registered_at: string | null;
        created_at: string;
        completed_at: string | null;
        registeredBy?: { name?: string | null } | null;
        registered_by?: { name?: string | null } | null;
        patient?: {
            first_name: string;
            middle_name?: string | null;
            last_name: string;
            patient_number?: string | null;
            gender?: string | null;
            date_of_birth?: string | null;
            age?: number | null;
            age_units?: string | null;
            phone_number?: string | null;
            blood_group?: string | null;
        } | null;
    };
    timeline: { label: string; value: string }[];
};

export function VisitOverviewTab({ visit, timeline }: VisitOverviewTabProps) {
    const patientName = [
        visit.patient?.first_name,
        visit.patient?.middle_name,
        visit.patient?.last_name,
    ]
        .filter(Boolean)
        .join(' ');

    return (
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
                        <p className="font-medium">{visit.visit_number}</p>
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
                                visit.registered_at ?? visit.created_at,
                            )}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Registered By
                        </p>
                        <p className="font-medium">
                            {visit.registeredBy?.name ||
                                visit.registered_by?.name ||
                                'Unknown'}
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
                        <p className="text-sm text-muted-foreground">Patient</p>
                        <p className="font-medium">
                            {patientName || 'Unknown'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">MRN</p>
                        <p className="font-medium">
                            {visit.patient?.patient_number || 'N/A'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Gender</p>
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
                                ? formatDate(visit.patient.date_of_birth)
                                : visit.patient?.age
                                  ? `${visit.patient.age} ${visit.patient.age_units}`
                                  : 'N/A'}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-muted-foreground">Phone</p>
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
                                <p className="font-medium">{entry.label}</p>
                                <p className="text-sm text-muted-foreground">
                                    {entry.value}
                                </p>
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </div>
    );
}
