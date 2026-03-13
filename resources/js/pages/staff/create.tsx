import StaffController from '@/actions/App/Http/Controllers/StaffController';
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
import { type StaffCreatePageProps } from '@/types/staff';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, User, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff', href: StaffController.index.url() },
    { title: 'Add Staff Member', href: StaffController.create.url() },
];

export default function StaffCreate({
    departments,
    positions,
    branches,
}: StaffCreatePageProps) {
    const [departmentIds, setDepartmentIds] = useState<string[]>([]);
    const [positionId, setPositionId] = useState<string>('');
    const [branchId, setBranchId] = useState<string>('');
    const [staffType, setStaffType] = useState<string>('');
    const [isActive, setIsActive] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Staff Member" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <User className="h-6 w-6 text-indigo-500" />
                        Add New Staff Member
                    </h2>
                    <p className="text-muted-foreground">
                        Register a new staff member in the system.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...StaffController.store.form()}
                    onSuccess={() =>
                        toast.success('Staff member created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            {departmentIds.map((id) => (
                                <input
                                    key={id}
                                    type="hidden"
                                    name="department_ids[]"
                                    value={id}
                                />
                            ))}
                            <input
                                type="hidden"
                                name="staff_position_id"
                                value={positionId}
                            />
                            <input
                                type="hidden"
                                name="type"
                                value={staffType}
                            />
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? '1' : '0'}
                            />
                            <input
                                type="hidden"
                                name="branch_ids[]"
                                value={branchId}
                            />
                            <input
                                type="hidden"
                                name="primary_branch_id"
                                value={branchId}
                            />

                            <div className="grid gap-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="first_name"
                                            className="text-sm font-semibold"
                                        >
                                            First Name
                                        </Label>
                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            placeholder="e.g. John"
                                            autoFocus
                                            required
                                        />
                                        <InputError
                                            message={errors.first_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="last_name"
                                            className="text-sm font-semibold"
                                        >
                                            Last Name
                                        </Label>
                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            placeholder="e.g. Doe"
                                            required
                                        />
                                        <InputError
                                            message={errors.last_name}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="email"
                                        className="text-sm font-semibold"
                                    >
                                        Email Address
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        placeholder="e.g. john.doe@example.com"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="phone"
                                        className="text-sm font-semibold"
                                    >
                                        Phone Number
                                    </Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        placeholder="e.g. +1 (555) 123-4567"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="department"
                                        className="text-sm font-semibold"
                                    >
                                        Departments
                                    </Label>
                                    <div className="space-y-2 max-h-32 overflow-y-auto border rounded-md p-3">
                                        {departments.map((dept) => (
                                            <div key={dept.id} className="flex items-center space-x-2">
                                                <input
                                                    type="checkbox"
                                                    id={`dept-${dept.id}`}
                                                    checked={departmentIds.includes(dept.id)}
                                                    onChange={(e) => {
                                                        if (e.target.checked) {
                                                            setDepartmentIds([...departmentIds, dept.id]);
                                                        } else {
                                                            setDepartmentIds(departmentIds.filter(id => id !== dept.id));
                                                        }
                                                    }}
                                                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <Label
                                                    htmlFor={`dept-${dept.id}`}
                                                    className="text-sm font-normal cursor-pointer"
                                                >
                                                    {dept.department_name}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                    <InputError
                                        message={errors.department_ids}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="position"
                                            className="text-sm font-semibold"
                                        >
                                            Position
                                        </Label>
                                        <Select
                                            value={positionId}
                                            onValueChange={setPositionId}
                                        >
                                            <SelectTrigger id="position">
                                                <SelectValue placeholder="Select position" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {positions.map((pos) => (
                                                    <SelectItem
                                                        key={pos.id}
                                                        value={pos.id}
                                                    >
                                                        {pos.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.staff_position_id}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="branch"
                                            className="text-sm font-semibold"
                                        >
                                            Branch/Facility
                                        </Label>
                                        <Select
                                            value={branchId}
                                            onValueChange={setBranchId}
                                        >
                                            <SelectTrigger id="branch">
                                                <SelectValue placeholder="Select branch" />
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
                                        <InputError
                                            message={errors.primary_branch_id}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="type"
                                            className="text-sm font-semibold"
                                        >
                                            Staff Type
                                        </Label>
                                        <Select
                                            value={staffType}
                                            onValueChange={setStaffType}
                                        >
                                            <SelectTrigger id="type">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="medical">
                                                    Medical
                                                </SelectItem>
                                                <SelectItem value="nursing">
                                                    Nursing
                                                </SelectItem>
                                                <SelectItem value="administrative">
                                                    Administrative
                                                </SelectItem>
                                                <SelectItem value="support">
                                                    Support
                                                </SelectItem>
                                                <SelectItem value="technical">
                                                    Technical
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.type} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="hire_date"
                                            className="text-sm font-semibold"
                                        >
                                            Hire Date
                                        </Label>
                                        <Input
                                            id="hire_date"
                                            name="hire_date"
                                            type="date"
                                            required
                                        />
                                        <InputError
                                            message={errors.hire_date}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="license_number"
                                            className="text-sm font-semibold"
                                        >
                                            License Number (Optional)
                                        </Label>
                                        <Input
                                            id="license_number"
                                            name="license_number"
                                            placeholder="e.g. MD-12345"
                                        />
                                        <InputError
                                            message={errors.license_number}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="specialty"
                                            className="text-sm font-semibold"
                                        >
                                            Specialty (Optional)
                                        </Label>
                                        <Input
                                            id="specialty"
                                            name="specialty"
                                            placeholder="e.g. Cardiology"
                                        />
                                        <InputError
                                            message={errors.specialty}
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
                                    Add Staff Member
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={StaffController.index.url()}>
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
