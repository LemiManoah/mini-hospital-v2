import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InventoryNavigationContext } from '@/types/inventory-navigation';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Check,
    History,
    PauseCircle,
    PlayCircle,
    Plus,
    ShoppingCart,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';

interface DispensingLocation {
    id: string;
    name: string;
    location_code: string;
    is_dispensing_point: boolean;
}

interface SearchableItem {
    id: string;
    name: string;
    generic_name: string | null;
    brand_name: string | null;
    strength: string | null;
    dosage_form: string | null;
    unit_price: number;
    available_quantity: number;
}

interface CartItem {
    id: string;
    inventory_item_id: string;
    item_name: string | null;
    generic_name: string | null;
    brand_name: string | null;
    strength: string | null;
    dosage_form: string | null;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    line_total: number;
    available_quantity: number;
    notes: string | null;
}

interface ActiveCart {
    id: string;
    cart_number: string;
    status: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    notes: string | null;
    inventory_location_id: string | null;
    inventory_location: {
        id: string;
        name: string;
        location_code: string;
    } | null;
    items: CartItem[];
    gross_amount: number;
    discount_amount: number;
    total_amount: number;
}

interface HeldCart {
    id: string;
    cart_number: string;
    held_at: string | null;
    customer_name: string | null;
}

interface PharmacyPosIndexProps {
    navigation: InventoryNavigationContext;
    dispensingLocations: DispensingLocation[];
    activeCart: ActiveCart | null;
    heldCarts: HeldCart[];
    searchableItems: SearchableItem[];
    defaults: {
        inventory_location_id: string | null;
    };
}

const breadcrumbs = (
    navigation: InventoryNavigationContext,
): BreadcrumbItem[] => [
    { title: navigation.section_title, href: navigation.section_href },
    { title: 'Pharmacy POS', href: '/pharmacy/pos' },
];

