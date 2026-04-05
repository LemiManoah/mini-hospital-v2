import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryLocationFormPageProps } from '@/types/inventory-location';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Locations', href: '/inventory-locations' },
    { title: 'Create Location', href: '/inventory-locations/create' },
];

export default function InventoryLocationCreate({
    locationTypes,
}: InventoryLocationFormPageProps) {
    const [type, setType] = useState(locationTypes[0]?.value ?? '');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Inventory Location" />

            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Inventory Location
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Add a branch-scoped store, pharmacy, or stock point.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/inventory-locations">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action="/inventory-locations"
                        method="post"
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input type="hidden" name="type" value={type} />

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="location_code">
                                            Location Code
                                        </Label>
                                        <Input
                                            id="location_code"
                                            name="location_code"
                                            required
                                        />
                                        <InputError
                                            message={errors.location_code}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Location Type</Label>
                                        <SearchableSelect
                                            options={locationTypes}
                                            value={type}
                                            onValueChange={setType}
                                            placeholder="Select location type"
                                            emptyMessage="No location types found."
                                        />
                                        <InputError message={errors.type} />
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
                                    <div className="flex items-center gap-2">
                                        <input
                                            id="is_dispensing_point"
                                            name="is_dispensing_point"
                                            type="checkbox"
                                            value="1"
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_dispensing_point"
                                            className="font-normal"
                                        >
                                            Dispensing point
                                        </Label>
                                    </div>
                                    <div className="flex items-center gap-2">
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
                                            Active location
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
                                        Create Location
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/inventory-locations">
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
