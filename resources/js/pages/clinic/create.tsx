import ClinicController from '@/actions/App/Http/Controllers/ClinicController';
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
import { Head, Link, useForm } from '@inertiajs/react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clinics', href: ClinicController.index.url() },
    { title: 'Add Clinic', href: ClinicController.create.url() },
];

export default function ClinicCreate({
    branches,
    departments,
    addresses,
}: ClinicCreatePageProps) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: '',
        clinic_code: '',
        clinic_name: '',
        department_id: '',
        address_id: '',
        phone: '',
        status: 'active',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(ClinicController.store.url(), {
            onSuccess: () => toast.success('Clinic created successfully.'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Clinic" />

            <div className="mx-auto max-w-2xl px-4 py-6">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Add Clinic
                    </h2>
                    <Button
                        variant="ghost"
                        size="sm"
                        asChild
                        className="text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        <Link href={ClinicController.index.url()}>Back</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="branch_id">Branch</Label>
                            <Select
                                value={data.branch_id}
                                onValueChange={(value) =>
                                    setData('branch_id', value)
                                }
                            >
                                <SelectTrigger
                                    className={
                                        errors.branch_id ? 'border-red-500' : ''
                                    }
                                >
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
                            {errors.branch_id && (
                                <p className="text-xs text-red-500">
                                    {errors.branch_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="department_id">Department</Label>
                            <Select
                                value={data.department_id}
                                onValueChange={(value) =>
                                    setData('department_id', value)
                                }
                            >
                                <SelectTrigger
                                    className={
                                        errors.department_id
                                            ? 'border-red-500'
                                            : ''
                                    }
                                >
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
                            {errors.department_id && (
                                <p className="text-xs text-red-500">
                                    {errors.department_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="clinic_code">Clinic Code</Label>
                            <Input
                                id="clinic_code"
                                value={data.clinic_code}
                                onChange={(e) =>
                                    setData('clinic_code', e.target.value)
                                }
                                placeholder="e.g. ENT-01"
                                className={
                                    errors.clinic_code ? 'border-red-500' : ''
                                }
                            />
                            {errors.clinic_code && (
                                <p className="text-xs text-red-500">
                                    {errors.clinic_code}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="clinic_name">Clinic Name</Label>
                            <Input
                                id="clinic_name"
                                value={data.clinic_name}
                                onChange={(e) =>
                                    setData('clinic_name', e.target.value)
                                }
                                placeholder="e.g. ENT Clinic"
                                className={
                                    errors.clinic_name ? 'border-red-500' : ''
                                }
                            />
                            {errors.clinic_name && (
                                <p className="text-xs text-red-500">
                                    {errors.clinic_name}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(value: any) =>
                                    setData('status', value)
                                }
                            >
                                <SelectTrigger
                                    className={
                                        errors.status ? 'border-red-500' : ''
                                    }
                                >
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
                            {errors.status && (
                                <p className="text-xs text-red-500">
                                    {errors.status}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="phone">Phone (Optional)</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                                placeholder="e.g. +123456789"
                            />
                            {errors.phone && (
                                <p className="text-xs text-red-500">
                                    {errors.phone}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="address_id">
                                Address (Optional)
                            </Label>
                            <Select
                                value={data.address_id}
                                onValueChange={(value) =>
                                    setData('address_id', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Address" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    {addresses.map((address) => (
                                        <SelectItem
                                            key={address.id}
                                            value={address.id}
                                        >
                                            {address.city} - {address.district}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <Button
                            type="button"
                            variant="outline"
                            asChild
                            disabled={processing}
                        >
                            <Link href={ClinicController.index.url()}>
                                Cancel
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            {processing ? 'Saving...' : 'Create Clinic'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
