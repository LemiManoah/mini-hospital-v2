import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
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
    ArrowRight,
    BadgePercent,
    CheckCircle2,
    ClipboardCheck,
    History,
    MapPin,
    Minus,
    Package2,
    PauseCircle,
    PencilLine,
    Phone,
    PlayCircle,
    Plus,
    Receipt,
    ReceiptText,
    Search,
    ShoppingCart,
    Trash2,
    UserRound,
    WalletCards,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

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

const CATALOG_PAGE_SIZE = 12;

const breadcrumbs = (
    navigation: InventoryNavigationContext,
): BreadcrumbItem[] => [
    { title: navigation.section_title, href: navigation.section_href },
    { title: 'Pharmacy POS', href: '/pharmacy/pos' },
];

const saleSteps = [
    {
        title: 'Start Sale',
        description: 'Open a cart and set the dispensing point.',
        icon: ShoppingCart,
    },
    {
        title: 'Build Cart',
        description: 'Search medicines, add items, and tune quantities.',
        icon: ClipboardCheck,
    },
    {
        title: 'Review Totals',
        description: 'Confirm discounts and check the cart summary.',
        icon: BadgePercent,
    },
    {
        title: 'Take Payment',
        description: 'Move to checkout and capture payment details.',
        icon: WalletCards,
    },
    {
        title: 'Receipt',
        description: 'Finalize the sale and print the invoice.',
        icon: Receipt,
    },
];

function formatMoney(amount: number) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function formatQuantity(quantity: number) {
    return Number.isInteger(quantity)
        ? quantity.toString()
        : quantity.toFixed(3);
}

function stockTone(quantity: number) {
    if (quantity <= 20) {
        return {
            border: 'border-rose-200 dark:border-rose-900/70',
            badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
            label: 'Low stock',
        };
    }

    if (quantity <= 75) {
        return {
            border: 'border-amber-200 dark:border-amber-900/70',
            badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
            label: 'Monitor',
        };
    }

    return {
        border: 'border-emerald-200 dark:border-emerald-900/70',
        badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        label: 'Available',
    };
}

