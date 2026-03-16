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
import { type AppointmentCategoryEditPageProps } from '@/types/appointment';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs = (id: string): BreadcrumbItem[] => [
    { title: 'Appointment Categories', href: '/appointment-categories' },
    {
        title: 'Edit Appointment Category',
        href: `/appointment-categories/${id}/edit`,
    },
];

export default function AppointmentCategoryEdit({
    appointmentCategory,
    clinics,
}: AppointmentCategoryEditPageProps) {
    const [clinicId, setClinicId] = useState(
        appointmentCategory.clinic_id ?? 'none',
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs(appointmentCategory.id)}>
            <Head title="Edit Appointment Category" />
            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Appointment Category
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update how appointments are grouped for operations.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/appointment-categories">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/appointment-categories/${appointmentCategory.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success(
                                'Appointment category updated successfully.',
                            )
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="clinic_id"
                                    value={clinicId === 'none' ? '' : clinicId}
                                />
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={
                                                appointmentCategory.name
                                            }
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
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
                                                    All clinics
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
                                        <InputError message={errors.clinic_id} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={4}
                                            defaultValue={
                                                appointmentCategory.description ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                appointmentCategory.is_active
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
                                        <Link href="/appointment-categories">
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
