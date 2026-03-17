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
import { type FacilityServiceFormPageProps } from '@/types/facility-service';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Services', href: '/facility-services' },
    { title: 'Create Facility Service', href: '/facility-services/create' },
];

export default function FacilityServiceCreate({
    categories,
}: FacilityServiceFormPageProps) {
    const [category, setCategory] = useState(categories[0]?.value ?? '');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Facility Service" />
            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Facility Service
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Add a service to the shared operational catalog.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/facility-services">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action="/facility-services"
                        method="post"
                        onSuccess={() =>
                            toast.success(
                                'Facility service created successfully.',
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
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="service_code">
                                            Service Code
                                        </Label>
                                        <Input
                                            id="service_code"
                                            name="service_code"
                                            required
                                        />
                                        <InputError
                                            message={errors.service_code}
                                        />
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
                                    <div className="grid gap-2">
                                        <Label htmlFor="department_name">
                                            Department
                                        </Label>
                                        <Input
                                            id="department_name"
                                            name="department_name"
                                            placeholder="e.g. Treatment Room"
                                        />
                                        <InputError
                                            message={errors.department_name}
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={3}
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="default_instructions">
                                            Default Instructions
                                        </Label>
                                        <Textarea
                                            id="default_instructions"
                                            name="default_instructions"
                                            rows={3}
                                        />
                                        <InputError
                                            message={
                                                errors.default_instructions
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="charge_master_id">
                                            Charge Master ID
                                        </Label>
                                        <Input
                                            id="charge_master_id"
                                            name="charge_master_id"
                                            placeholder="Optional billing mapping"
                                        />
                                        <InputError
                                            message={errors.charge_master_id}
                                        />
                                    </div>
                                    <div className="flex items-center gap-2 pt-8">
                                        <input
                                            id="is_billable"
                                            name="is_billable"
                                            type="checkbox"
                                            value="1"
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
                                            defaultChecked
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
                                            <PlusCircle className="mr-2 h-4 w-4" />
                                        )}
                                        Create Facility Service
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