function CartItemRow({ cartId, item }: { cartId: string; item: CartItem }) {
    const [editing, setEditing] = useState(false);

    const form = useForm({
        quantity: item.quantity.toFixed(3),
        unit_price: item.unit_price.toFixed(2),
        discount_amount: item.discount_amount.toFixed(2),
        notes: item.notes ?? '',
    });

    const handleSave = () => {
        form.put(`/pharmacy/pos/carts/${cartId}/items/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const handleCancel = () => {
        form.reset();
        setEditing(false);
    };

    if (editing) {
        return (
            <div className="flex flex-col gap-3 py-3">
                <p className="text-sm font-medium">{item.item_name}</p>
                <div className="grid grid-cols-3 gap-2">
                    <div className="flex flex-col gap-1">
                        <Label className="text-xs">Qty</Label>
                        <Input
                            type="number"
                            step="0.001"
                            min="0.001"
                            max={item.available_quantity}
                            value={form.data.quantity}
                            onChange={(e) =>
                                form.setData('quantity', e.target.value)
                            }
                            className="h-8 text-sm"
                        />
                        <InputError message={form.errors.quantity} />
                    </div>
                    <div className="flex flex-col gap-1">
                        <Label className="text-xs">Price</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={form.data.unit_price}
                            onChange={(e) =>
                                form.setData('unit_price', e.target.value)
                            }
                            className="h-8 text-sm"
                        />
                        <InputError message={form.errors.unit_price} />
                    </div>
                    <div className="flex flex-col gap-1">
                        <Label className="text-xs">Discount</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={form.data.discount_amount}
                            onChange={(e) =>
                                form.setData('discount_amount', e.target.value)
                            }
                            className="h-8 text-sm"
                        />
                        <InputError message={form.errors.discount_amount} />
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button
                        size="sm"
                        onClick={handleSave}
                        disabled={form.processing}
                    >
                        <Check className="mr-1 h-3 w-3" />
                        Save
                    </Button>
                    <Button size="sm" variant="outline" onClick={handleCancel}>
                        <X className="mr-1 h-3 w-3" />
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div
            className="group flex cursor-pointer items-center justify-between gap-4 py-3"
            onClick={() => setEditing(true)}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => e.key === 'Enter' && setEditing(true)}
        >
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium group-hover:text-primary">
                    {item.item_name}
                </p>
                <p className="text-xs text-muted-foreground">
                    {[item.generic_name, item.strength]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
                <p className="text-xs text-muted-foreground">
                    {item.quantity} × {item.unit_price.toFixed(2)}
                    {item.discount_amount > 0 &&
                        ` − ${item.discount_amount.toFixed(2)}`}
                    <span className="ml-2 text-muted-foreground/60">
                        tap to edit
                    </span>
                </p>
            </div>
            <div className="text-right">
                <p className="text-sm font-semibold">
                    {item.line_total.toFixed(2)}
                </p>
                <p className="text-xs text-muted-foreground">
                    Avail: {item.available_quantity.toFixed(3)}
                </p>
            </div>
            <Button
                size="icon"
                variant="ghost"
                className="text-destructive hover:text-destructive"
                onClick={(e) => {
                    e.stopPropagation();
                    router.delete(
                        `/pharmacy/pos/carts/${cartId}/items/${item.id}`,
                        {
                            preserveScroll: true,
                        },
                    );
                }}
            >
                <Trash2 className="h-4 w-4" />
            </Button>
        </div>
    );
}

export default function PharmacyPosIndex({
    navigation,
    dispensingLocations,
    activeCart,
    heldCarts,
    searchableItems,
    defaults,
}: PharmacyPosIndexProps) {
    const [itemSearch, setItemSearch] = useState('');

    const openCartForm = useForm({
        inventory_location_id: defaults.inventory_location_id ?? '',
        customer_name: '',
        customer_phone: '',
        notes: '',
    });

    const addItemForm = useForm<{
        inventory_item_id: string;
        quantity: string;
        unit_price: string;
        discount_amount: string;
        notes: string;
    }>({
        inventory_item_id: '',
        quantity: '1',
        unit_price: '0',
        discount_amount: '0',
        notes: '',
    });

    const filteredItems = searchableItems.filter(
        (item) =>
            itemSearch === '' ||
            item.name.toLowerCase().includes(itemSearch.toLowerCase()) ||
            (item.generic_name ?? '')
                .toLowerCase()
                .includes(itemSearch.toLowerCase()) ||
            (item.brand_name ?? '')
                .toLowerCase()
                .includes(itemSearch.toLowerCase()),
    );

    const handleOpenCart = (e: React.FormEvent) => {
        e.preventDefault();
        openCartForm.post('/pharmacy/pos', { preserveScroll: true });
    };

    const handleAddItem = (item: SearchableItem) => {
        if (!activeCart) {
            return;
        }
        router.post(
            `/pharmacy/pos/carts/${activeCart.id}/items`,
            {
                inventory_item_id: item.id,
                quantity: '1',
                unit_price: item.unit_price.toFixed(2),
                discount_amount: '0',
                notes: '',
            },
            {
                preserveScroll: true,
                onSuccess: () => setItemSearch(''),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(navigation)}>
            <Head title="Pharmacy POS" />

            <div className="flex h-full flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Pharmacy POS
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Walk-in counter sales
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {activeCart && (
                            <>
                                <Badge className="border-emerald-200 bg-emerald-50 text-emerald-700">
                                    Cart: {activeCart.cart_number}
                                </Badge>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            `/pharmacy/pos/carts/${activeCart.id}/hold`,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <PauseCircle className="mr-1 h-4 w-4" />
                                    Hold Cart
                                </Button>
                            </>
                        )}
                        <Button size="sm" variant="outline" asChild>
                            <Link href="/pharmacy/pos/history">
                                <History className="mr-1 h-4 w-4" />
                                History
                            </Link>
                        </Button>
                    </div>
                </div>

                {!activeCart && heldCarts.length > 0 && (
                    <Card className="max-w-lg">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <PauseCircle className="h-4 w-4" />
                                Held Carts
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            {heldCarts.map((held) => (
                                <div
                                    key={held.id}
                                    className="flex items-center justify-between rounded-md border px-3 py-2"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {held.cart_number}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {held.customer_name ?? 'Walk-in'}
                                            {held.held_at &&
                                                ` · ${new Date(held.held_at).toLocaleTimeString()}`}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.delete(
                                                `/pharmacy/pos/carts/${held.id}/hold`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <PlayCircle className="mr-1 h-3 w-3" />
                                        Resume
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {!activeCart ? (
                    <Card className="max-w-lg">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ShoppingCart className="h-5 w-5" />
                                Open POS Cart
                            </CardTitle>
                        </CardHeader>
                        <form onSubmit={handleOpenCart}>
                            <CardContent className="flex flex-col gap-4">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="inventory_location_id">
                                        Dispensing Location
                                    </Label>
                                    <Select
                                        value={
                                            openCartForm.data
                                                .inventory_location_id ||
                                            undefined
                                        }
                                        onValueChange={(value) =>
                                            openCartForm.setData(
                                                'inventory_location_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="inventory_location_id">
                                            <SelectValue placeholder="Select location" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {dispensingLocations.map((loc) => (
                                                <SelectItem
                                                    key={loc.id}
                                                    value={loc.id}
                                                >
                                                    {loc.name}
                                                    {loc.is_dispensing_point
                                                        ? ' (Dispensing Point)'
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            openCartForm.errors
                                                .inventory_location_id
                                        }
                                    />
                                </div>

                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="customer_name">
                                        Customer Name (optional)
                                    </Label>
                                    <Input
                                        id="customer_name"
                                        value={openCartForm.data.customer_name}
                                        onChange={(e) =>
                                            openCartForm.setData(
                                                'customer_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Walk-in customer"
                                    />
                                </div>

                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="customer_phone">
                                        Customer Phone (optional)
                                    </Label>
                                    <Input
                                        id="customer_phone"
                                        value={openCartForm.data.customer_phone}
                                        onChange={(e) =>
                                            openCartForm.setData(
                                                'customer_phone',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="+256..."
                                    />
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button
                                    type="submit"
                                    disabled={openCartForm.processing}
                                    className="w-full"
                                >
                                    {openCartForm.processing
                                        ? 'Opening...'
                                        : 'Open Cart'}
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="flex flex-col gap-4 lg:col-span-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Search &amp; Add Items
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-3">
                                    <Input
                                        placeholder="Search by name, generic name, or brand..."
                                        value={itemSearch}
                                        onChange={(e) =>
                                            setItemSearch(e.target.value)
                                        }
                                    />
                                    {itemSearch !== '' && (
                                        <div className="max-h-72 overflow-y-auto rounded-md border">
                                            {filteredItems.length === 0 ? (
                                                <p className="p-4 text-sm text-muted-foreground">
                                                    No items found.
                                                </p>
                                            ) : (
                                                <div className="divide-y">
                                                    {filteredItems.map(
                                                        (item) => (
                                                            <div
                                                                key={item.id}
                                                                className="flex items-center justify-between p-3"
                                                            >
                                                                <div>
                                                                    <p className="text-sm font-medium">
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        {[
                                                                            item.generic_name,
                                                                            item.strength,
                                                                            item.dosage_form,
                                                                        ]
                                                                            .filter(
                                                                                Boolean,
                                                                            )
                                                                            .join(
                                                                                ' · ',
                                                                            )}
                                                                    </p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Available:{' '}
                                                                        {item.available_quantity.toFixed(
                                                                            3,
                                                                        )}{' '}
                                                                        · Price:{' '}
                                                                        {item.unit_price.toFixed(
                                                                            2,
                                                                        )}
                                                                    </p>
                                                                </div>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        handleAddItem(
                                                                            item,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        addItemForm.processing
                                                                    }
                                                                >
                                                                    <Plus className="h-4 w-4" />
                                                                    Add
                                                                </Button>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Cart Items
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {activeCart.items.length === 0 ? (
                                        <p className="py-4 text-center text-sm text-muted-foreground">
                                            No items in cart. Search above to
                                            add items.
                                        </p>
                                    ) : (
                                        <div className="divide-y">
                                            {activeCart.items.map((item) => (
                                                <CartItemRow
                                                    key={item.id}
                                                    cartId={activeCart.id}
                                                    item={item}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        <div className="flex flex-col gap-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Order Summary
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            Location
                                        </span>
                                        <span className="font-medium">
                                            {activeCart.inventory_location
                                                ?.name ?? '—'}
                                        </span>
                                    </div>
                                    {activeCart.customer_name && (
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">
                                                Customer
                                            </span>
                                            <span className="font-medium">
                                                {activeCart.customer_name}
                                            </span>
                                        </div>
                                    )}
                                    <Separator />
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            Gross
                                        </span>
                                        <span>
                                            {activeCart.gross_amount.toFixed(2)}
                                        </span>
                                    </div>
                                    {activeCart.discount_amount > 0 && (
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">
                                                Discount
                                            </span>
                                            <span className="text-rose-600">
                                                −{' '}
                                                {activeCart.discount_amount.toFixed(
                                                    2,
                                                )}
                                            </span>
                                        </div>
                                    )}
                                    <Separator />
                                    <div className="flex justify-between font-semibold">
                                        <span>Total</span>
                                        <span>
                                            {activeCart.total_amount.toFixed(2)}
                                        </span>
                                    </div>
                                    <Button
                                        className="mt-2 w-full"
                                        disabled={activeCart.items.length === 0}
                                        onClick={() =>
                                            (window.location.href = `/pharmacy/pos/carts/${activeCart.id}/checkout`)
                                        }
                                    >
                                        Proceed to Checkout
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
