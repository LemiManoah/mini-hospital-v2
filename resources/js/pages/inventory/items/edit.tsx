import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryItemEditPageProps } from '@/types/inventory-item';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

export default function InventoryItemEdit({
    inventoryItem,
    itemTypes,
    unitOptions,
    drugCategories,
    dosageForms,
}: InventoryItemEditPageProps) {
    const [itemType, setItemType] = useState(inventoryItem.item_type);
    const [unitId, setUnitId] = useState(inventoryItem.unit_id ?? '');
    const [category, setCategory] = useState(inventoryItem.category ?? '');
    const [dosageForm, setDosageForm] = useState(
        inventoryItem.dosage_form ?? '',
    );
    const isDrug = itemType === 'drug';
    const itemLabel = inventoryItem.generic_name ?? inventoryItem.name;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Items', href: '/inventory-items' },
        {
            title: 'Edit Item',
            href: `/inventory-items/${inventoryItem.id}/edit`,
        },
    ];

    const pageTitle = useMemo(
        () =>
            isDrug
                ? `Edit Drug Item: ${itemLabel}`
                : `Edit Inventory Item: ${itemLabel}`,
        [isDrug, itemLabel],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Inventory Item
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update the shared catalog record for {itemLabel}.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/inventory-items">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/inventory-items/${inventoryItem.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success(
                                'Inventory item updated successfully.',
                            )
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="item_type"
                                    value={itemType}
                                />
                                <input
                                    type="hidden"
                                    name="unit_id"
                                    value={unitId}
                                />
                                <input
                                    type="hidden"
                                    name="category"
                                    value={category}
                                />
                                <input
                                    type="hidden"
                                    name="dosage_form"
                                    value={dosageForm}
                                />

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Item Type</Label>
                                        <SearchableSelect
                                            options={itemTypes}
                                            value={itemType}
                                            onValueChange={(value) => {
                                                setItemType(value);

                                                if (value !== 'drug') {
                                                    setCategory('');
                                                    setDosageForm('');
                                                }
                                            }}
                                            placeholder="Select item type"
                                            emptyMessage="No item types found."
                                        />
                                        <InputError
                                            message={errors.item_type}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Unit</Label>
                                        <SearchableSelect
                                            options={unitOptions}
                                            value={unitId}
                                            onValueChange={setUnitId}
                                            placeholder="Select a unit"
                                            emptyMessage="No units found."
                                            allowClear
                                        />
                                        <InputError message={errors.unit_id} />
                                    </div>

                                    {isDrug ? (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="generic_name">
                                                    Generic Name
                                                </Label>
                                                <Input
                                                    id="generic_name"
                                                    name="generic_name"
                                                    defaultValue={
                                                        inventoryItem.generic_name ??
                                                        ''
                                                    }
                                                    autoFocus
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors.generic_name
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="brand_name">
                                                    Brand Name
                                                </Label>
                                                <Input
                                                    id="brand_name"
                                                    name="brand_name"
                                                    defaultValue={
                                                        inventoryItem.brand_name ??
                                                        ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.brand_name}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>Drug Category</Label>
                                                <SearchableSelect
                                                    options={drugCategories}
                                                    value={category}
                                                    onValueChange={setCategory}
                                                    placeholder="Select a category"
                                                    emptyMessage="No categories found."
                                                    allowClear
                                                />
                                                <InputError
                                                    message={errors.category}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>Dosage Form</Label>
                                                <SearchableSelect
                                                    options={dosageForms}
                                                    value={dosageForm}
                                                    onValueChange={setDosageForm}
                                                    placeholder="Select a dosage form"
                                                    emptyMessage="No dosage forms found."
                                                    allowClear
                                                />
                                                <InputError
                                                    message={
                                                        errors.dosage_form
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="strength">
                                                    Strength
                                                </Label>
                                                <Input
                                                    id="strength"
                                                    name="strength"
                                                    defaultValue={
                                                        inventoryItem.strength ??
                                                        ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.strength}
                                                />
                                            </div>
                                        </>
                                    ) : (
                                        <div className="grid gap-2 md:col-span-2">
                                            <Label htmlFor="name">Name</Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                defaultValue={inventoryItem.name}
                                                autoFocus
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="manufacturer">
                                            Manufacturer
                                        </Label>
                                        <Input
                                            id="manufacturer"
                                            name="manufacturer"
                                            defaultValue={
                                                inventoryItem.manufacturer ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.manufacturer}
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
                                            defaultValue={
                                                inventoryItem.description ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="minimum_stock_level">
                                            Minimum Stock Level
                                        </Label>
                                        <Input
                                            id="minimum_stock_level"
                                            name="minimum_stock_level"
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            defaultValue={
                                                inventoryItem.minimum_stock_level
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors.minimum_stock_level
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="reorder_level">
                                            Reorder Level
                                        </Label>
                                        <Input
                                            id="reorder_level"
                                            name="reorder_level"
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            defaultValue={
                                                inventoryItem.reorder_level
                                            }
                                        />
                                        <InputError
                                            message={errors.reorder_level}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="default_purchase_price">
                                            Default Purchase Price
                                        </Label>
                                        <Input
                                            id="default_purchase_price"
                                            name="default_purchase_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            defaultValue={
                                                inventoryItem.default_purchase_price ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors.default_purchase_price
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="default_selling_price">
                                            Default Selling Price
                                        </Label>
                                        <Input
                                            id="default_selling_price"
                                            name="default_selling_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            defaultValue={
                                                inventoryItem.default_selling_price ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors.default_selling_price
                                            }
                                        />
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <input
                                            id="expires"
                                            name="expires"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                inventoryItem.expires
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="expires"
                                            className="font-normal"
                                        >
                                            This item expires
                                        </Label>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                inventoryItem.is_active
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active for stock workflows
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
                                        <Link href="/inventory-items">
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
