import ClinicController from '@/actions/App/Http/Controllers/ClinicController';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type ClinicCreatePageProps } from '@/types/clinic';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clinics', href: ClinicController.index.url() },
    { title: 'Create Clinic', href: ClinicController.create.url() },
];

export default function ClinicCreate({
    branches,
    departments,
}: ClinicCreatePageProps) {
    const [branchId, setBranchId] = useState('');
    const [departmentId, setDepartmentId] = useState('');
    const [status, setStatus] = useState('active');
    const [name, setName] = useState('');
    const [code, setCode] = useState('');

    const handleNameChange = (newName: string) => {
        setName(newName);
        // Autogenerate code: Uppercase, alphanumeric, max 10 chars
        const generatedCode = newName
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '-')
            .substring(0, 10);
        setCode(generatedCode);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Clinic" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create New Clinic
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="h-8"
                        >
                            <Link href={ClinicController.index.url()}>
                                Back
                            </Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Manage and define clinic settings and assignments.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...ClinicController.store.form()}
                    onSuccess={() =>
                        toast.success('Clinic created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="branch_id"
                                            className="text-sm font-semibold"
                                        >
                                            Branch
                                        </Label>
                                        <Select
                                            value={branchId}
                                            onValueChange={(value) =>
                                                setBranchId(value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Branch" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {branches.map((branch) => (
                                                    <SelectItem
                                                        key={branch.id}
                                                        value={branch.id}
                                                    >
                                                        {branch.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="branch_id"
                                            value={branchId}
                                        />
                                        <InputError
                                            message={errors.branch_id}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="department_id"
                                            className="text-sm font-semibold"
                                        >
                                            Department
                                        </Label>
                                        <Select
                                            value={departmentId}
                                            onValueChange={(value) =>
                                                setDepartmentId(value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Department" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {departments.map((dept) => (
                                                    <SelectItem
                                                        key={dept.id}
                                                        value={dept.id}
                                                    >
                                                        {dept.department_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="department_id"
                                            value={departmentId}
                                        />
                                        <InputError
                                            message={errors.department_id}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="clinic_name"
                                            className="text-sm font-semibold"
                                        >
                                            Clinic Name
                                        </Label>
                                        <Input
                                            id="clinic_name"
                                            name="clinic_name"
                                            value={name}
                                            onChange={(e) =>
                                                handleNameChange(e.target.value)
                                            }
                                            placeholder="e.g. ENT Clinic"
                                            autoFocus
                                            required
                                        />
                                        <InputError
                                            message={errors.clinic_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="clinic_code"
                                            className="text-sm font-semibold"
                                        >
                                            Clinic Code
                                        </Label>
                                        <Input
                                            id="clinic_code"
                                            name="clinic_code"
                                            value={code}
                                            onChange={(e) =>
                                                setCode(e.target.value)
                                            }
                                            placeholder="e.g. ENT-01"
                                            required
                                        />
                                        <InputError
                                            message={errors.clinic_code}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="status"
                                            className="text-sm font-semibold"
                                        >
                                            Status
                                        </Label>
                                        <Select
                                            value={status}
                                            onValueChange={(value: any) =>
                                                setStatus(value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="inactive">
                                                    Inactive
                                                </SelectItem>
                                                <SelectItem value="suspended">
                                                    Suspended
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="status"
                                            value={status}
                                        />
                                        <InputError message={errors.status} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="phone"
                                            className="text-sm font-semibold"
                                        >
                                            Phone (Optional)
                                        </Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            placeholder="e.g. +123456789"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="location"
                                        className="text-sm font-semibold"
                                    >
                                        Location (Optional)
                                    </Label>
                                    <Input
                                        id="location"
                                        name="location"
                                        placeholder="e.g. Main Wing, Floor 2"
                                    />
                                    <InputError message={errors.location} />
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
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Create Clinic
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={ClinicController.index.url()}>
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
