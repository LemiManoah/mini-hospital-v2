import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

interface CheckoutCustomerDialogProps {
    cartId: string;
    customerName: string | null;
    customerPhone: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function CheckoutCustomerDialog({
    cartId,
    customerName,
    customerPhone,
    open,
    onOpenChange,
}: CheckoutCustomerDialogProps) {
    const form = useForm({
        customer_name: customerName ?? '',
        customer_phone: customerPhone ?? '',
    });

    useEffect(() => {
        if (!open) {
            form.clearErrors();
        }

        form.setData({
            customer_name: customerName ?? '',
            customer_phone: customerPhone ?? '',
        });
    }, [customerName, customerPhone, open]);

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();

        form.put(`/pharmacy/pos/carts/${cartId}`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Customer details</DialogTitle>
                    <DialogDescription>
                        Save the client name and phone number before completing
                        a partially paid sale.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="checkout_customer_name">
                            Customer name
                        </Label>
                        <Input
                            id="checkout_customer_name"
                            value={form.data.customer_name}
                            onChange={(event) =>
                                form.setData(
                                    'customer_name',
                                    event.target.value,
                                )
                            }
                            placeholder="Full name"
                        />
                        <InputError message={form.errors.customer_name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="checkout_customer_phone">
                            Phone number
                        </Label>
                        <Input
                            id="checkout_customer_phone"
                            value={form.data.customer_phone}
                            onChange={(event) =>
                                form.setData(
                                    'customer_phone',
                                    event.target.value,
                                )
                            }
                            placeholder="+256..."
                        />
                        <InputError message={form.errors.customer_phone} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save details'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
