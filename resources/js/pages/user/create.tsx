import UserController from '@/actions/App/Http/Controllers/UserController';
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
import { type UserCreatePageProps } from '@/types/user';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, User } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: UserController.index.url() },
    { title: 'Create User', href: UserController.create.url() },
];

export default function UserCreate({ staff, roles }: UserCreatePageProps) {
    const [selectedStaffId, setSelectedStaffId] = useState<string>('');
    const [email, setEmail] = useState<string>('');

    const handleStaffChange = (staffId: string) => {
        setSelectedStaffId(staffId);

        // Auto-fill email when staff is selected
        if (staffId) {
            const selectedStaff = staff.find((s) => s.id === staffId);
            if (selectedStaff?.email) {
                setEmail(selectedStaff.email);
            }
        } else {
            setEmail('');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create User" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <User className="h-6 w-6 text-indigo-500" />
                        Create New User
                    </h2>
                    <p className="text-muted-foreground">
                        Create a user account for an existing staff member.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...UserController.store.form()}
                    onSuccess={() =>
                        toast.success('User created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="staff_id"
                                        className="text-sm font-semibold"
                                    >
                                        Select Staff Member
                                    </Label>
                                    <Select
                                        name="staff_id"
                                        value={selectedStaffId}
                                        onValueChange={handleStaffChange}
                                        required
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choose a staff member..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {staff.map((staffMember) => (
                                                <SelectItem
                                                    key={staffMember.id}
                                                    value={staffMember.id}
                                                >
                                                    {staffMember.first_name}{' '}
                                                    {staffMember.last_name} -{' '}
                                                    {
                                                        staffMember.employee_number
                                                    }
                                                    {(staffMember.departments ??
                                                        []).length > 0 && (
                                                        <span className="ml-2 text-muted-foreground">
                                                            (
                                                            {
                                                                staffMember.departments
                                                                    ?.map(
                                                                        department =>
                                                                            department.department_name,
                                                                    )
                                                                    .join(', ')
                                                            }
                                                            )
                                                        </span>
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.staff_id} />
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
                                        value={email}
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
                                        placeholder="e.g. john@example.com"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="text-sm font-semibold">
                                        Assign Roles
                                    </Label>
                                    <div className="flex flex-wrap gap-3">
                                        {roles.map((role) => (
                                            <label
                                                key={role.id}
                                                className="inline-flex items-center space-x-2"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="roles[]"
                                                    value={role.id}
                                                    className="checkbox"
                                                />
                                                <span>{role.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.roles} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="password"
                                        className="text-sm font-semibold"
                                    >
                                        Password
                                    </Label>
                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        placeholder="Enter a secure password"
                                        required
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="password_confirmation"
                                        className="text-sm font-semibold"
                                    >
                                        Confirm Password
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        placeholder="Confirm the password"
                                        required
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
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
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Create User
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={UserController.index.url()}>
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
