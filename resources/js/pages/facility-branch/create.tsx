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
import { type FacilityBranchFormPageProps } from '@/types/facility-branch';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Branches', href: '/facility-branches' },
    { title: 'Create Branch', href: '/facility-branches/create' },
];

export default function FacilityBranchCreate({
    currencies,
}: FacilityBranchFormPageProps) {
    const [currencyId, setCurrencyId] = useState('');
    const [status, setStatus] = useState('active');
    const [hasStore, setHasStore] = useState(false);
    const [name, setName] = useState('');
    const [code, setCode] = useState('');

    const handleNameChange = (value: string) => {
        setName(value);

        if (code !== '') {
            return;
        }

        setCode(
            value
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '')
                .slice(0, 20),
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Facility Branch" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create Facility Branch
                        </h2>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/facility-branches">Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Add a new operational branch for the current tenant.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    action="/facility-branches"
                    method="post"
                    onSuccess={() =>
                        toast.success(
                            'Facility branch created successfully.',
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
                                        value={name}
                                        onChange={(event) =>
                                            handleNameChange(
                                                event.target.value,
                                            )
                                        }
                                        placeholder="e.g. Nyagatare Branch"
                                        autoFocus
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
                                        value={code}
                                        onChange={(event) =>
                                            setCode(event.target.value)
                                        }
                                        placeholder="e.g. NYG-01"
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
                                        placeholder="e.g. +2507..."
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
                                        placeholder="Backup line"
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
                                    placeholder="branch@facility.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="flex items-center gap-3 rounded border border-dashed p-4">
                                <input
                                    id="has_store"
                                    name="has_store"
                                    type="checkbox"
                                    value="1"
                                    checked={hasStore}
                                    onChange={(event) =>
                                        setHasStore(event.target.checked)
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

                            <div className="flex items-center gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Create Branch
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
