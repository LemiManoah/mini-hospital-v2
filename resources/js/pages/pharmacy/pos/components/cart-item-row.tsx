import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router, useForm } from '@inertiajs/react';
import { Minus, PencilLine, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { formatMoney, formatQuantity, type CartItem } from './types';

export function CartItemRow({
    cartId,
    item,
}: {
    cartId: string;
    item: CartItem;
}) {
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
            <div className="rounded-xl border border-dashed border-sky-200 bg-sky-50/50 p-4 dark:border-sky-900/70 dark:bg-sky-950/20">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {item.item_name}
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
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
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
