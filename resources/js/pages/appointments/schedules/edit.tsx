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
import { type DoctorScheduleEditPageProps } from '@/types/appointment';
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

function toSelectValue(value: string | number | null | undefined): string {
    if (value === null || value === undefined) return '';

    return String(value);
}

const breadcrumbs = (id: string): BreadcrumbItem[] => [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Schedules', href: '/appointments/schedules' },
    { title: 'Edit Schedule', href: `/appointments/schedules/${id}/edit` },
];

export default function DoctorScheduleEdit({
    doctorSchedule,
    dayOptions,
    doctors,
    clinics,
}: DoctorScheduleEditPageProps) {
    const doctorsList = Array.isArray(doctors) ? doctors : [];
    const clinicsList = Array.isArray(clinics) ? clinics : [];

    const [dayOfWeek, setDayOfWeek] = useState(
        toSelectValue(doctorSchedule.day_of_week),
    );
    const [doctorId, setDoctorId] = useState(
        toSelectValue(doctorSchedule.doctor_id),
    );
    const [clinicId, setClinicId] = useState(
        toSelectValue(doctorSchedule.clinic_id),
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs(doctorSchedule.id)}>
            <Head title="Edit Doctor Schedule" />
            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Doctor Schedule
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update recurring appointment availability for this
                            doctor.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/appointments/schedules">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/appointments/schedules/${doctorSchedule.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success(
                                'Doctor schedule updated successfully.',
                            )
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="day_of_week"
                                    value={dayOfWeek}
                                />
                                <input
                                    type="hidden"
                                    name="doctor_id"
                                    value={doctorId}
                                />
                                <input
                                    type="hidden"
                                    name="clinic_id"
                                    value={clinicId}
                                />

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label>Doctor</Label>
                                        <Select
                                            value={doctorId}
                                            onValueChange={setDoctorId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select doctor" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {doctorsList.map((doctor) => (
                                                    <SelectItem
                                                        key={doctor.id}
                                                        value={doctor.id}
                                                    >
                                                        {doctor.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.doctor_id} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Clinic</Label>
                                        <Select
                                            value={clinicId}
                                            onValueChange={setClinicId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select clinic" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clinicsList.map((clinic) => (
                                                    <SelectItem
                                                        key={clinic.id}
                                                        value={clinic.id}
                                                    >
                                                        {clinic.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.clinic_id} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Day</Label>
                                        <Select
                                            value={dayOfWeek}
                                            onValueChange={setDayOfWeek}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select day" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {dayOptions.map((day) => (
                                                    <SelectItem
                                                        key={day.value}
                                                        value={day.value}
                                                    >
                                                        {day.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.day_of_week}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="start_time">
                                            Start Time
                                        </Label>
                                        <Input
                                            id="start_time"
                                            name="start_time"
                                            type="time"
                                            defaultValue={toTimeValue(
                                                doctorSchedule.start_time,
                                            )}
                                            required
                                        />
                                        <InputError
                                            message={errors.start_time}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="end_time">
                                            End Time
                                        </Label>
                                        <Input
                                            id="end_time"
                                            name="end_time"
                                            type="time"
                                            defaultValue={toTimeValue(
                                                doctorSchedule.end_time,
                                            )}
                                            required
                                        />
                                        <InputError message={errors.end_time} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="slot_duration_minutes">
                                            Slot Duration (minutes)
                                        </Label>
                                        <Input
                                            id="slot_duration_minutes"
                                            name="slot_duration_minutes"
                                            type="number"
                                            min={5}
                                            defaultValue={
                                                doctorSchedule.slot_duration_minutes
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors.slot_duration_minutes
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="max_patients">
                                            Max Patients
                                        </Label>
                                        <Input
                                            id="max_patients"
                                            name="max_patients"
                                            type="number"
                                            min={1}
                                            defaultValue={
                                                doctorSchedule.max_patients
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.max_patients}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="valid_from">
                                            Valid From
                                        </Label>
                                        <Input
                                            id="valid_from"
                                            name="valid_from"
                                            type="date"
                                            defaultValue={toDateValue(
                                                doctorSchedule.valid_from,
                                            )}
                                            required
                                        />
                                        <InputError
                                            message={errors.valid_from}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="valid_to">Valid To</Label>
                                        <Input
                                            id="valid_to"
                                            name="valid_to"
                                            type="date"
                                            defaultValue={toDateValue(
                                                doctorSchedule.valid_to,
                                            )}
                                        />
                                        <InputError message={errors.valid_to} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            rows={4}
                                            defaultValue={
                                                doctorSchedule.notes ?? ''
                                            }
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2 xl:col-span-3">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                doctorSchedule.is_active
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active for booking
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
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/appointments/schedules">
                                            Cancel
                                        </Link>
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
