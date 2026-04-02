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
import { type InventoryItemFormPageProps } from '@/types/inventory-item';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

const NONE = '__none__';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory/dashboard' },
    { title: 'Items', href: '/inventory-items' },
    { title: 'Create Item', href: '/inventory-items/create' },
];

export default function InventoryItemCreate({
    itemTypes,
    unitOptions,
    drugCategories,
    dosageForms,
}: InventoryItemFormPageProps) {
    const [itemType, setItemType] = useState(itemTypes[0]?.value ?? '');
    const [unitId, setUnitId] = useState('');
    const [category, setCategory] = useState('');
    const [dosageForm, setDosageForm] = useState('');
    const isDrug = itemType === 'drug';
    const pageTitle = useMemo(
        () => (isDrug ? 'Create Drug Item' : 'Create Inventory Item'),
        [isDrug],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="m-4 max-w-5xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{pageTitle}</h1>
                        <p className="text-sm text-muted-foreground">
                            Build one shared catalog for drugs, supplies,
                            consumables, reagents, and other stock items.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/inventory-items">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action="/inventory-items"
                        method="post"
                        onSuccess={() =>
                            toast.success(
                                'Inventory item created successfully.',
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
                                        <Select
                                            value={itemType}
                                            onValueChange={(value) => {
                                                setItemType(value);

                                                if (value !== 'drug') {
                                                    setCategory('');
                                                    setDosageForm('');
                                                }
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select item type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {itemTypes.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.item_type}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Unit</Label>
                                        <Select
                                            value={unitId || NONE}
                                            onValueChange={(value) =>
                                                setUnitId(
                                                    value === NONE ? '' : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a unit" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={NONE}>
                                                    No unit
                                                </SelectItem>
                                                {unitOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
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
                                                />
                                                <InputError
                                                    message={errors.brand_name}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>Drug Category</Label>
                                                <Select
                                                    value={category || NONE}
                                                    onValueChange={(value) =>
                                                        setCategory(
                                                            value === NONE
                                                                ? ''
                                                                : value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select a category" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value={NONE}>
                                                            Select a category
                                                        </SelectItem>
                                                        {drugCategories.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={errors.category}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>Dosage Form</Label>
                                                <Select
                                                    value={dosageForm || NONE}
                                                    onValueChange={(value) =>
                                                        setDosageForm(
                                                            value === NONE
                                                                ? ''
                                                                : value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select a dosage form" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value={NONE}>
                                                            Select a dosage form
                                                        </SelectItem>
                                                        {dosageForms.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
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
                                                    placeholder="e.g. 500mg"
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
                                            defaultValue="0"
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
                                            defaultValue="0"
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
                                            defaultChecked
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
                                            <PlusCircle className="mr-2 h-4 w-4" />
                                        )}
                                        Create Inventory Item
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
