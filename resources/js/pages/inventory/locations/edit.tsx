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
import { type InventoryLocationEditPageProps } from '@/types/inventory-location';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function InventoryLocationEdit({
    inventoryLocation,
    locationTypes,
}: InventoryLocationEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Locations', href: '/inventory-locations' },
        {
            title: 'Edit Location',
            href: `/inventory-locations/${inventoryLocation.id}/edit`,
        },
    ];
    const [type, setType] = useState(inventoryLocation.type);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Inventory Location: ${inventoryLocation.name}`} />

            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Inventory Location
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update location settings for {inventoryLocation.name}
                            .
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/inventory-locations">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/inventory-locations/${inventoryLocation.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success(
                                'Inventory location updated successfully.',
                            )
                        }
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
                                            defaultValue={inventoryLocation.name}
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
                                            defaultValue={
                                                inventoryLocation.location_code
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.location_code}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Location Type</Label>
                                        <Select value={type} onValueChange={setType}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select location type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {locationTypes.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
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
                                            defaultValue={
                                                inventoryLocation.description ??
                                                ''
                                            }
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
                                            defaultChecked={
                                                inventoryLocation.is_dispensing_point
                                            }
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
                                            defaultChecked={
                                                inventoryLocation.is_active
                                            }
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
                                            <Save className="mr-2 h-4 w-4" />
                                        )}
                                        Save Changes
                                    </Button>
                                    <Button variant="ghost" type="button" asChild>
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
