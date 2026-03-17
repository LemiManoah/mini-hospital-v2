import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type DoctorScheduleExceptionEditPageProps } from '@/types/appointment';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

function toDateValue(value: string | null | undefined): string {
    if (!value) return '';

    return value.slice(0, 10);
}

function toTimeValue(value: string | null | undefined): string {
    if (!value) return '';

    return value.slice(0, 5);
}

const breadcrumbs = (id: string): BreadcrumbItem[] => [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Schedule Exceptions', href: '/appointments/exceptions' },
    { title: 'Edit Exception', href: `/appointments/exceptions/${id}/edit` },
];

export default function DoctorScheduleExceptionEdit({
    exception,
    doctors,
    clinics,
    typeOptions,
}: DoctorScheduleExceptionEditPageProps) {
    const [doctorId, setDoctorId] = useState(exception.doctor_id);
    const [clinicId, setClinicId] = useState(exception.clinic_id ?? 'all');
    const [type, setType] = useState(exception.type);
    const [isAllDay, setIsAllDay] = useState(exception.is_all_day);

    return (
        <AppLayout breadcrumbs={breadcrumbs(exception.id)}>
            <Head title="Edit Schedule Exception" />
            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Schedule Exception
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update blocked availability for this doctor.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/appointments/exceptions">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/appointments/exceptions/${exception.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success('Schedule exception updated successfully.')
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input type="hidden" name="doctor_id" value={doctorId} />
                                <input
                                    type="hidden"
                                    name="clinic_id"
                                    value={clinicId === 'all' ? '' : clinicId}
                                />
                                <input type="hidden" name="type" value={type} />

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label>Doctor</Label>
                                        <Select value={doctorId} onValueChange={setDoctorId}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select doctor" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {doctors.map((doctor) => (
                                                    <SelectItem key={doctor.id} value={doctor.id}>
                                                        {doctor.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.doctor_id} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Clinic</Label>
                                        <Select value={clinicId} onValueChange={setClinicId}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="All clinics" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All clinics</SelectItem>
                                                {clinics.map((clinic) => (
                                                    <SelectItem key={clinic.id} value={clinic.id}>
                                                        {clinic.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.clinic_id} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Type</Label>
                                        <Select value={type} onValueChange={setType}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {typeOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.type} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="exception_date">Exception Date</Label>
                                        <Input
                                            id="exception_date"
                                            name="exception_date"
                                            type="date"
                                            defaultValue={toDateValue(exception.exception_date)}
                                            required
                                        />
                                        <InputError message={errors.exception_date} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="start_time">Start Time</Label>
                                        <Input
                                            id="start_time"
                                            name="start_time"
                                            type="time"
                                            defaultValue={toTimeValue(exception.start_time)}
                                            disabled={isAllDay}
                                        />
                                        <InputError message={errors.start_time} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="end_time">End Time</Label>
                                        <Input
                                            id="end_time"
                                            name="end_time"
                                            type="time"
                                            defaultValue={toTimeValue(exception.end_time)}
                                            disabled={isAllDay}
                                        />
                                        <InputError message={errors.end_time} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="reason">Reason</Label>
                                        <Textarea
                                            id="reason"
                                            name="reason"
                                            rows={4}
                                            defaultValue={exception.reason ?? ''}
                                        />
                                        <InputError message={errors.reason} />
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2 xl:col-span-3">
                                        <input
                                            id="is_all_day"
                                            name="is_all_day"
                                            type="checkbox"
                                            value="1"
                                            checked={isAllDay}
                                            onChange={(event) =>
                                                setIsAllDay(event.target.checked)
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label htmlFor="is_all_day" className="font-normal">
                                            Block the entire day
                                        </Label>
                                    </div>
                                </div>

                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <Save className="mr-2 h-4 w-4" />
                                        )}
                                        Save Changes
                                    </Button>
                                    <Button variant="ghost" type="button" asChild>
                                        <Link href="/appointments/exceptions">Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
