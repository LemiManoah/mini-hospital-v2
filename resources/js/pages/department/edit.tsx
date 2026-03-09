import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
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
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type DepartmentEditPageProps } from '@/types/department';
import { Form, Head, Link } from '@inertiajs/react';
import { Building2, LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function DepartmentEdit({
    department,
    staff,
}: DepartmentEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Departments', href: DepartmentController.index.url() },
        {
            title: `Edit ${department.department_name}`,
            href: DepartmentController.edit.url({ department: department.id }),
        },
    ];

    const [isActive, setIsActive] = useState(department.is_active);
    const [isClinical, setIsClinical] = useState(department.is_clinical);
    const [headOfDepartmentId, setHeadOfDepartmentId] = useState<string>(
        department.head_of_department_id || '',
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Department: ${department.department_name}`} />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <Building2 className="h-6 w-6 text-indigo-500" />
                        Edit Department
                    </h2>
                    <p className="text-muted-foreground">
                        Modify the department details.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...DepartmentController.update.form({
                        department: department.id,
                    })}
                    onSuccess={() =>
                        toast.success('Department updated successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? '1' : '0'}
                            />
                            <input
                                type="hidden"
                                name="is_clinical"
                                value={isClinical ? '1' : '0'}
                            />
                            <input
                                type="hidden"
                                name="head_of_department_id"
                                value={headOfDepartmentId}
                            />

                            <div className="grid gap-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="department_code"
                                            className="text-sm font-semibold"
                                        >
                                            Department Code
                                        </Label>
                                        <Input
                                            id="department_code"
                                            name="department_code"
                                            defaultValue={
                                                department.department_code
                                            }
                                            placeholder="e.g. RAD-01"
                                            className="uppercase"
                                            required
                                        />
                                        <InputError
                                            message={errors.department_code}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="department_name"
                                            className="text-sm font-semibold"
                                        >
                                            Department Name
                                        </Label>
                                        <Input
                                            id="department_name"
                                            name="department_name"
                                            defaultValue={
                                                department.department_name
                                            }
                                            placeholder="e.g. Radiology"
                                            required
                                        />
                                        <InputError
                                            message={errors.department_name}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="location"
                                        className="text-sm font-semibold"
                                    >
                                        Physical Location
                                    </Label>
                                    <Input
                                        id="location"
                                        name="location"
                                        defaultValue={department.location || ''}
                                        placeholder="e.g. Second Floor, West Wing"
                                    />
                                    <InputError message={errors.location} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="head_of_dept"
                                        className="text-sm font-semibold"
                                    >
                                        Head of Department
                                    </Label>
                                    <Select
                                        onValueChange={setHeadOfDepartmentId}
                                        value={headOfDepartmentId}
                                    >
                                        <SelectTrigger id="head_of_dept">
                                            <SelectValue placeholder="Select a staff member" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value=" ">
                                                None (Clear Selection)
                                            </SelectItem>
                                            {staff.map((s) => (
                                                <SelectItem
                                                    key={s.id}
                                                    value={s.id}
                                                >
                                                    {s.first_name} {s.last_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.head_of_department_id}
                                    />
                                </div>

                                <div className="space-y-4 pt-2">
                                    <div className="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                                        <div className="space-y-0.5">
                                            <Label
                                                htmlFor="is_clinical_toggle"
                                                className="text-sm font-semibold"
                                            >
                                                Clinical Department
                                            </Label>
                                            <p className="text-sm text-zinc-500">
                                                Does this department provide
                                                direct patient care?
                                            </p>
                                        </div>
                                        <Switch
                                            id="is_clinical_toggle"
                                            checked={isClinical}
                                            onCheckedChange={setIsClinical}
                                        />
                                        <InputError
                                            message={errors.is_clinical}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                                        <div className="space-y-0.5">
                                            <Label
                                                htmlFor="is_active_toggle"
                                                className="text-sm font-semibold"
                                            >
                                                Active Status
                                            </Label>
                                            <p className="text-sm text-zinc-500">
                                                Enable or disable this
                                                department across the system.
                                            </p>
                                        </div>
                                        <Switch
                                            id="is_active_toggle"
                                            checked={isActive}
                                            onCheckedChange={setIsActive}
                                        />
                                        <InputError
                                            message={errors.is_active}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-start gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-w-[140px]"
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <Save className="mr-2 h-4 w-4" />
                                    )}
                                    Save Changes
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link
                                        href={DepartmentController.index.url()}
                                    >
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
