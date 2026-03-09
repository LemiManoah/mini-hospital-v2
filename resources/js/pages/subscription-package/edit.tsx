import SubscriptionPackageController from '@/actions/App/Http/Controllers/SubscriptionPackageController';
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
import { type SubscriptionPackage } from '@/types/subscription-package';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Package, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface SubscriptionPackageEditProps {
    package: SubscriptionPackage;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Subscription Packages', href: SubscriptionPackageController.index.url() },
    { title: 'Edit Package', href: '#' },
];

export default function SubscriptionPackageEdit({ package: subscriptionPackage }: SubscriptionPackageEditProps) {
    const [status, setStatus] = useState(subscriptionPackage.status);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Package: ${subscriptionPackage.name}`} />

            <div className="mt-4 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-4">
                <div className="flex flex-col gap-1 w-full">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <Package className="h-6 w-6 text-indigo-500" />
                        Edit Package: {subscriptionPackage.name}
                    </h2>
                    <p className="text-muted-foreground">
                        Update subscription details and pricing.
                    </p>
                </div>
            </div>

            <div className="m-2 rounded border bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <Form
                    {...SubscriptionPackageController.update.form({ subscription_package: subscriptionPackage.id })}
                    onSuccess={() => toast.success('Subscription package updated successfully.')}
                    className="p-6 space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name" className="text-sm font-semibold">
                                        Package Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={subscriptionPackage.name}
                                        placeholder="e.g. Premium Health"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="users" className="text-sm font-semibold">
                                            User Limit
                                        </Label>
                                        <Input
                                            id="users"
                                            name="users"
                                            type="number"
                                            min={1}
                                            defaultValue={subscriptionPackage.users}
                                            placeholder="e.g. 10"
                                            required
                                        />
                                        <InputError message={errors.users} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="price" className="text-sm font-semibold">
                                            Price
                                        </Label>
                                        <Input
                                            id="price"
                                            name="price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            defaultValue={subscriptionPackage.price}
                                            placeholder="0.00"
                                            required
                                        />
                                        <InputError message={errors.price} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status" className="text-sm font-semibold">
                                        Status
                                    </Label>
                                    <Select value={status} onValueChange={(value) => setStatus(value as SubscriptionPackage['status'])}>
                                        <SelectTrigger id="status">
                                            <SelectValue placeholder="Select package status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="suspended">Suspended</SelectItem>
                                            <SelectItem value="cancelled">Cancelled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input type="hidden" name="status" value={status} />
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <div className="flex items-center justify-start gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                                <Button type="submit" disabled={processing} className="min-w-[140px]">
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin mr-2" />
                                    ) : (
                                        <Save className="h-4 w-4 mr-2" />
                                    )}
                                    Save Changes
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={SubscriptionPackageController.index.url()}>Cancel</Link>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
