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
import { type DoctorScheduleExceptionFormPageProps } from '@/types/appointment';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: 'Schedule Exceptions', href: '/appointments/exceptions' },
    { title: 'Create Exception', href: '/appointments/exceptions/create' },
];

export default function DoctorScheduleExceptionCreate({
    doctors,
    clinics,
    typeOptions,
}: DoctorScheduleExceptionFormPageProps) {
    const [doctorId, setDoctorId] = useState(doctors[0]?.id ?? '');
    const [clinicId, setClinicId] = useState('all');
    const [type, setType] = useState(typeOptions[0]?.value ?? '');
    const [isAllDay, setIsAllDay] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Schedule Exception" />
            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Schedule Exception
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Block out doctor availability for leave, meetings,
                            holidays, or one-off closures.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/appointments/exceptions">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action="/appointments/exceptions"
                        method="post"
                        onSuccess={() =>
                            toast.success('Schedule exception created successfully.')
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
                                            disabled={isAllDay}
                                        />
                                        <InputError message={errors.end_time} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="reason">Reason</Label>
                                        <Textarea id="reason" name="reason" rows={4} />
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
                                            <PlusCircle className="mr-2 h-4 w-4" />
                                        )}
                                        Create Exception
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
