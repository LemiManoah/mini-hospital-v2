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
import { type InsurancePackageEditPageProps } from '@/types/insurance-package';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Package, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function InsurancePackageEdit({
    insurancePackage,
    companies,
}: InsurancePackageEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Insurance Packages', href: '/insurance-packages' },
        {
            title: `Edit ${insurancePackage.name}`,
            href: `/insurance-packages/${insurancePackage.id}/edit`,
        },
    ];

    const [companyId, setCompanyId] = useState(
        insurancePackage.insurance_company_id,
    );
    const [status, setStatus] = useState(insurancePackage.status);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Insurance Package: ${insurancePackage.name}`} />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <Package className="h-6 w-6 text-indigo-500" />
                        Edit Insurance Package
                    </h2>
                    <p className="text-muted-foreground">
                        Modify insurance package details.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    action={`/insurance-packages/${insurancePackage.id}`}
                    method="put"
                    onSuccess={() =>
                        toast.success('Insurance package updated successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <input
                                type="hidden"
                                name="insurance_company_id"
                                value={companyId}
                            />
                            <input type="hidden" name="status" value={status} />

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="name"
                                        className="text-sm font-semibold"
                                    >
                                        Package Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={insurancePackage.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="insurance_company_id"
                                            className="text-sm font-semibold"
                                        >
                                            Insurance Company
                                        </Label>
                                        <Select
                                            value={companyId}
                                            onValueChange={setCompanyId}
                                        >
                                            <SelectTrigger id="insurance_company_id">
                                                <SelectValue placeholder="Select insurance company" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {companies.map((company) => (
                                                    <SelectItem
                                                        key={company.id}
                                                        value={company.id}
                                                    >
                                                        {company.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                errors.insurance_company_id
                                            }
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
                                    <Link href="/insurance-packages">
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
