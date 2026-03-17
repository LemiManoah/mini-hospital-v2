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
import { type AppointmentFormPageProps } from '@/types/appointment';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Create Appointment', href: '/appointments/create' },
];

export default function AppointmentCreate({
    patients,
    doctors,
    clinics,
    appointmentCategories,
    appointmentModes,
}: AppointmentFormPageProps) {
    const [patientId, setPatientId] = useState(patients[0]?.id ?? '');
    const [doctorId, setDoctorId] = useState('none');
    const [clinicId, setClinicId] = useState('none');
    const [categoryId, setCategoryId] = useState(
        appointmentCategories[0]?.id ?? 'none',
    );
    const [modeId, setModeId] = useState(appointmentModes[0]?.id ?? 'none');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Appointment" />
            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Appointment
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Book a patient into the scheduling workflow.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/appointments">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action="/appointments"
                        method="post"
                        onSuccess={() =>
                            toast.success('Appointment created successfully.')
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="patient_id"
                                    value={patientId}
                                />
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
                                    value={
                                        categoryId === 'none' ? '' : categoryId
                                    }
                                />
                                <input
                                    type="hidden"
                                    name="appointment_mode_id"
                                    value={modeId === 'none' ? '' : modeId}
                                />

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label>Patient</Label>
                                        <Select
                                            value={patientId}
                                            onValueChange={setPatientId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select patient" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {patients.map((patient) => (
                                                    <SelectItem
                                                        key={patient.id}
                                                        value={patient.id}
                                                    >
                                                        {patient.name} (
                                                        {patient.patient_number}
                                                        )
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.patient_id}
                                        />
                                    </div>
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
                                                <SelectItem value="none">
                                                    Unassigned
                                                </SelectItem>
                                                {doctors.map((doctor) => (
                                                    <SelectItem
                                                        key={doctor.id}
                                                        value={doctor.id}
                                                    >
                                                        {doctor.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.doctor_id}
                                        />
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
                                                <SelectItem value="none">
                                                    Unassigned
                                                </SelectItem>
                                                {clinics.map((clinic) => (
                                                    <SelectItem
                                                        key={clinic.id}
                                                        value={clinic.id}
                                                    >
                                                        {clinic.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.clinic_id}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Category</Label>
                                        <Select
                                            value={categoryId}
                                            onValueChange={setCategoryId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">
                                                    No category
                                                </SelectItem>
                                                {appointmentCategories.map(
                                                    (category) => (
                                                        <SelectItem
                                                            key={category.id}
                                                            value={category.id}
                                                        >
                                                            {category.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Mode</Label>
                                        <Select
                                            value={modeId}
                                            onValueChange={setModeId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select mode" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">
                                                    No mode
                                                </SelectItem>
                                                {appointmentModes.map(
                                                    (mode) => (
                                                        <SelectItem
                                                            key={mode.id}
                                                            value={mode.id}
                                                        >
                                                            {mode.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="appointment_date">
                                            Appointment Date
                                        </Label>
                                        <Input
                                            id="appointment_date"
                                            name="appointment_date"
                                            type="date"
                                            required
                                        />
                                        <InputError
                                            message={errors.appointment_date}
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
                                        />
                                        <InputError message={errors.end_time} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="reason_for_visit">
                                            Reason for Visit
                                        </Label>
                                        <Textarea
                                            id="reason_for_visit"
                                            name="reason_for_visit"
                                            rows={4}
                                        />
                                        <InputError
                                            message={errors.reason_for_visit}
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="chief_complaint">
                                            Chief Complaint
                                        </Label>
                                        <Input
                                            id="chief_complaint"
                                            name="chief_complaint"
                                        />
                                        <InputError
                                            message={errors.chief_complaint}
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            rows={3}
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2 xl:col-span-3">
                                        <input
                                            id="is_walk_in"
                                            name="is_walk_in"
                                            type="checkbox"
                                            value="1"
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_walk_in"
                                            className="font-normal"
                                        >
                                            Walk-in appointment
                                        </Label>
                                    </div>
                                </div>

                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <PlusCircle className="mr-2 h-4 w-4" />
                                        )}
                                        Create Appointment
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/appointments">Cancel</Link>
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
