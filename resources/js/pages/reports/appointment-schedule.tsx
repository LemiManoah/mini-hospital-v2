import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Head, router } from '@inertiajs/react';
import { Calendar, Download } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Appointment Schedule', href: '/reports/appointment-schedule' },
];

interface AppointmentRow {
    id: string;
    start_time: string | null;
    end_time: string | null;
    chief_complaint: string | null;
    reason_for_visit: string | null;
    is_walk_in: boolean;
    status: { value: string };
    patient: {
        patient_number: string;
        first_name: string;
        middle_name: string | null;
        last_name: string;
        phone_number: string | null;
    } | null;
    doctor: { first_name: string; last_name: string } | null;
    clinic: { clinic_name: string } | null;
    category: { name: string } | null;
    mode: { name: string; is_virtual: boolean } | null;
    branch: { name: string } | null;
}

interface ReportData {
    date: string;
    day_of_week: string;
    branch_name: string | null;
    total: number;
    by_status: Record<string, number>;
    rows: AppointmentRow[];
}

interface Props {
    report: ReportData | null;
    filters: { date: string; doctor_id?: string | null };
}

const STATUS_BADGE: Record<string, string> = {
    scheduled: 'bg-gray-100 text-gray-700',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-indigo-100 text-indigo-800',
    in_progress: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    no_show: 'bg-orange-100 text-orange-800',
    rescheduled: 'bg-yellow-100 text-yellow-800',
};

function formatTime(t: string | null): string {
    if (!t) return '—';
    return t.slice(0, 5);
}

function fullName(p: AppointmentRow['patient'] | null): string {
    if (!p) return '—';
    return [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(' ');
}

export default function AppointmentScheduleReport({ report, filters }: Props) {
    const [date, setDate] = useState(
        filters.date ?? new Date().toISOString().slice(0, 10),
    );

    function apply() {
        router.get(
            '/reports/appointment-schedule',
            { date },
            { preserveScroll: true },
        );
    }

    function downloadPdf() {
        window.location.href = `/reports/appointment-schedule/download?date=${date}`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointment Schedule" />
            <div className="space-y-6 p-6">
                <div className="flex items-center gap-2">
                    <Calendar className="h-6 w-6 text-blue-600" />
                    <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                        Appointment Schedule
                    </h1>
                </div>

                {/* Filter bar */}
                <div className="flex flex-wrap items-end gap-4 rounded-lg border bg-white p-4 dark:bg-gray-900">
                    <div className="space-y-1">
                        <Label htmlFor="date">Date</Label>
                        <Input
                            id="date"
                            type="date"
                            value={date}
                            onChange={(e) => setDate(e.target.value)}
                            className="w-44"
                        />
                    </div>
                    <Button onClick={apply}>Apply</Button>
                    <Button
                        variant="outline"
                        onClick={downloadPdf}
                        className="ml-auto gap-2"
                    >
                        <Download className="h-4 w-4" />
                        Download PDF
                    </Button>
                </div>

                {report ? (
                    <>
                        {/* KPI cards */}
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                                        Total
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold text-blue-600">
                                        {report.total}
                                    </p>
                                    <p className="text-xs text-gray-400">
                                        {report.day_of_week}
                                    </p>
                                </CardContent>
                            </Card>
                            {Object.entries(report.by_status).map(
                                ([status, count]) => (
                                    <Card key={status}>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-xs font-medium tracking-wide text-gray-500 capitalize uppercase">
                                                {status.replace(/_/g, ' ')}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="text-2xl font-bold">
                                                {count}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ),
                            )}
                        </div>

                        {/* Appointments table */}
                        <div className="rounded-lg border bg-white dark:bg-gray-900">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-8">#</TableHead>
                                        <TableHead>Time</TableHead>
                                        <TableHead>Patient</TableHead>
                                        <TableHead>Doctor</TableHead>
                                        <TableHead>Clinic</TableHead>
                                        <TableHead>Category</TableHead>
                                        <TableHead>Mode</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Complaint</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report.rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={9}
                                                className="py-10 text-center text-gray-400"
                                            >
                                                No appointments scheduled for
                                                this date.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        report.rows.map((row, i) => {
                                            const statusVal =
                                                row.status?.value ??
                                                'scheduled';
                                            return (
                                                <TableRow key={row.id}>
                                                    <TableCell className="text-gray-400">
                                                        {i + 1}
                                                    </TableCell>
                                                    <TableCell className="text-sm font-medium whitespace-nowrap">
                                                        {formatTime(
                                                            row.start_time,
                                                        )}
                                                        {row.end_time && (
                                                            <span className="text-xs text-gray-400">
                                                                {' '}
                                                                –{' '}
                                                                {formatTime(
                                                                    row.end_time,
                                                                )}
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="font-medium">
                                                            {fullName(
                                                                row.patient,
                                                            )}
                                                        </span>
                                                        {row.patient
                                                            ?.patient_number && (
                                                            <span className="block text-xs text-gray-400">
                                                                {
                                                                    row.patient
                                                                        .patient_number
                                                                }
                                                            </span>
                                                        )}
                                                        {row.is_walk_in && (
                                                            <Badge className="mt-0.5 bg-yellow-100 text-xs text-yellow-800 hover:bg-yellow-100">
                                                                Walk-in
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-sm">
                                                        {row.doctor
                                                            ? `${row.doctor.first_name} ${row.doctor.last_name}`
                                                            : '—'}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {row.clinic
                                                            ?.clinic_name ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {row.category?.name ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {row.mode?.name ?? '—'}
                                                        {row.mode
                                                            ?.is_virtual && (
                                                            <Badge className="ml-1 bg-blue-100 text-xs text-blue-800 hover:bg-blue-100">
                                                                Virtual
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            className={`text-xs ${STATUS_BADGE[statusVal] ?? 'bg-gray-100 text-gray-700'} hover:opacity-90`}
                                                        >
                                                            {statusVal.replace(
                                                                /_/g,
                                                                ' ',
                                                            )}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="max-w-[140px] truncate text-xs">
                                                        {row.chief_complaint ??
                                                            row.reason_for_visit ??
                                                            '—'}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                ) : (
                    <div className="rounded-lg border bg-white p-12 text-center text-gray-400 dark:bg-gray-900">
                        Select a date and click Apply to load the report.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