function CartItemRow({ cartId, item }: { cartId: string; item: CartItem }) {
    const [editing, setEditing] = useState(false);

    const form = useForm({
        quantity: item.quantity.toFixed(3),
        unit_price: item.unit_price.toFixed(2),
        discount_amount: item.discount_amount.toFixed(2),
        notes: item.notes ?? '',
    });

    const quantityStep = Number.isInteger(item.quantity) ? 1 : 0.001;
    const minQuantity = quantityStep === 1 ? 1 : 0.001;

    const quickUpdateQuantity = (nextQuantity: number) => {
        router.put(
            `/pharmacy/pos/carts/${cartId}/items/${item.id}`,
            {
                quantity: nextQuantity.toFixed(3),
                unit_price: item.unit_price.toFixed(2),
                discount_amount: item.discount_amount.toFixed(2),
                notes: item.notes ?? '',
            },
            { preserveScroll: true },
        );
    };

    const handleSave = () => {
        form.put(`/pharmacy/pos/carts/${cartId}/items/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    if (editing) {
        return (
            <div className="rounded-2xl border border-dashed border-sky-200 bg-sky-50/50 p-4 dark:border-sky-900/70 dark:bg-sky-950/20">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {item.item_name}
                        </p>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Fine tune quantity, pricing, and discount.
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => setEditing(false)}
                    >
                        Cancel
                    </Button>
                </div>

                <div className="mt-4 grid gap-3 sm:grid-cols-3">
                    <div className="space-y-1.5">
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
                            className="h-9"
                        />
                        <InputError message={form.errors.quantity} />
                    </div>
                    <div className="space-y-1.5">
                        <Label className="text-xs">Unit Price</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={form.data.unit_price}
                            onChange={(e) =>
                                form.setData('unit_price', e.target.value)
                            }
                            className="h-9"
                        />
                        <InputError message={form.errors.unit_price} />
                    </div>
                    <div className="space-y-1.5">
                        <Label className="text-xs">Discount</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={form.data.discount_amount}
                            onChange={(e) =>
                                form.setData('discount_amount', e.target.value)
                            }
                            className="h-9"
                        />
                        <InputError message={form.errors.discount_amount} />
                    </div>
                </div>

                <div className="mt-4 space-y-1.5">
                    <Label className="text-xs">Notes</Label>
                    <Input
                        value={form.data.notes}
                        onChange={(e) => form.setData('notes', e.target.value)}
                        placeholder="Optional line note..."
                    />
                </div>

                <div className="mt-4 flex gap-2">
                    <Button
                        size="sm"
                        onClick={handleSave}
                        disabled={form.processing}
                    >
                        Save changes
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setEditing(false)}
                    >
                        Keep current
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {item.item_name}
                    </p>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {[item.generic_name, item.strength, item.dosage_form]
                            .filter(Boolean)
                            .join(' / ')}
                    </p>
                </div>
                <Button
                    size="icon"
                    variant="ghost"
                    className="h-8 w-8 shrink-0 text-rose-500 hover:text-rose-600"
                    onClick={() =>
                        router.delete(
                            `/pharmacy/pos/carts/${cartId}/items/${item.id}`,
                            { preserveScroll: true },
                        )
                    }
                >
                    <Trash2 className="h-4 w-4" />
                </Button>
            </div>

            <div className="mt-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-2 rounded-full border border-slate-200 px-2 py-1 dark:border-slate-800">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="h-7 w-7 rounded-full"
                        onClick={() =>
                            quickUpdateQuantity(
                                Math.max(
                                    minQuantity,
                                    Number(
                                        (item.quantity - quantityStep).toFixed(
                                            3,
                                        ),
                                    ),
                                ),
                            )
                        }
                        disabled={item.quantity <= minQuantity}
                    >
                        <Minus className="h-3.5 w-3.5" />
                    </Button>
                    <span className="min-w-12 text-center text-sm font-semibold">
                        {formatQuantity(item.quantity)}
                    </span>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="h-7 w-7 rounded-full"
                        onClick={() =>
                            quickUpdateQuantity(
                                Math.min(
                                    item.available_quantity,
                                    Number(
                                        (item.quantity + quantityStep).toFixed(
                                            3,
                                        ),
                                    ),
                                ),
                            )
                        }
                        disabled={item.quantity >= item.available_quantity}
                    >
                        <Plus className="h-3.5 w-3.5" />
                    </Button>
                </div>

                <div className="text-right">
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        {formatMoney(item.unit_price)} each
                    </p>
                    <p className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {formatMoney(item.line_total)}
                    </p>
                </div>
            </div>

            <div className="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>
                    Available: {formatQuantity(item.available_quantity)}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-auto px-0 text-xs font-medium text-sky-700 hover:text-sky-800 dark:text-sky-300 dark:hover:text-sky-200"
                    onClick={() => setEditing(true)}
                >
                    <PencilLine className="mr-1 h-3.5 w-3.5" />
                    Edit line
                </Button>
            </div>
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
    const [selectedCategory, setSelectedCategory] = useState('All Items');
    const [catalogPage, setCatalogPage] = useState(1);

    const openCartForm = useForm({
        inventory_location_id: defaults.inventory_location_id ?? '',
        customer_name: '',
        customer_phone: '',
        notes: '',
    });

    const categoryOptions = useMemo(() => {
        const forms = Array.from(
            new Set(
                searchableItems
                    .map((item) => item.dosage_form?.trim())
                    .filter((value): value is string => Boolean(value)),
            ),
        ).slice(0, 6);

        return ['All Items', ...forms];
    }, [searchableItems]);

    const filteredItems = useMemo(() => {
        return searchableItems.filter((item) => {
            const search = itemSearch.trim().toLowerCase();
            const matchesSearch =
                search === '' ||
                item.name.toLowerCase().includes(search) ||
                (item.generic_name ?? '').toLowerCase().includes(search) ||
                (item.brand_name ?? '').toLowerCase().includes(search);

            const matchesCategory =
                selectedCategory === 'All Items' ||
                (item.dosage_form ?? '').toLowerCase() ===
                    selectedCategory.toLowerCase();

            return matchesSearch && matchesCategory;
        });
    }, [itemSearch, searchableItems, selectedCategory]);

    const totalPages = Math.max(
        1,
        Math.ceil(filteredItems.length / CATALOG_PAGE_SIZE),
    );
    const currentPage = Math.min(catalogPage, totalPages);
    const pagedItems = filteredItems.slice(
        (currentPage - 1) * CATALOG_PAGE_SIZE,
        currentPage * CATALOG_PAGE_SIZE,
    );

    useEffect(() => {
        setCatalogPage(1);
    }, [itemSearch, selectedCategory]);

    const handleOpenCart = (event: React.FormEvent) => {
        event.preventDefault();
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
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(navigation)}>
            <Head title="Pharmacy POS" />

            <div className="flex h-full flex-col gap-6 bg-slate-50/40 p-4 md:p-6 dark:bg-transparent">
                <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                    <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                    <ReceiptText className="h-5 w-5" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">
                                        Sales Screen
                                    </h1>
                                    <p className="text-sm text-slate-500 dark:text-slate-400">
                                        Walk-in pharmacy sales with a cleaner
                                        counter workflow.
                                    </p>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="secondary"
                                    className="rounded-full bg-slate-100 px-3 py-1 text-slate-700 dark:bg-slate-900 dark:text-slate-300"
                                >
                                    Pharmacy POS
                                </Badge>
                                {activeCart ? (
                                    <>
                                        <Badge className="rounded-full bg-emerald-600 px-3 py-1 text-white">
                                            {activeCart.cart_number}
                                        </Badge>
                                        {activeCart.inventory_location && (
                                            <Badge
                                                variant="outline"
                                                className="rounded-full"
                                            >
                                                {
                                                    activeCart
                                                        .inventory_location.name
                                                }
                                            </Badge>
                                        )}
                                    </>
                                ) : (
                                    <Badge
                                        variant="outline"
                                        className="rounded-full"
                                    >
                                        Ready to start
                                    </Badge>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {activeCart && (
                                <Button
                                    variant="outline"
                                    className="rounded-xl"
                                    onClick={() =>
                                        router.post(
                                            `/pharmacy/pos/carts/${activeCart.id}/hold`,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <PauseCircle className="mr-2 h-4 w-4" />
                                    Hold Sale
                                </Button>
                            )}
                            <Button
                                variant="outline"
                                className="rounded-xl"
                                asChild
                            >
                                <Link href="/pharmacy/pos/history">
                                    <History className="mr-2 h-4 w-4" />
                                    Sales History
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {!activeCart ? (
                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                        <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                            <CardHeader className="pb-4">
                                <CardTitle className="text-xl">
                                    Start a new sale
                                </CardTitle>
                                <CardDescription>
                                    Open a cart, choose the dispensing point,
                                    and optionally tag the walk-in customer.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={handleOpenCart}
                                    className="grid gap-5 lg:grid-cols-2"
                                >
                                    <div className="space-y-2">
                                        <Label htmlFor="inventory_location_id">
                                            Dispensing location
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
                                                {dispensingLocations.map(
                                                    (location) => (
                                                        <SelectItem
                                                            key={location.id}
                                                            value={location.id}
                                                        >
                                                            {location.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                openCartForm.errors
                                                    .inventory_location_id
                                            }
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="customer_name">
                                            Customer name
                                        </Label>
                                        <Input
                                            id="customer_name"
                                            value={
                                                openCartForm.data.customer_name
                                            }
                                            onChange={(event) =>
                                                openCartForm.setData(
                                                    'customer_name',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Walk-in customer"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="customer_phone">
                                            Phone number
                                        </Label>
                                        <Input
                                            id="customer_phone"
                                            value={
                                                openCartForm.data.customer_phone
                                            }
                                            onChange={(event) =>
                                                openCartForm.setData(
                                                    'customer_phone',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="+256..."
                                        />
                                    </div>

                                    <div className="space-y-2 lg:col-span-2">
                                        <Label htmlFor="cart_notes">
                                            Counter note
                                        </Label>
                                        <Input
                                            id="cart_notes"
                                            value={openCartForm.data.notes}
                                            onChange={(event) =>
                                                openCartForm.setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Optional note for the sale..."
                                        />
                                    </div>

                                    <div className="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900/60">
                                        <p className="text-sm text-slate-600 dark:text-slate-400">
                                            Once the cart opens, products appear
                                            in a visual catalog and the cart
                                            summary moves to the right.
                                        </p>
                                        <Button
                                            type="submit"
                                            className="rounded-xl"
                                            disabled={openCartForm.processing}
                                        >
                                            {openCartForm.processing
                                                ? 'Opening...'
                                                : 'Start Sale'}
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <div className="space-y-6">
                            <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <CardHeader>
                                    <CardTitle className="text-lg">
                                        Held sales
                                    </CardTitle>
                                    <CardDescription>
                                        Resume a parked counter sale when the
                                        customer returns.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {heldCarts.length === 0 ? (
                                        <div className="rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                            No held carts at the moment.
                                        </div>
                                    ) : (
                                        heldCarts.map((heldCart) => (
                                            <div
                                                key={heldCart.id}
                                                className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800"
                                            >
                                                <div className="min-w-0">
                                                    <p className="font-semibold text-slate-900 dark:text-slate-100">
                                                        {heldCart.cart_number}
                                                    </p>
                                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                        {heldCart.customer_name ??
                                                            'Walk-in customer'}
                                                    </p>
                                                </div>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="rounded-xl"
                                                    onClick={() =>
                                                        router.delete(
                                                            `/pharmacy/pos/carts/${heldCart.id}/hold`,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <PlayCircle className="mr-1.5 h-3.5 w-3.5" />
                                                    Resume
                                                </Button>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <CardHeader>
                                    <CardTitle className="text-lg">
                                        Counter workflow
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-3">
                                    {saleSteps.map((step, index) => (
                                        <div
                                            key={step.title}
                                            className="rounded-2xl border border-slate-200 p-4 dark:border-slate-800"
                                        >
                                            <div className="flex items-start gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                    <step.icon className="h-4 w-4" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                        {index + 1}.{' '}
                                                        {step.title}
                                                    </p>
                                                    <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                        {step.description}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_390px]">
                            <div className="space-y-6">
                                <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                    <CardHeader className="pb-4">
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div>
                                                <CardTitle className="text-xl">
                                                    Product catalog
                                                </CardTitle>
                                                <CardDescription>
                                                    Search by medicine name,
                                                    generic name, or brand, then
                                                    add directly to the active
                                                    cart.
                                                </CardDescription>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge
                                                    variant="secondary"
                                                    className="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-900"
                                                >
                                                    {filteredItems.length} items
                                                </Badge>
                                                <Badge
                                                    variant="outline"
                                                    className="rounded-full"
                                                >
                                                    {activeCart.items.length} in
                                                    cart
                                                </Badge>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-5">
                                        <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                                            <div className="relative">
                                                <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                                <Input
                                                    value={itemSearch}
                                                    onChange={(event) =>
                                                        setItemSearch(
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Search medicine by name, generic name, or brand..."
                                                    className="h-11 rounded-2xl pl-10"
                                                />
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {categoryOptions.map(
                                                    (option) => (
                                                        <button
                                                            key={option}
                                                            type="button"
                                                            onClick={() =>
                                                                setSelectedCategory(
                                                                    option,
                                                                )
                                                            }
                                                            className={[
                                                                'rounded-xl border px-4 py-2 text-sm font-medium transition',
                                                                option ===
                                                                selectedCategory
                                                                    ? 'border-sky-600 bg-sky-600 text-white'
                                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-sky-200 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-slate-100',
                                                            ].join(' ')}
                                                        >
                                                            {option}
                                                        </button>
                                                    ),
                                                )}
                                            </div>
                                        </div>

                                        {pagedItems.length === 0 ? (
                                            <div className="rounded-3xl border border-dashed border-slate-200 py-16 text-center dark:border-slate-800">
                                                <Package2 className="mx-auto h-8 w-8 text-slate-400" />
                                                <p className="mt-4 text-sm font-medium text-slate-700 dark:text-slate-300">
                                                    No medicines match this
                                                    search.
                                                </p>
                                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                    Try another keyword or
                                                    switch the catalog filter.
                                                </p>
                                            </div>
                                        ) : (
                                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                                {pagedItems.map((item) => {
                                                    const tone = stockTone(
                                                        item.available_quantity,
                                                    );

                                                    return (
                                                        <button
                                                            key={item.id}
                                                            type="button"
                                                            onClick={() =>
                                                                handleAddItem(
                                                                    item,
                                                                )
                                                            }
                                                            className={`group rounded-3xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md dark:bg-slate-950/40 ${tone.border}`}
                                                        >
                                                            <div className="flex items-start justify-between gap-3">
                                                                <div className="min-w-0">
                                                                    <p className="line-clamp-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </p>
                                                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                        {[
                                                                            item.generic_name,
                                                                            item.dosage_form,
                                                                        ]
                                                                            .filter(
                                                                                Boolean,
                                                                            )
                                                                            .join(
                                                                                ' / ',
                                                                            ) ||
                                                                            'General item'}
                                                                    </p>
                                                                </div>
                                                                <Badge
                                                                    className={`rounded-full ${tone.badge}`}
                                                                >
                                                                    {tone.label}
                                                                </Badge>
                                                            </div>

                                                            <div className="mt-6 flex items-end justify-between gap-4">
                                                                <div>
                                                                    <p className="text-lg font-semibold text-slate-950 dark:text-slate-100">
                                                                        {formatMoney(
                                                                            item.unit_price,
                                                                        )}
                                                                    </p>
                                                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                                                        Stock{' '}
                                                                        {formatQuantity(
                                                                            item.available_quantity,
                                                                        )}
                                                                    </p>
                                                                </div>
                                                                <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 transition group-hover:bg-sky-600 group-hover:text-white dark:bg-slate-900 dark:text-slate-200">
                                                                    <Plus className="h-4 w-4" />
                                                                </div>
                                                            </div>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {totalPages > 1 && (
                                            <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                                    Showing{' '}
                                                    {(currentPage - 1) *
                                                        CATALOG_PAGE_SIZE +
                                                        1}{' '}
                                                    to{' '}
                                                    {Math.min(
                                                        currentPage *
                                                            CATALOG_PAGE_SIZE,
                                                        filteredItems.length,
                                                    )}{' '}
                                                    of {filteredItems.length}{' '}
                                                    items
                                                </p>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="rounded-xl"
                                                        disabled={
                                                            currentPage === 1
                                                        }
                                                        onClick={() =>
                                                            setCatalogPage(
                                                                currentPage - 1,
                                                            )
                                                        }
                                                    >
                                                        Previous
                                                    </Button>
                                                    <Badge
                                                        variant="secondary"
                                                        className="rounded-lg px-3 py-1"
                                                    >
                                                        Page {currentPage} /{' '}
                                                        {totalPages}
                                                    </Badge>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="rounded-xl"
                                                        disabled={
                                                            currentPage ===
                                                            totalPages
                                                        }
                                                        onClick={() =>
                                                            setCatalogPage(
                                                                currentPage + 1,
                                                            )
                                                        }
                                                    >
                                                        Next
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            <div className="space-y-6 xl:sticky xl:top-6 xl:self-start">
                                <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                    <CardHeader className="pb-4">
                                        <CardTitle className="text-lg">
                                            Customer
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-900">
                                                    <UserRound className="h-4 w-4 text-slate-600 dark:text-slate-300" />
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="font-semibold text-slate-900 dark:text-slate-100">
                                                        {activeCart.customer_name ??
                                                            'Walk-in Customer'}
                                                    </p>
                                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                                        Counter sale
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                            <div className="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                                <div className="flex items-center gap-2 text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">
                                                    <MapPin className="h-3.5 w-3.5" />
                                                    Location
                                                </div>
                                                <p className="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                                                    {activeCart
                                                        .inventory_location
                                                        ?.name ?? 'Not set'}
                                                </p>
                                            </div>
                                            <div className="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                                <div className="flex items-center gap-2 text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">
                                                    <Phone className="h-3.5 w-3.5" />
                                                    Phone
                                                </div>
                                                <p className="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                                                    {activeCart.customer_phone ??
                                                        'Not captured'}
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                    <CardHeader className="pb-4">
                                        <div className="flex items-center justify-between gap-4">
                                            <div>
                                                <CardTitle className="text-lg">
                                                    Cart
                                                </CardTitle>
                                                <CardDescription>
                                                    {activeCart.items.length}{' '}
                                                    item(s) currently in this
                                                    sale.
                                                </CardDescription>
                                            </div>
                                            <ShoppingCart className="h-5 w-5 text-slate-400" />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {activeCart.items.length === 0 ? (
                                            <div className="rounded-2xl border border-dashed border-slate-200 py-10 text-center dark:border-slate-800">
                                                <ShoppingCart className="mx-auto h-8 w-8 text-slate-400" />
                                                <p className="mt-4 text-sm font-medium text-slate-700 dark:text-slate-300">
                                                    Cart is empty
                                                </p>
                                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                    Add products from the
                                                    catalog to begin the sale.
                                                </p>
                                            </div>
                                        ) : (
                                            <div className="space-y-3">
                                                {activeCart.items.map(
                                                    (item) => (
                                                        <CartItemRow
                                                            key={item.id}
                                                            cartId={
                                                                activeCart.id
                                                            }
                                                            item={item}
                                                        />
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                    <CardHeader className="pb-4">
                                        <CardTitle className="text-lg">
                                            Order summary
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-3 text-sm">
                                            <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                                <span>Subtotal</span>
                                                <span className="font-medium text-slate-900 dark:text-slate-100">
                                                    {formatMoney(
                                                        activeCart.gross_amount,
                                                    )}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                                <span>Discount</span>
                                                <span className="font-medium text-emerald-700 dark:text-emerald-300">
                                                    -
                                                    {formatMoney(
                                                        activeCart.discount_amount,
                                                    )}
                                                </span>
                                            </div>
                                        </div>

                                        <Separator />

                                        <div className="flex items-end justify-between">
                                            <div>
                                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                                    Total payable
                                                </p>
                                                <p className="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">
                                                    {formatMoney(
                                                        activeCart.total_amount,
                                                    )}
                                                </p>
                                            </div>
                                            <Badge className="rounded-full bg-emerald-600 px-3 py-1 text-white">
                                                Ready
                                            </Badge>
                                        </div>

                                        <div className="grid gap-2">
                                            <Button
                                                className="h-11 rounded-2xl"
                                                disabled={
                                                    activeCart.items.length ===
                                                    0
                                                }
                                                asChild
                                            >
                                                <Link
                                                    href={`/pharmacy/pos/carts/${activeCart.id}/checkout`}
                                                >
                                                    Proceed to Checkout
                                                    <ArrowRight className="ml-2 h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Payment method and tendered
                                                amount are captured on the next
                                                screen.
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-5">
                            {saleSteps.map((step, index) => {
                                const isComplete =
                                    (index === 0 && activeCart !== null) ||
                                    (index === 1 &&
                                        activeCart.items.length > 0) ||
                                    (index === 2 &&
                                        activeCart.total_amount > 0);
                                const isCurrent = index === 3;

                                return (
                                    <Card
                                        key={step.title}
                                        className="rounded-3xl border-slate-200 shadow-sm dark:border-slate-800 dark:bg-slate-950/40"
                                    >
                                        <CardContent className="p-5">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-900">
                                                    <step.icon className="h-5 w-5 text-slate-700 dark:text-slate-300" />
                                                </div>
                                                {isComplete ? (
                                                    <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                                                ) : isCurrent ? (
                                                    <Badge className="rounded-full bg-sky-600 text-white">
                                                        Next
                                                    </Badge>
                                                ) : null}
                                            </div>
                                            <p className="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                {index + 1}. {step.title}
                                            </p>
                                            <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                {step.description}
                                            </p>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
