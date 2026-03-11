import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Building2, CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Departments', href: DepartmentController.index.url() },
    { title: 'Create Department', href: DepartmentController.create.url() },
];

export default function DepartmentCreate() {
    const [isActive, setIsActive] = useState(true);
    const [isClinical, setIsClinical] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Department" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create New Department
                        </h2>
                        <Button variant="outline" size="sm" asChild className="h-8">
                            <Link href={DepartmentController.index.url()}>Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Add a new department to the hospital system.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...DepartmentController.store.form()}
                    onSuccess={() =>
                        toast.success('Department created successfully.')
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
                                            placeholder="e.g. RAD-01"
                                            className="uppercase"
                                            autoFocus
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
                                        placeholder="e.g. Second Floor, West Wing"
                                    />
                                    <InputError message={errors.location} />
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
                                    className="min-w-[160px]"
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Create Department
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
