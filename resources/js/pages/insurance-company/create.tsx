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
import { type InsuranceCompanyCreatePageProps } from '@/types/insurance-company';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Insurance Companies', href: '/insurance-companies' },
    { title: 'Create Insurance Company', href: '/insurance-companies/create' },
];

export default function InsuranceCompanyCreate({
    addresses,
}: InsuranceCompanyCreatePageProps) {
    const [status, setStatus] = useState('active');
    const [addressId, setAddressId] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Insurance Company" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create Insurance Company
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="h-8"
                        >
                            <Link href="/insurance-companies">Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Add a new insurance company for package and claim
                        management.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    action="/insurance-companies"
                    method="post"
                    onSuccess={() =>
                        toast.success('Insurance company created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <input type="hidden" name="status" value={status} />
                            <input
                                type="hidden"
                                name="address_id"
                                value={addressId}
                            />

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="name"
                                        className="text-sm font-semibold"
                                    >
                                        Company Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="e.g. Jubilee Insurance"
                                        autoFocus
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className="text-sm font-semibold"
                                        >
                                            Email
                                        </Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            placeholder="e.g. claims@company.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="main_contact"
                                            className="text-sm font-semibold"
                                        >
                                            Main Contact
                                        </Label>
                                        <Input
                                            id="main_contact"
                                            name="main_contact"
                                            placeholder="e.g. +256700000000"
                                        />
                                        <InputError
                                            message={errors.main_contact}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="other_contact"
                                            className="text-sm font-semibold"
                                        >
                                            Other Contact
                                        </Label>
                                        <Input
                                            id="other_contact"
                                            name="other_contact"
                                        />
                                        <InputError
                                            message={errors.other_contact}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="status"
                                            className="text-sm font-semibold"
                                        >
                                            Status
                                        </Label>
                                        <Select
                                            value={status}
                                            onValueChange={setStatus}
                                        >
                                            <SelectTrigger id="status">
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="inactive">
                                                    Inactive
                                                </SelectItem>
                                                <SelectItem value="pending">
                                                    Pending
                                                </SelectItem>
                                                <SelectItem value="suspended">
                                                    Suspended
                                                </SelectItem>
                                                <SelectItem value="cancelled">
                                                    Cancelled
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="address_id"
                                        className="text-sm font-semibold"
                                    >
                                        Address
                                    </Label>
                                    <Select
                                        value={addressId}
                                        onValueChange={setAddressId}
                                    >
                                        <SelectTrigger id="address_id">
                                            <SelectValue placeholder="Select an address" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {addresses.map((address) => (
                                                <SelectItem
                                                    key={address.id}
                                                    value={address.id}
                                                >
                                                    {address.city}
                                                    {address.district
                                                        ? `, ${address.district}`
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.address_id} />
                                </div>
                            </div>

                            <div className="flex items-center justify-start gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-w-[180px]"
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Create Insurance Company
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href="/insurance-companies">
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
