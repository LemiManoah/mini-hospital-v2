import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { type AppointmentShowPageProps } from '@/types/appointment';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
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

function toSelectValue(value: string | number | null | undefined, fallback = 'none'): string {
    if (value === null || value === undefined || value === '') return fallback;

    return String(value);
}

const breadcrumbs = (appointmentId: string): BreadcrumbItem[] => [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Appointment Details', href: `/appointments/${appointmentId}` },
];

export default function AppointmentShow({
    appointment,
    doctors,
    clinics,
    appointmentCategories,
    appointmentModes,
    visitTypes,
    billingTypes,
    insuranceCompanies,
    insurancePackages,
}: AppointmentShowPageProps) {
    const [doctorId, setDoctorId] = useState(toSelectValue(appointment.doctor_id));
    const [clinicId, setClinicId] = useState(toSelectValue(appointment.clinic_id));
    const [categoryId, setCategoryId] = useState(
        toSelectValue(appointment.appointment_category_id),
    );
    const [modeId, setModeId] = useState(
        toSelectValue(appointment.appointment_mode_id),
    );
    const [visitType, setVisitType] = useState(visitTypes[0]?.value ?? '');
    const [billingType, setBillingType] = useState(billingTypes[0]?.value ?? '');
    const [insuranceCompanyId, setInsuranceCompanyId] = useState('none');
    const [insurancePackageId, setInsurancePackageId] = useState('none');

    const filteredPackages =
        billingType === 'insurance' && insuranceCompanyId !== 'none'
            ? insurancePackages.filter(
                  (item) => item.insurance_company_id === insuranceCompanyId,
              )
            : insurancePackages;

    const canCheckIn =
        !appointment.visit &&
        !['cancelled', 'completed', 'no_show'].includes(appointment.status);

    return (
        <AppLayout breadcrumbs={breadcrumbs(appointment.id)}>
            <Head title="Appointment Details" />
            <div className="m-4 space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-semibold">Appointment Details</h1>
                            <Badge variant="secondary">
                                {appointment.status.replaceAll('_', ' ')}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {appointment.patient?.name ?? 'Unknown patient'} ·{' '}
                            {appointment.patient?.patient_number ?? 'No patient number'}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/appointments">Back</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/patients/${appointment.patient_id}`}>
                                Patient Profile
                            </Link>
                        </Button>
                        {appointment.visit ? (
                            <Button asChild>
                                <Link href={`/visits/${appointment.visit.id}`}>Open Visit</Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                    <div className="space-y-6">
                        <div className="rounded border bg-white p-6 shadow-sm dark:bg-zinc-900">
                            <h2 className="text-lg font-semibold">Summary</h2>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <p className="text-xs uppercase text-muted-foreground">Date</p>
                                    <p>{toDateValue(appointment.appointment_date)}</p>
                                </div>
                                <div>
                                    <p className="text-xs uppercase text-muted-foreground">Time</p>
                                    <p>
                                        {toTimeValue(appointment.start_time)}
                                        {appointment.end_time
                                            ? ` - ${toTimeValue(appointment.end_time)}`
                                            : ''}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs uppercase text-muted-foreground">Doctor</p>
                                    <p>
                                        {appointment.doctor?.name ||
                                            `${appointment.doctor?.first_name ?? ''} ${appointment.doctor?.last_name ?? ''}`.trim() ||
                                            'Unassigned'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs uppercase text-muted-foreground">Clinic</p>
                                    <p>
                                        {appointment.clinic?.name ||
                                            appointment.clinic?.clinic_name ||
                                            'Unassigned'}
                                    </p>
                                </div>
                                <div className="md:col-span-2">
                                    <p className="text-xs uppercase text-muted-foreground">Reason</p>
                                    <p>{appointment.reason_for_visit}</p>
                                </div>
                                {appointment.chief_complaint ? (
                                    <div className="md:col-span-2">
                                        <p className="text-xs uppercase text-muted-foreground">
                                            Chief Complaint
                                        </p>
                                        <p>{appointment.chief_complaint}</p>
                                    </div>
                                ) : null}
                                {appointment.cancellation_reason ? (
                                    <div className="md:col-span-2">
                                        <p className="text-xs uppercase text-muted-foreground">
                                            Cancellation Reason
                                        </p>
                                        <p>{appointment.cancellation_reason}</p>
                                    </div>
                                ) : null}
                            </div>
                        </div>

                        <div className="rounded border bg-white p-6 shadow-sm dark:bg-zinc-900">
                            <h2 className="text-lg font-semibold">Update Booking</h2>
                            <Form
                                action={`/appointments/${appointment.id}`}
                                method="put"
                                onSuccess={() => toast.success('Appointment updated successfully.')}
                                className="mt-4 space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="doctor_id"
                                            value={doctorId === 'none' ? '' : doctorId}
                                        />
                                        <input
                                            type="hidden"
                                            name="clinic_id"
                                            value={clinicId === 'none' ? '' : clinicId}
                                        />
                                        <input
                                            type="hidden"
                                            name="appointment_category_id"
                                            value={categoryId === 'none' ? '' : categoryId}
                                        />
                                        <input
                                            type="hidden"
                                            name="appointment_mode_id"
                                            value={modeId === 'none' ? '' : modeId}
                                        />

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label>Doctor</Label>
                                                <Select value={doctorId} onValueChange={setDoctorId}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select doctor" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none">Unassigned</SelectItem>
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
                                                        <SelectValue placeholder="Select clinic" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none">Unassigned</SelectItem>
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
                                                <Label>Category</Label>
                                                <Select value={categoryId} onValueChange={setCategoryId}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select category" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none">No category</SelectItem>
                                                        {appointmentCategories.map((category) => (
                                                            <SelectItem key={category.id} value={category.id}>
                                                                {category.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={errors.appointment_category_id}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Mode</Label>
                                                <Select value={modeId} onValueChange={setModeId}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select mode" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none">No mode</SelectItem>
                                                        {appointmentModes.map((mode) => (
                                                            <SelectItem key={mode.id} value={mode.id}>
                                                                {mode.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.appointment_mode_id} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="appointment_date">Appointment Date</Label>
                                                <Input
                                                    id="appointment_date"
                                                    name="appointment_date"
                                                    type="date"
                                                    defaultValue={toDateValue(appointment.appointment_date)}
                                                    required
                                                />
                                                <InputError message={errors.appointment_date} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="start_time">Start Time</Label>
                                                <Input
                                                    id="start_time"
                                                    name="start_time"
                                                    type="time"
                                                    defaultValue={toTimeValue(appointment.start_time)}
                                                    required
                                                />
                                                <InputError message={errors.start_time} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="end_time">End Time</Label>
                                                <Input
                                                    id="end_time"
                                                    name="end_time"
                                                    type="time"
                                                    defaultValue={toTimeValue(appointment.end_time)}
                                                />
                                                <InputError message={errors.end_time} />
                                            </div>
                                            <div className="grid gap-2 md:col-span-2">
                                                <Label htmlFor="reason_for_visit">Reason for Visit</Label>
                                                <Textarea
                                                    id="reason_for_visit"
                                                    name="reason_for_visit"
                                                    rows={4}
                                                    defaultValue={appointment.reason_for_visit}
                                                    required
                                                />
                                                <InputError message={errors.reason_for_visit} />
                                            </div>
                                            <div className="grid gap-2 md:col-span-2">
                                                <Label htmlFor="chief_complaint">Chief Complaint</Label>
                                                <Input
                                                    id="chief_complaint"
                                                    name="chief_complaint"
                                                    defaultValue={appointment.chief_complaint ?? ''}
                                                />
                                                <InputError message={errors.chief_complaint} />
                                            </div>
                                            <div className="grid gap-2 md:col-span-2">
                                                <Label htmlFor="notes">Notes</Label>
                                                <Textarea
                                                    id="notes"
                                                    name="notes"
                                                    rows={3}
                                                    defaultValue={appointment.notes ?? ''}
                                                />
                                                <InputError message={errors.notes} />
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <input
                                                id="is_walk_in"
                                                name="is_walk_in"
                                                type="checkbox"
                                                value="1"
                                                defaultChecked={appointment.is_walk_in}
                                                className="h-4 w-4"
                                            />
                                            <Label htmlFor="is_walk_in" className="font-normal">
                                                Walk-in appointment
                                            </Label>
                                        </div>

                                        <Button type="submit" disabled={processing}>
                                            {processing ? (
                                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                            ) : null}
                                            Save Changes
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded border bg-white p-6 shadow-sm dark:bg-zinc-900">
                            <h2 className="text-lg font-semibold">Status Actions</h2>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Form
                                    action={`/appointments/${appointment.id}/confirm`}
                                    method="post"
                                    onSuccess={() => toast.success('Appointment confirmed successfully.')}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing ||
                                                !['scheduled', 'rescheduled'].includes(
                                                    appointment.status,
                                                )
                                            }
                                        >
                                            Confirm
                                        </Button>
                                    )}
                                </Form>
                                <Form
                                    action={`/appointments/${appointment.id}/no-show`}
                                    method="post"
                                    onSuccess={() => toast.success('Appointment marked as no-show.')}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={
                                                processing ||
                                                !['scheduled', 'confirmed', 'rescheduled'].includes(
                                                    appointment.status,
                                                )
                                            }
                                        >
                                            Mark No-Show
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </div>

                        <div className="rounded border bg-white p-6 shadow-sm dark:bg-zinc-900">
                            <h2 className="text-lg font-semibold">Reschedule</h2>
                            <Form
                                action={`/appointments/${appointment.id}/reschedule`}
                                method="post"
                                onSuccess={() => toast.success('Appointment rescheduled successfully.')}
                                className="mt-4 space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="reschedule_date">New Date</Label>
                                                <Input
                                                    id="reschedule_date"
                                                    name="appointment_date"
                                                    type="date"
                                                    defaultValue={toDateValue(appointment.appointment_date)}
                                                    required
                                                />
                                                <InputError message={errors.appointment_date} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="reschedule_start_time">Start Time</Label>
                                                <Input
                                                    id="reschedule_start_time"
                                                    name="start_time"
                                                    type="time"
                                                    defaultValue={toTimeValue(appointment.start_time)}
                                                    required
                                                />
                                                <InputError message={errors.start_time} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="reschedule_end_time">End Time</Label>
                                                <Input
                                                    id="reschedule_end_time"
                                                    name="end_time"
                                                    type="time"
                                                    defaultValue={toTimeValue(appointment.end_time)}
                                                />
                                                <InputError message={errors.end_time} />
                                            </div>
                                        </div>
                                        <Button type="submit" variant="outline" disabled={processing}>
                                            Reschedule
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </div>

                        <div className="rounded border bg-white p-6 shadow-sm dark:bg-zinc-900">
                            <h2 className="text-lg font-semibold">Cancel Appointment</h2>
                            <Form
                                action={`/appointments/${appointment.id}/cancel`}
                                method="post"
                                onSuccess={() => toast.success('Appointment cancelled successfully.')}
                                className="mt-4 space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="cancellation_reason">Reason</Label>
                                            <Textarea
                                                id="cancellation_reason"
                                                name="cancellation_reason"
                                                rows={3}
                                                defaultValue={appointment.cancellation_reason ?? ''}
                                            />
                                            <InputError message={errors.cancellation_reason} />
                                        </div>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            Cancel Appointment
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </div>

                        <div className="rounded border bg-white p-6 shadow-sm dark:bg-zinc-900">
                            <h2 className="text-lg font-semibold">Check In to Visit</h2>
                            {appointment.visit ? (
                                <div className="mt-4 space-y-3">
                                    <p className="text-sm">
                                        Linked visit: {appointment.visit.visit_number}
                                    </p>
                                    <Button asChild>
                                        <Link href={`/visits/${appointment.visit.id}`}>Open Visit</Link>
                                    </Button>
                                </div>
                            ) : canCheckIn ? (
                                <Form
                                    action={`/appointments/${appointment.id}/check-in`}
                                    method="post"
                                    onSuccess={() => toast.success('Appointment checked in successfully.')}
                                    className="mt-4 space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <InputError message={errors.appointment} />
                                            <input type="hidden" name="visit_type" value={visitType} />
                                            <input type="hidden" name="billing_type" value={billingType} />
                                            <input
                                                type="hidden"
                                                name="insurance_company_id"
                                                value={
                                                    billingType === 'insurance' &&
                                                    insuranceCompanyId !== 'none'
                                                        ? insuranceCompanyId
                                                        : ''
                                                }
                                            />
                                            <input
                                                type="hidden"
                                                name="insurance_package_id"
                                                value={
                                                    billingType === 'insurance' &&
                                                    insurancePackageId !== 'none'
                                                        ? insurancePackageId
                                                        : ''
                                                }
                                            />

                                            <div className="grid gap-4">
                                                <div className="grid gap-2">
                                                    <Label>Visit Type</Label>
                                                    <Select
                                                        value={visitType}
                                                        onValueChange={setVisitType}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select visit type" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {visitTypes.map((item) => (
                                                                <SelectItem
                                                                    key={item.value}
                                                                    value={item.value}
                                                                >
                                                                    {item.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={errors.visit_type} />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Billing Type</Label>
                                                    <Select
                                                        value={billingType}
                                                        onValueChange={(value) => {
                                                            setBillingType(value);
                                                            setInsuranceCompanyId('none');
                                                            setInsurancePackageId('none');
                                                        }}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select billing type" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {billingTypes.map((item) => (
                                                                <SelectItem
                                                                    key={item.value}
                                                                    value={item.value}
                                                                >
                                                                    {item.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={errors.billing_type} />
                                                </div>
                                                {billingType === 'insurance' ? (
                                                    <>
                                                        <div className="grid gap-2">
                                                            <Label>Insurance Company</Label>
                                                            <Select
                                                                value={insuranceCompanyId}
                                                                onValueChange={(value) => {
                                                                    setInsuranceCompanyId(value);
                                                                    setInsurancePackageId('none');
                                                                }}
                                                            >
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Select company" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="none">
                                                                        Select company
                                                                    </SelectItem>
                                                                    {insuranceCompanies.map((company) => (
                                                                        <SelectItem
                                                                            key={company.id}
                                                                            value={company.id}
                                                                        >
                                                                            {company.name}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                            <InputError
                                                                message={errors.insurance_company_id}
                                                            />
                                                        </div>
                                                        <div className="grid gap-2">
                                                            <Label>Insurance Package</Label>
                                                            <Select
                                                                value={insurancePackageId}
                                                                onValueChange={setInsurancePackageId}
                                                            >
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Select package" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="none">
                                                                        Select package
                                                                    </SelectItem>
                                                                    {filteredPackages.map((item) => (
                                                                        <SelectItem
                                                                            key={item.id}
                                                                            value={item.id}
                                                                        >
                                                                            {item.name}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                            <InputError
                                                                message={errors.insurance_package_id}
                                                            />
                                                        </div>
                                                    </>
                                                ) : null}
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <input
                                                    id="is_emergency"
                                                    name="is_emergency"
                                                    type="checkbox"
                                                    value="1"
                                                    className="h-4 w-4"
                                                />
                                                <Label htmlFor="is_emergency" className="font-normal">
                                                    Emergency check-in
                                                </Label>
                                            </div>

                                            <Button type="submit" disabled={processing}>
                                                {processing ? (
                                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                ) : null}
                                                Check In Patient
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <p className="mt-4 text-sm text-muted-foreground">
                                    This appointment cannot be checked in in its current
                                    status.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
