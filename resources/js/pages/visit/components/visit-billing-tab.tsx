import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    type VisitBilling,
    type VisitCharge,
    type VisitPayment,
} from '@/types/patient';
import { Receipt } from 'lucide-react';
import {
    billingStatusClasses,
    formatDateTime,
    formatMoney,
} from './visit-show-utils';

type PaymentFormState = {
    amount: string;
    payment_method: string;
    payment_date: string;
    reference_number: string;
    notes: string;
};

type PaymentFormErrors = Partial<Record<keyof PaymentFormState, string>>;

type VisitBillingTabProps = {
    visitId: string;
    billing?: VisitBilling | null;
    charges: VisitCharge[];
    payments: VisitPayment[];
    canCreatePayment: boolean;
    paymentMethods: { value: string; label: string }[];
    paymentForm: PaymentFormState;
    paymentErrors: PaymentFormErrors;
    paymentProcessing: boolean;
    onPaymentChange: (field: keyof PaymentFormState, value: string) => void;
    onPaymentSubmit: () => void;
};

export function VisitBillingTab({
    visitId,
    billing,
    charges,
    payments,
    canCreatePayment,
    paymentMethods,
    paymentForm,
    paymentErrors,
    paymentProcessing,
    onPaymentChange,
    onPaymentSubmit,
}: VisitBillingTabProps) {
    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Billing Summary</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                <Receipt className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Billing Status
                                </p>
                                <p className="font-medium">
                                    {billing
                                        ? billing.status.replaceAll('_', ' ')
                                        : 'Not started'}
                                </p>
                            </div>
                        </div>
                        {billing ? (
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${billingStatusClasses(billing.status)}`}
                            >
                                {billing.status.replaceAll('_', ' ')}
                            </span>
                        ) : null}
                    </div>
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="rounded-lg border p-3">
                            <p className="text-sm text-muted-foreground">
                                Gross
                            </p>
                            <p className="text-lg font-semibold">
                                {formatMoney(billing?.gross_amount ?? 0)}
                            </p>
                        </div>
                        <div className="rounded-lg border p-3">
                            <p className="text-sm text-muted-foreground">
                                Paid
                            </p>
                            <p className="text-lg font-semibold">
                                {formatMoney(billing?.paid_amount ?? 0)}
                            </p>
                        </div>
                        <div className="rounded-lg border p-3">
                            <p className="text-sm text-muted-foreground">
                                Balance
                            </p>
                            <p className="text-lg font-semibold">
                                {formatMoney(billing?.balance_amount ?? 0)}
                            </p>
                        </div>
                    </div>
                    <div className="space-y-3 text-sm">
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-muted-foreground">
                                Charges
                            </span>
                            <span className="font-medium">
                                {charges.length}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-muted-foreground">
                                Payments
                            </span>
                            <span className="font-medium">
                                {payments.length}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-muted-foreground">
                                Last Payment
                            </span>
                            <span className="font-medium">
                                {payments[0]
                                    ? formatDateTime(payments[0].payment_date)
                                    : 'No payments yet'}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <Card>
                    <CardHeader>
                        <CardTitle>Charge Lines</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {charges.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No charge lines have been added to this visit
                                yet.
                            </p>
                        ) : (
                            charges.map((charge) => (
                                <div
                                    key={charge.id}
                                    className="grid gap-3 rounded-lg border p-4 sm:grid-cols-[2fr_1fr_1fr]"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {charge.description}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {charge.charge_code ??
                                                'No charge code'}{' '}
                                            {' | '}{' '}
                                            {charge.status.replaceAll('_', ' ')}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Quantity x Unit Price
                                        </p>
                                        <p className="font-medium">
                                            {formatMoney(charge.quantity)} x{' '}
                                            {formatMoney(charge.unit_price)}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Line Total
                                        </p>
                                        <p className="font-medium">
                                            {formatMoney(charge.line_total)}
                                        </p>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Record Payment</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {!canCreatePayment ? (
                                <p className="text-sm text-muted-foreground">
                                    You can review billing here, but you do not
                                    have permission to record payments.
                                </p>
                            ) : (billing?.balance_amount ?? 0) <= 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    This visit does not currently have an
                                    outstanding balance to settle.
                                </p>
                            ) : (
                                <form
                                    className="space-y-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        onPaymentSubmit();
                                    }}
                                >
                                    <div className="space-y-2">
                                        <Label htmlFor="payment_amount">
                                            Amount
                                        </Label>
                                        <Input
                                            id="payment_amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max={String(
                                                billing?.balance_amount ?? '',
                                            )}
                                            value={paymentForm.amount}
                                            onChange={(event) =>
                                                onPaymentChange(
                                                    'amount',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={paymentErrors.amount}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor={`payment_method_${visitId}`}
                                        >
                                            Payment Method
                                        </Label>
                                        <Select
                                            value={paymentForm.payment_method}
                                            onValueChange={(value) =>
                                                onPaymentChange(
                                                    'payment_method',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id={`payment_method_${visitId}`}
                                            >
                                                <SelectValue placeholder="Select payment method" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {paymentMethods.map(
                                                    (method) => (
                                                        <SelectItem
                                                            key={method.value}
                                                            value={method.value}
                                                        >
                                                            {method.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                paymentErrors.payment_method
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="payment_date">
                                            Payment Date
                                        </Label>
                                        <Input
                                            id="payment_date"
                                            type="datetime-local"
                                            value={paymentForm.payment_date}
                                            onChange={(event) =>
                                                onPaymentChange(
                                                    'payment_date',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={paymentErrors.payment_date}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="reference_number">
                                            Reference Number
                                        </Label>
                                        <Input
                                            id="reference_number"
                                            value={paymentForm.reference_number}
                                            onChange={(event) =>
                                                onPaymentChange(
                                                    'reference_number',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                paymentErrors.reference_number
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="payment_notes">
                                            Notes
                                        </Label>
                                        <Textarea
                                            id="payment_notes"
                                            value={paymentForm.notes}
                                            onChange={(event) =>
                                                onPaymentChange(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={paymentErrors.notes}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={paymentProcessing}
                                    >
                                        Record Payment
                                    </Button>
                                </form>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Payment History</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {payments.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No payments have been recorded for this
                                    visit yet.
                                </p>
                            ) : (
                                payments.map((payment) => (
                                    <div
                                        key={payment.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    {formatMoney(
                                                        payment.amount,
                                                    )}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {payment.payment_method?.replaceAll(
                                                        '_',
                                                        ' ',
                                                    ) ?? 'Method not set'}
                                                </p>
                                            </div>
                                            <div className="text-right text-sm text-muted-foreground">
                                                <p>
                                                    {formatDateTime(
                                                        payment.payment_date,
                                                    )}
                                                </p>
                                                <p>
                                                    {payment.receipt_number ??
                                                        'No receipt'}
                                                </p>
                                            </div>
                                        </div>
                                        {payment.reference_number ? (
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                Ref: {payment.reference_number}
                                            </p>
                                        ) : null}
                                        {payment.notes ? (
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {payment.notes}
                                            </p>
                                        ) : null}
                                        {!payment.is_refund ? (
                                            <div className="mt-3 flex justify-end">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/visits/${visitId}/payments/${payment.id}/print`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        Print Receipt
                                                    </a>
                                                </Button>
                                            </div>
                                        ) : null}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
