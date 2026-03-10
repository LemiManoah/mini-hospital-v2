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
import { type StaffEditPageProps } from '@/types/staff';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save, User } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function StaffEdit({
    staff,
    departments,
    positions,
    branches,
}: StaffEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Staff', href: StaffController.index.url() },
        {
            title: `Edit ${staff.first_name} ${staff.last_name}`,
            href: StaffController.edit.url({ staff: staff.id }),
        },
    ];

    const [selectedDepartmentIds, setSelectedDepartmentIds] = useState<
        string[]
    >((staff.departments ?? []).map((department) => department.id));
    const [positionId, setPositionId] = useState(staff.staff_position_id || '');
    const [staffType, setStaffType] = useState(staff.type || '');
    const [isActive, _setIsActive] = useState(staff.is_active);
    const [selectedBranchIds, setSelectedBranchIds] = useState<string[]>(
        (staff.branches ?? []).map((branch) => branch.id),
    );
    const [primaryBranchId, setPrimaryBranchId] = useState<string>(
        (staff.branches ?? [])[0]?.id ?? '',
    );

    const handleBranchToggle = (branchId: string, checked: boolean) => {
        if (checked) {
            const updated = [...selectedBranchIds, branchId];
            setSelectedBranchIds(updated);
            if (!primaryBranchId) {
                setPrimaryBranchId(branchId);
            }
            return;
        }

        const updated = selectedBranchIds.filter((id) => id !== branchId);
        setSelectedBranchIds(updated);
        if (primaryBranchId === branchId) {
            setPrimaryBranchId(updated[0] ?? '');
        }
    };

    const handleDepartmentToggle = (departmentId: string, checked: boolean) => {
        if (checked) {
            setSelectedDepartmentIds([...selectedDepartmentIds, departmentId]);
            return;
        }

        setSelectedDepartmentIds(
            selectedDepartmentIds.filter((id) => id !== departmentId),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Edit Staff Member: ${staff.first_name} ${staff.last_name}`}
            />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <User className="h-6 w-6 text-indigo-500" />
                        Edit Staff Member
                    </h2>
                    <p className="text-muted-foreground">
                        Update staff member details.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...StaffController.update.form({ staff: staff.id })}
                    onSuccess={() =>
                        toast.success('Staff member updated successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            {selectedDepartmentIds.map((departmentId) => (
                                <input
                                    key={departmentId}
                                    type="hidden"
                                    name="department_ids[]"
                                    value={departmentId}
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
                                name="primary_branch_id"
                                value={primaryBranchId}
                            />
                            {selectedBranchIds.map((branchId) => (
                                <input
                                    key={branchId}
                                    type="hidden"
                                    name="branch_ids[]"
                                    value={branchId}
                                />
                            ))}

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
                                            defaultValue={staff.first_name}
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
                                            defaultValue={staff.last_name}
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
                                        defaultValue={staff.email}
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
                                        defaultValue={staff.phone ?? ''}
                                    />
                                    <InputError message={errors.phone} />
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
                                </div>

                                <div className="grid gap-2">
                                    <Label className="text-sm font-semibold">
                                        Departments
                                    </Label>
                                    <div className="space-y-2 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                        {departments.map((department) => {
                                            const selected =
                                                selectedDepartmentIds.includes(
                                                    department.id,
                                                );

                                            return (
                                                <label
                                                    key={department.id}
                                                    className="inline-flex items-center gap-2"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={selected}
                                                        onChange={(event) =>
                                                            handleDepartmentToggle(
                                                                department.id,
                                                                event.target
                                                                    .checked,
                                                            )
                                                        }
                                                    />
                                                    <span>
                                                        {
                                                            department.department_name
                                                        }
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                    <InputError
                                        message={errors.department_ids}
                                    />
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
                                            defaultValue={staff.hire_date ?? ''}
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
                                            defaultValue={
                                                staff.license_number ?? ''
                                            }
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
                                            defaultValue={staff.specialty ?? ''}
                                        />
                                        <InputError
                                            message={errors.specialty}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label className="text-sm font-semibold">
                                        Branch Assignment
                                    </Label>
                                    <div className="space-y-2 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                        {branches.map((branch) => {
                                            const selected =
                                                selectedBranchIds.includes(
                                                    branch.id,
                                                );

                                            return (
                                                <div
                                                    key={branch.id}
                                                    className="flex items-center justify-between gap-4"
                                                >
                                                    <label className="inline-flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            checked={selected}
                                                            onChange={(event) =>
                                                                handleBranchToggle(
                                                                    branch.id,
                                                                    event.target
                                                                        .checked,
                                                                )
                                                            }
                                                        />
                                                        <span>
                                                            {branch.name}
                                                        </span>
                                                    </label>
                                                    <label className="inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                        <input
                                                            type="radio"
                                                            name="primary-branch-selector"
                                                            checked={
                                                                primaryBranchId ===
                                                                branch.id
                                                            }
                                                            disabled={!selected}
                                                            onChange={() =>
                                                                setPrimaryBranchId(
                                                                    branch.id,
                                                                )
                                                            }
                                                        />
                                                        Primary
                                                    </label>
                                                </div>
                                            );
                                        })}
                                    </div>
                                    <InputError message={errors.branch_ids} />
                                    <InputError
                                        message={errors.primary_branch_id}
                                    />
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
