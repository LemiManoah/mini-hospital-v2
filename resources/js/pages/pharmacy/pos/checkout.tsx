import InputError from '@/components/input-error';
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
import { Head, useForm } from '@inertiajs/react';

interface CartItem {
    id: string;
    item_name: string | null;
    generic_name: string | null;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    line_total: number;
}

interface CheckoutCart {
    id: string;
    cart_number: string;
    customer_name: string | null;
    customer_phone: string | null;
    inventory_location: { id: string; name: string } | null;
    items: CartItem[];
    gross_amount: number;
    discount_amount: number;
    total_amount: number;
}

interface PharmacyPosCheckoutProps {
    navigation: InventoryNavigationContext;
    cart: CheckoutCart;
}

const PAYMENT_METHODS = [
    { value: 'cash', label: 'Cash' },
    { value: 'mobile_money', label: 'Mobile Money' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'insurance', label: 'Insurance' },
    { value: 'other', label: 'Other' },
];

const breadcrumbs = (
    navigation: InventoryNavigationContext,
    cart: CheckoutCart,
): BreadcrumbItem[] => [
    { title: navigation.section_title, href: navigation.section_href },
    { title: 'Pharmacy POS', href: '/pharmacy/pos' },
    { title: cart.cart_number, href: `/pharmacy/pos` },
    { title: 'Checkout', href: `/pharmacy/pos/carts/${cart.id}/checkout` },
];

export default function PharmacyPosCheckout({
    navigation,
    cart,
}: PharmacyPosCheckoutProps) {
    const form = useForm({
        paid_amount: cart.total_amount.toFixed(2),
        payment_method: 'cash',
        reference_number: '',
        notes: '',
    });

    const paidAmount = parseFloat(form.data.paid_amount) || 0;
    const change = Math.max(0, paidAmount - cart.total_amount);
    const balance = Math.max(0, cart.total_amount - paidAmount);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/pharmacy/pos/carts/${cart.id}/finalize`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(navigation, cart)}>
            <Head title="Checkout" />

            <div className="flex h-full flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Checkout
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Cart: {cart.cart_number}
                        {cart.customer_name && ` · ${cart.customer_name}`}
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Order Summary
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            <div className="divide-y text-sm">
                                {cart.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex justify-between py-2"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {item.item_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.quantity} ×{' '}
                                                {item.unit_price.toFixed(2)}
                                                {item.discount_amount > 0 &&
                                                    ` − ${item.discount_amount.toFixed(2)}`}
                                            </p>
                                        </div>
                                        <span className="font-medium">
                                            {item.line_total.toFixed(2)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            <Separator />
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Gross
                                </span>
                                <span>{cart.gross_amount.toFixed(2)}</span>
                            </div>
                            {cart.discount_amount > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Discount
                                    </span>
                                    <span className="text-rose-600">
                                        − {cart.discount_amount.toFixed(2)}
                                    </span>
                                </div>
                            )}
                            <Separator />
                            <div className="flex justify-between text-lg font-semibold">
                                <span>Total Due</span>
                                <span>{cart.total_amount.toFixed(2)}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Payment
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="payment_method">
                                        Payment Method
                                    </Label>
                                    <Select
                                        value={form.data.payment_method}
                                        onValueChange={(v) =>
                                            form.setData('payment_method', v)
                                        }
                                    >
                                        <SelectTrigger id="payment_method">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {PAYMENT_METHODS.map((m) => (
                                                <SelectItem
                                                    key={m.value}
                                                    value={m.value}
                                                >
                                                    {m.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.payment_method}
                                    />
                                </div>

                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="paid_amount">
                                        Amount Paid
                                    </Label>
                                    <Input
                                        id="paid_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.data.paid_amount}
                                        onChange={(e) =>
                                            form.setData(
                                                'paid_amount',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.paid_amount}
                                    />
                                </div>

                                {change > 0 && (
                                    <div className="rounded-md bg-emerald-50 p-3 text-sm dark:bg-emerald-950">
                                        <div className="flex justify-between font-semibold text-emerald-700 dark:text-emerald-300">
                                            <span>Change</span>
                                            <span>{change.toFixed(2)}</span>
                                        </div>
                                    </div>
                                )}

                                {balance > 0 && (
                                    <div className="rounded-md bg-amber-50 p-3 text-sm dark:bg-amber-950">
                                        <div className="flex justify-between font-semibold text-amber-700 dark:text-amber-300">
                                            <span>Balance Remaining</span>
                                            <span>{balance.toFixed(2)}</span>
                                        </div>
                                    </div>
                                )}

                                {form.data.payment_method !== 'cash' && (
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="reference_number">
                                            Reference Number
                                        </Label>
                                        <Input
                                            id="reference_number"
                                            value={form.data.reference_number}
                                            onChange={(e) =>
                                                form.setData(
                                                    'reference_number',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Transaction reference..."
                                        />
                                    </div>
                                )}
                            </CardContent>
                            <CardFooter>
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={
                                        form.processing || paidAmount <= 0
                                    }
                                    size="lg"
                                >
                                    {form.processing
                                        ? 'Processing...'
                                        : 'Complete Sale'}
                                </Button>
                            </CardFooter>
                        </Card>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
