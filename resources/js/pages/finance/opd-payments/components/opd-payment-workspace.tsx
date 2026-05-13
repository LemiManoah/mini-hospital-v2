import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/lib/permissions';
import {
    formatDateTime,
    formatMoney,
} from '@/pages/visit/components/visit-show-utils';
import {
    type BillingDiscount,
    type FinanceOpdVisitBilling,
} from '@/types/finance';
import { type VisitCharge, type VisitPayment } from '@/types/patient';
import { router, useForm } from '@inertiajs/react';
import { Printer, Tag } from 'lucide-react';
import { useState } from 'react';

type OpdPaymentWorkspaceProps = {
    visitId: string;
    payerType: 'cash' | 'insurance';
    billing?: FinanceOpdVisitBilling | null;
    charges: VisitCharge[];
    payments: VisitPayment[];
    discounts: BillingDiscount[];
    paymentMethods: { value: string; label: string }[];
};

type PaymentModalState = { label: string; prefillAmount: string } | null;

export function OpdPaymentWorkspace({
    visitId,
    payerType,
    billing,
    charges,
    payments,
    discounts,
    paymentMethods,
}: OpdPaymentWorkspaceProps) {
    const { hasPermission } = usePermissions();
    const canRequestDiscount = hasPermission('billing_discounts.create');
    const canApproveDiscount = hasPermission('billing_discounts.approve');
    const canReverseDiscount = hasPermission('billing_discounts.reverse');

    const [paymentModal, setPaymentModal] = useState<PaymentModalState>(null);
    const [discountModal, setDiscountModal] = useState(false);
    const [reversalReasons, setReversalReasons] = useState<
        Record<string, string>
    >({});

    const paymentForm = useForm({
        amount: '',
        payment_method_id: paymentMethods[0]?.value ?? '',
        payment_date: '',
        reference_number: '',
        notes: '',
    });

    const discountForm = useForm({
        amount: '',
        reason: '',
        notes: '',
    });

    const isInsurancePayer = payerType === 'insurance';
    const hasOutstanding = (billing?.balance_amount ?? 0) > 0;
    const patientCopayBalance = billing?.split?.patient_balance_amount ?? 0;
    const hasCashierBalance = isInsurancePayer
        ? patientCopayBalance > 0
        : hasOutstanding;
    const primaryPaymentAmount =
        isInsurancePayer && patientCopayBalance > 0
            ? patientCopayBalance
            : (billing?.balance_amount ?? 0);
    const primaryPaymentLabel =
        isInsurancePayer && patientCopayBalance > 0
            ? 'Patient Copay'
            : 'All Outstanding';
    const primaryPaymentButton =
        isInsurancePayer && patientCopayBalance > 0
            ? 'Pay Patient Copay'
            : 'Pay All Outstanding';

    function openPaymentModal(label: string, prefillAmount: string) {
        paymentForm.setData('amount', prefillAmount);
        setPaymentModal({ label, prefillAmount });
    }

    function closePaymentModal() {
        setPaymentModal(null);
        paymentForm.reset();
    }

    function submitPayment() {
        paymentForm.post(`/finance/opd-payments/${visitId}/payments`, {
            onSuccess: closePaymentModal,
        });
    }

    function submitDiscount() {
        discountForm.post(`/finance/opd-payments/${visitId}/discounts`, {
            onSuccess: () => {
                setDiscountModal(false);
                discountForm.reset('amount', 'reason', 'notes');
            },
        });
    }

    return (
        <div className="space-y-8">
            {/* Service Charges */}
            <div>
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-base font-semibold">Service Charges</h2>
                    {hasOutstanding && (
                        <div className="flex gap-2">
                            {canRequestDiscount && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setDiscountModal(true)}
                                >
                                    <Tag className="mr-1.5 h-3.5 w-3.5" />
                                    Apply Discount
                                </Button>
                            )}
                            {hasCashierBalance ? (
                                <Button
                                    size="sm"
                                    onClick={() =>
                                        openPaymentModal(
                                            primaryPaymentLabel,
                                            String(primaryPaymentAmount || ''),
                                        )
                                    }
                                >
                                    {primaryPaymentButton}
                                </Button>
                            ) : null}
                        </div>
                    )}
                </div>

                {charges.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No charge lines have been added to this visit yet.
                    </p>
                ) : (
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Service</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead className="text-right">
                                        Qty × Unit
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    {isInsurancePayer ? (
                                        <>
                                            <TableHead className="text-right">
                                                Patient Copay
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Insurer
                                            </TableHead>
                                        </>
                                    ) : null}
                                    <TableHead>Status</TableHead>
                                    {hasCashierBalance && <TableHead />}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {charges.map((charge) => {
                                    const chargeCashierAmount = isInsurancePayer
                                        ? Math.min(
                                              charge.copay_amount ?? 0,
                                              patientCopayBalance ||
                                                  charge.line_total,
                                          )
                                        : charge.line_total;

                                    return (
                                        <TableRow key={charge.id}>
                                        <TableCell className="font-medium">
                                            {charge.description}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {charge.charge_code ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right text-sm">
                                            {charge.quantity} &times;{' '}
                                            {formatMoney(charge.unit_price)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatMoney(charge.line_total)}
                                        </TableCell>
                                        {isInsurancePayer ? (
                                            <>
                                                <TableCell className="text-right font-medium">
                                                    {formatMoney(
                                                        charge.copay_amount ??
                                                            0,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right text-sm text-muted-foreground">
                                                    {formatMoney(
                                                        Math.max(
                                                            0,
                                                            charge.line_total -
                                                                (charge.copay_amount ??
                                                                    0),
                                                        ),
                                                    )}
                                                </TableCell>
                                            </>
                                        ) : null}
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className="capitalize"
                                            >
                                                {charge.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>
                                        </TableCell>
                                        {hasCashierBalance && (
                                            <TableCell className="text-right">
                                                {chargeCashierAmount > 0 ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            openPaymentModal(
                                                                charge.description,
                                                                String(
                                                                    chargeCashierAmount,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        Pay
                                                    </Button>
                                                ) : null}
                                            </TableCell>
                                        )}
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>

            {/* Payment History */}
            <div>
                <h2 className="mb-3 text-base font-semibold">
                    Payment History
                </h2>
                {payments.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No payments recorded yet.
                    </p>
                ) : (
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Receipt</TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payments.map((payment) => (
                                    <TableRow key={payment.id}>
                                        <TableCell className="text-sm">
                                            {formatDateTime(
                                                payment.payment_date,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm capitalize">
                                            {payment.payment_method?.replaceAll(
                                                '_',
                                                ' ',
                                            ) ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {payment.reference_number ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {payment.receipt_number ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {payment.is_refund ? (
                                                <span className="text-destructive">
                                                    &minus;
                                                    {formatMoney(
                                                        payment.amount,
                                                    )}
                                                </span>
                                            ) : (
                                                formatMoney(payment.amount)
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {!payment.is_refund && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a
                                                        href={`/visits/${visitId}/payments/${payment.id}/print`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        <Printer className="h-3.5 w-3.5" />
                                                    </a>
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>

            {/* Discounts */}
            {discounts.length > 0 && (
                <div>
                    <h2 className="mb-3 text-base font-semibold">Discounts</h2>
                    <div className="space-y-2">
                        {discounts.map((discount) => (
                            <div
                                key={discount.id}
                                className="flex flex-col gap-3 rounded-lg border px-4 py-3"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {formatMoney(discount.amount)}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {discount.reason}
                                        </p>
                                        {discount.notes ? (
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {discount.notes}
                                            </p>
                                        ) : null}
                                        {discount.reversal_reason ? (
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                Reversal:{' '}
                                                {discount.reversal_reason}
                                            </p>
                                        ) : null}
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        className="shrink-0 capitalize"
                                    >
                                        {discount.status.replaceAll('_', ' ')}
                                    </Badge>
                                </div>

                                {discount.status === 'pending' &&
                                    canApproveDiscount && (
                                        <div className="flex justify-end">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        `/finance/opd-payments/${visitId}/discounts/${discount.id}/approve`,
                                                    )
                                                }
                                            >
                                                Approve
                                            </Button>
                                        </div>
                                    )}

                                {discount.status === 'approved' &&
                                    canReverseDiscount && (
                                        <form
                                            className="flex gap-2"
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                router.post(
                                                    `/finance/opd-payments/${visitId}/discounts/${discount.id}/reverse`,
                                                    {
                                                        reversal_reason:
                                                            reversalReasons[
                                                                discount.id
                                                            ] ?? '',
                                                    },
                                                    {
                                                        onSuccess: () =>
                                                            setReversalReasons(
                                                                (cur) => ({
                                                                    ...cur,
                                                                    [discount.id]:
                                                                        '',
                                                                }),
                                                            ),
                                                    },
                                                );
                                            }}
                                        >
                                            <Input
                                                placeholder="Reversal reason"
                                                value={
                                                    reversalReasons[
                                                        discount.id
                                                    ] ?? ''
                                                }
                                                onChange={(e) =>
                                                    setReversalReasons(
                                                        (cur) => ({
                                                            ...cur,
                                                            [discount.id]:
                                                                e.target.value,
                                                        }),
                                                    )
                                                }
                                            />
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                size="sm"
                                                className="shrink-0"
                                            >
                                                Reverse
                                            </Button>
                                        </form>
                                    )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Payment Modal */}
            <Dialog
                open={paymentModal !== null}
                onOpenChange={(open) => !open && closePaymentModal()}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {paymentModal?.label
                                ? `Pay — ${paymentModal.label}`
                                : 'Record Payment'}
                        </DialogTitle>
                    </DialogHeader>
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submitPayment();
                        }}
                    >
                        <div className="space-y-2">
                            <Label htmlFor="pay_amount">Amount</Label>
                            <Input
                                id="pay_amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={String(primaryPaymentAmount || '')}
                                value={paymentForm.data.amount}
                                onChange={(e) =>
                                    paymentForm.setData(
                                        'amount',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={paymentForm.errors.amount} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pay_method">Payment Method</Label>
                            <Select
                                value={paymentForm.data.payment_method_id}
                                onValueChange={(value) =>
                                    paymentForm.setData(
                                        'payment_method_id',
                                        value,
                                    )
                                }
                            >
                                <SelectTrigger id="pay_method">
                                    <SelectValue placeholder="Select method" />
                                </SelectTrigger>
                                <SelectContent>
                                    {paymentMethods.map((method) => (
                                        <SelectItem
                                            key={method.value}
                                            value={method.value}
                                        >
                                            {method.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={paymentForm.errors.payment_method_id}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pay_date">Payment Date</Label>
                            <Input
                                id="pay_date"
                                type="datetime-local"
                                value={paymentForm.data.payment_date}
                                onChange={(e) =>
                                    paymentForm.setData(
                                        'payment_date',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={paymentForm.errors.payment_date}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pay_ref">Reference Number</Label>
                            <Input
                                id="pay_ref"
                                value={paymentForm.data.reference_number}
                                onChange={(e) =>
                                    paymentForm.setData(
                                        'reference_number',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={paymentForm.errors.reference_number}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pay_notes">Notes</Label>
                            <Textarea
                                id="pay_notes"
                                value={paymentForm.data.notes}
                                onChange={(e) =>
                                    paymentForm.setData('notes', e.target.value)
                                }
                            />
                            <InputError message={paymentForm.errors.notes} />
                        </div>
                        <div className="flex justify-end gap-2 pt-1">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closePaymentModal}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={paymentForm.processing}
                            >
                                Receive Payment
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Discount Modal */}
            <Dialog open={discountModal} onOpenChange={setDiscountModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Apply Discount</DialogTitle>
                    </DialogHeader>
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submitDiscount();
                        }}
                    >
                        <div className="space-y-2">
                            <Label htmlFor="disc_amount">Amount</Label>
                            <Input
                                id="disc_amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={String(billing?.balance_amount ?? '')}
                                value={discountForm.data.amount}
                                onChange={(e) =>
                                    discountForm.setData(
                                        'amount',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={discountForm.errors.amount} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="disc_reason">Reason</Label>
                            <Input
                                id="disc_reason"
                                value={discountForm.data.reason}
                                onChange={(e) =>
                                    discountForm.setData(
                                        'reason',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={discountForm.errors.reason} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="disc_notes">Notes</Label>
                            <Textarea
                                id="disc_notes"
                                value={discountForm.data.notes}
                                onChange={(e) =>
                                    discountForm.setData(
                                        'notes',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={discountForm.errors.notes} />
                        </div>
                        <div className="flex justify-end gap-2 pt-1">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDiscountModal(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={discountForm.processing}
                            >
                                Request Discount
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
