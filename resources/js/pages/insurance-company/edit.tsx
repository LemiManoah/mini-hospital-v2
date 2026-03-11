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
import { type InsuranceCompanyEditPageProps } from '@/types/insurance-company';
import { Form, Head, Link } from '@inertiajs/react';
import { Building2, LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function InsuranceCompanyEdit({
    insuranceCompany,
    addresses,
}: InsuranceCompanyEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Insurance Companies', href: '/insurance-companies' },
        {
            title: `Edit ${insuranceCompany.name}`,
            href: `/insurance-companies/${insuranceCompany.id}/edit`,
        },
    ];

    const [status, setStatus] = useState(insuranceCompany.status);
    const [addressId, setAddressId] = useState(insuranceCompany.address_id ?? '');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Insurance Company: ${insuranceCompany.name}`} />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <Building2 className="h-6 w-6 text-indigo-500" />
                        Edit Insurance Company
                    </h2>
                    <p className="text-muted-foreground">Modify the insurance company details.</p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    action={`/insurance-companies/${insuranceCompany.id}`}
                    method="put"
                    onSuccess={() => toast.success('Insurance company updated successfully.')}
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <input type="hidden" name="status" value={status} />
                            <input type="hidden" name="address_id" value={addressId} />

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name" className="text-sm font-semibold">
                                        Company Name
                                    </Label>
                                    <Input id="name" name="name" defaultValue={insuranceCompany.name} required />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email" className="text-sm font-semibold">
                                            Email
                                        </Label>
                                        <Input id="email" name="email" type="email" defaultValue={insuranceCompany.email ?? ''} />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="main_contact" className="text-sm font-semibold">
                                            Main Contact
                                        </Label>
                                        <Input
                                            id="main_contact"
                                            name="main_contact"
                                            defaultValue={insuranceCompany.main_contact ?? ''}
                                        />
                                        <InputError message={errors.main_contact} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="other_contact" className="text-sm font-semibold">
                                            Other Contact
                                        </Label>
                                        <Input
                                            id="other_contact"
                                            name="other_contact"
                                            defaultValue={insuranceCompany.other_contact ?? ''}
                                        />
                                        <InputError message={errors.other_contact} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="status" className="text-sm font-semibold">
                                            Status
                                        </Label>
                                        <Select value={status} onValueChange={(v) => setStatus(v as any)}>
                                            <SelectTrigger id="status">
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">Active</SelectItem>
                                                <SelectItem value="inactive">Inactive</SelectItem>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="suspended">Suspended</SelectItem>
                                                <SelectItem value="cancelled">Cancelled</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="address_id" className="text-sm font-semibold">
                                        Address
                                    </Label>
                                    <Select value={addressId} onValueChange={setAddressId}>
                                        <SelectTrigger id="address_id">
                                            <SelectValue placeholder="Select an address" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {addresses.map((address) => (
                                                <SelectItem key={address.id} value={address.id}>
                                                    {address.city}{address.district ? `, ${address.district}` : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.address_id} />
                                </div>
                            </div>

                            <div className="flex items-center justify-start gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                <Button type="submit" disabled={processing} className="min-w-[140px]">
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <Save className="mr-2 h-4 w-4" />
                                    )}
                                    Save Changes
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href="/insurance-companies">Cancel</Link>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
