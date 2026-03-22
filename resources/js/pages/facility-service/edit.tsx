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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type FacilityServiceEditPageProps } from '@/types/facility-service';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function FacilityServiceEdit({
    facilityService,
    categories,
}: FacilityServiceEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facility Services', href: '/facility-services' },
        {
            title: 'Edit Facility Service',
            href: `/facility-services/${facilityService.id}/edit`,
        },
    ];
    const [category, setCategory] = useState(facilityService.category);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Facility Service: ${facilityService.name}`} />
            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Facility Service
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update operational catalog details for{' '}
                            {facilityService.name}.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/facility-services">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/facility-services/${facilityService.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success(
                                'Facility service updated successfully.',
                            )
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="category"
                                    value={category}
                                />
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Service Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={facilityService.name}
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Service Code</Label>
                                        <div className="rounded-md border bg-zinc-50 px-3 py-2 font-mono text-sm dark:bg-zinc-950">
                                            {facilityService.service_code}
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Category</Label>
                                        <Select
                                            value={category}
                                            onValueChange={setCategory}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.category} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={3}
                                            defaultValue={
                                                facilityService.description ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="cost_price">
                                            Cost Price
                                        </Label>
                                        <Input
                                            id="cost_price"
                                            name="cost_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            defaultValue={
                                                facilityService.cost_price ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.cost_price}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="selling_price">
                                            Selling Price
                                        </Label>
                                        <Input
                                            id="selling_price"
                                            name="selling_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            defaultValue={
                                                facilityService.selling_price ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={errors.selling_price}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Billing Mapping</Label>
                                        <div className="rounded-md border bg-zinc-50 px-3 py-2 text-sm text-muted-foreground dark:bg-zinc-950">
                                            {facilityService.is_billable
                                                ? 'Linked automatically for visit billing.'
                                                : 'Will link automatically once marked billable.'}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 pt-8">
                                        <input
                                            id="is_billable"
                                            name="is_billable"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                facilityService.is_billable
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_billable"
                                            className="font-normal"
                                        >
                                            Billable service
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                facilityService.is_active
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active for ordering
                                        </Label>
                                    </div>
                                </div>
                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <Save className="mr-2 h-4 w-4" />
                                        )}
                                        Save Changes
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/facility-services">
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
