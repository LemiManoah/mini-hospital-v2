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
import { type FacilityBranchEditPageProps } from '@/types/facility-branch';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Branches', href: '/facility-branches' },
    { title: 'Edit Branch', href: '#' },
];

export default function FacilityBranchEdit({
    branch,
    currencies,
}: FacilityBranchEditPageProps) {
    const [currencyId, setCurrencyId] = useState(branch.currency_id);
    const [status, setStatus] = useState(branch.status);
    const [hasStore, setHasStore] = useState(branch.has_store);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${branch.name}`} />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Edit Facility Branch
                        </h2>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/facility-branches">Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Update branch metadata and operational settings.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    action={`/facility-branches/${branch.id}`}
                    method="put"
                    onSuccess={() =>
                        toast.success(
                            'Facility branch updated successfully.',
                        )
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-3xl space-y-6">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Branch Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={branch.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="branch_code">
                                        Branch Code
                                    </Label>
                                    <Input
                                        id="branch_code"
                                        name="branch_code"
                                        defaultValue={branch.branch_code}
                                        required
                                    />
                                    <InputError
                                        message={errors.branch_code}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="currency_id">
                                        Currency
                                    </Label>
                                    <Select
                                        value={currencyId}
                                        onValueChange={setCurrencyId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((currency) => (
                                                <SelectItem
                                                    key={currency.id}
                                                    value={currency.id}
                                                >
                                                    {currency.code} -{' '}
                                                    {currency.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="currency_id"
                                        value={currencyId}
                                    />
                                    <InputError
                                        message={errors.currency_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        value={status}
                                        onValueChange={setStatus}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select status" />
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
                                            <SelectItem value="pending">
                                                Pending
                                            </SelectItem>
                                            <SelectItem value="cancelled">
                                                Cancelled
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
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="main_contact">
                                        Main Contact
                                    </Label>
                                    <Input
                                        id="main_contact"
                                        name="main_contact"
                                        defaultValue={branch.main_contact ?? ''}
                                    />
                                    <InputError
                                        message={errors.main_contact}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="other_contact">
                                        Other Contact
                                    </Label>
                                    <Input
                                        id="other_contact"
                                        name="other_contact"
                                        defaultValue={
                                            branch.other_contact ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.other_contact}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={branch.email ?? ''}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-3 rounded border border-dashed p-4">
                                <div className="text-sm text-muted-foreground">
                                    {branch.is_main_branch
                                        ? 'This is the main branch. Main-branch status is intentionally protected in this first administration slice.'
                                        : 'Store availability can be changed here for non-main branches too.'}
                                </div>
                                <div className="flex items-center gap-3">
                                    <input
                                        id="has_store"
                                        name="has_store"
                                        type="checkbox"
                                        value="1"
                                        checked={hasStore}
                                        onChange={(event) =>
                                            setHasStore(
                                                event.target.checked,
                                            )
                                        }
                                        className="h-4 w-4"
                                    />
                                    <Label
                                        htmlFor="has_store"
                                        className="font-normal"
                                    >
                                        This branch has a store or stock point
                                    </Label>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Update Branch
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href="/facility-branches">
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
