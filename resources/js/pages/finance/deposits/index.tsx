import { Badge } from '@/components/ui/badge';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import {
    formatDateTime,
    formatMoney,
} from '@/pages/visit/components/visit-show-utils';
import { type BreadcrumbItem } from '@/types';
import {
    type BillingDepositRow,
    type FinanceDepositsIndexPageProps,
} from '@/types/finance';
import { Head, router, useForm } from '@inertiajs/react';
import { Banknote, CheckCircle2, Search } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const statusClasses: Record<string, string> = {
    held: 'border-amber-200 bg-amber-50 text-amber-700',
    partially_applied: 'border-blue-200 bg-blue-50 text-blue-700',
    applied: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    refunded: 'border-slate-200 bg-slate-50 text-slate-700',
    cancelled: 'border-red-200 bg-red-50 text-red-700',
};

export default function FinanceDepositsIndexPage({
    deposits,
    filters,
    paymentMethods,
}: FinanceDepositsIndexPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [selectedDepositId, setSelectedDepositId] = useState<string | null>(
        null,
    );

    const depositForm = useForm({
        patient_number: '',
        visit_number: '',
        amount: '',
        payment_method_id: paymentMethods[0]?.value ?? '',
        reference_number: '',
        notes: '',
    });

    const applyForm = useForm({
        visit_number: '',
        amount: '',
        notes: '',
    });

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            router.get(
                '/finance/deposits',
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['deposits', 'filters'],
                },
            );
        }, 300);

        return () => window.clearTimeout(timeoutId);
    }, [filters.search, search]);

    const totals = useMemo(
        () => ({
            received: deposits.data.reduce(
                (sum, deposit) => sum + deposit.amount,
                0,
            ),
            applied: deposits.data.reduce(
                (sum, deposit) => sum + deposit.applied_amount,
                0,
            ),
            held: deposits.data.reduce(
                (sum, deposit) => sum + deposit.available_amount,
                0,
            ),
        }),
        [deposits.data],
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Finance & Accounting', href: '/finance/deposits' },
        { title: 'Deposits', href: '/finance/deposits' },
    ];

    function selectDeposit(deposit: BillingDepositRow): void {
        setSelectedDepositId(deposit.id);
        applyForm.setData({
            visit_number: deposit.visit_number ?? '',
            amount: deposit.available_amount.toFixed(2),
            notes: '',
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing Deposits" />

            <div className="m-4 flex flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="flex flex-col gap-2">
                        <h1 className="text-2xl font-semibold">
                            Billing Deposits
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Record advance patient deposits and apply held
                            balances to billed visits.
                        </p>
                        <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                            <span>
                                Listed received: {formatMoney(totals.received)}
                            </span>
                            <span>Applied: {formatMoney(totals.applied)}</span>
                            <span>Held: {formatMoney(totals.held)}</span>
                        </div>
                    </div>

                    <div className="relative w-full sm:w-80">
                        <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search deposit, patient, or phone..."
                            className="pl-9"
                        />
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Record Deposit</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_1.2fr] xl:grid-cols-[1fr_1fr_1fr_1.2fr_1fr_auto]"
                            onSubmit={(event) => {
                                event.preventDefault();
                                depositForm.post('/finance/deposits', {
                                    preserveScroll: true,
                                    onSuccess: () =>
                                        depositForm.reset(
                                            'patient_number',
                                            'visit_number',
                                            'amount',
                                            'reference_number',
                                            'notes',
                                        ),
                                });
                            }}
                        >
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="patient_number">
                                    Patient Number
                                </Label>
                                <Input
                                    id="patient_number"
                                    value={depositForm.data.patient_number}
                                    onChange={(event) =>
                                        depositForm.setData(
                                            'patient_number',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="visit_number">
                                    Visit Number
                                </Label>
                                <Input
                                    id="visit_number"
                                    value={depositForm.data.visit_number}
                                    onChange={(event) =>
                                        depositForm.setData(
                                            'visit_number',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="deposit_amount">Amount</Label>
                                <Input
                                    id="deposit_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={depositForm.data.amount}
                                    onChange={(event) =>
                                        depositForm.setData(
                                            'amount',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="payment_method_id">
                                    Payment Method
                                </Label>
                                <Select
                                    value={depositForm.data.payment_method_id}
                                    onValueChange={(value) =>
                                        depositForm.setData(
                                            'payment_method_id',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger id="payment_method_id">
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
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="reference_number">
                                    Reference
                                </Label>
                                <Input
                                    id="reference_number"
                                    value={depositForm.data.reference_number}
                                    onChange={(event) =>
                                        depositForm.setData(
                                            'reference_number',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                className="self-end"
                                disabled={depositForm.processing}
                            >
                                <Banknote data-icon="inline-start" />
                                Record
                            </Button>
                            <div className="lg:col-span-4 xl:col-span-6">
                                <Textarea
                                    value={depositForm.data.notes}
                                    onChange={(event) =>
                                        depositForm.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Notes"
                                    rows={2}
                                />
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {deposits.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-14 text-center text-sm text-muted-foreground">
                            No billing deposits were found in this branch.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-border/60 bg-background">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Deposit</TableHead>
                                    <TableHead>Patient</TableHead>
                                    <TableHead>Payment</TableHead>
                                    <TableHead>Received</TableHead>
                                    <TableHead>Applied</TableHead>
                                    <TableHead>Held</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {deposits.data.map((deposit) => (
                                    <TableRow key={deposit.id}>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {deposit.deposit_number}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDateTime(
                                                        deposit.received_at,
                                                    )}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span className="font-medium">
                                                    {deposit.patient_name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {deposit.patient_number ??
                                                        'No MRN'}
                                                    {deposit.visit_number
                                                        ? ` · ${deposit.visit_number}`
                                                        : ''}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <span>
                                                    {deposit.payment_method ??
                                                        'Unknown'}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {deposit.reference_number ??
                                                        'No reference'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(deposit.amount)}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                deposit.applied_amount,
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {formatMoney(
                                                deposit.available_amount,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    statusClasses[
                                                        deposit.status
                                                    ] ?? ''
                                                }
                                            >
                                                {deposit.status.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {deposit.available_amount > 0 ? (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        selectDeposit(deposit)
                                                    }
                                                >
                                                    Apply
                                                </Button>
                                            ) : null}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {selectedDepositId ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Apply Deposit</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                className="grid gap-4 md:grid-cols-[1fr_1fr_2fr_auto]"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    applyForm.post(
                                        `/finance/deposits/${selectedDepositId}/apply`,
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                setSelectedDepositId(null);
                                                applyForm.reset();
                                            },
                                        },
                                    );
                                }}
                            >
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="apply_visit_number">
                                        Visit Number
                                    </Label>
                                    <Input
                                        id="apply_visit_number"
                                        value={applyForm.data.visit_number}
                                        onChange={(event) =>
                                            applyForm.setData(
                                                'visit_number',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="apply_amount">Amount</Label>
                                    <Input
                                        id="apply_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={applyForm.data.amount}
                                        onChange={(event) =>
                                            applyForm.setData(
                                                'amount',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="apply_notes">Notes</Label>
                                    <Input
                                        id="apply_notes"
                                        value={applyForm.data.notes}
                                        onChange={(event) =>
                                            applyForm.setData(
                                                'notes',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="self-end"
                                    disabled={applyForm.processing}
                                >
                                    <CheckCircle2 data-icon="inline-start" />
                                    Apply
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                ) : null}
            </div>
        </AppLayout>
    );
}
