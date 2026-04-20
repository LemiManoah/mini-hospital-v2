import CurrencyController from '@/actions/App/Http/Controllers/CurrencyController';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type Currency, type CurrencyExchangeRate } from '@/types/currency';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, LoaderCircle, Plus, TrendingUp } from 'lucide-react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Currencies', href: CurrencyController.index.url() },
    { title: 'Exchange Rates', href: '/currency-exchange-rates' },
];

interface ExchangeRatesPageProps {
    rates: CurrencyExchangeRate[];
    currencies: Currency[];
}

export default function ExchangeRates({ rates, currencies }: ExchangeRatesPageProps) {
    const { hasPermission } = usePermissions();

    const currencyOptions = currencies.map((c) => ({
        value: c.id,
        label: `${c.name} (${c.code} — ${c.symbol})`,
    }));

    const form = useForm({
        from_currency_id: '',
        to_currency_id: '',
        rate: '',
        effective_date: new Date().toISOString().slice(0, 10),
        notes: '',
    });

    const handleSubmit = () => {
        form.post('/currency-exchange-rates', {
            onSuccess: () => {
                form.reset();
                toast.success('Exchange rate added.');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Currency Exchange Rates" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2">
                        <TrendingUp className="h-6 w-6 text-indigo-500" />
                        <h1 className="text-2xl font-bold tracking-tight">Currency Exchange Rates</h1>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Define conversion rates between currencies for your facility. Rates are effective from the date specified.
                    </p>
                    <div className="mt-1">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={CurrencyController.index.url()}>← Back to Currencies</Link>
                        </Button>
                    </div>
                </div>

                {hasPermission('currency_exchange_rates.create') ? (
                    <Card className="border-none shadow-sm ring-1 ring-border/50">
                        <CardHeader>
                            <CardTitle className="text-base">Add Exchange Rate</CardTitle>
                            <CardDescription>
                                Set how many units of the target currency equal one unit of the source currency.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="from_currency_id" className="text-sm font-semibold">
                                        From Currency
                                    </Label>
                                    <SearchableSelect
                                        options={currencyOptions}
                                        value={form.data.from_currency_id}
                                        onValueChange={(value) => form.setData('from_currency_id', value)}
                                        inputId="from_currency_id"
                                        placeholder="Source currency"
                                        emptyMessage="No currencies found."
                                        allowClear
                                    />
                                    <InputError message={form.errors.from_currency_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="to_currency_id" className="text-sm font-semibold">
                                        To Currency
                                    </Label>
                                    <SearchableSelect
                                        options={currencyOptions}
                                        value={form.data.to_currency_id}
                                        onValueChange={(value) => form.setData('to_currency_id', value)}
                                        inputId="to_currency_id"
                                        placeholder="Target currency"
                                        emptyMessage="No currencies found."
                                        allowClear
                                    />
                                    <InputError message={form.errors.to_currency_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="rate" className="text-sm font-semibold">
                                        Rate
                                    </Label>
                                    <Input
                                        id="rate"
                                        type="number"
                                        step="0.000001"
                                        min="0.000001"
                                        value={form.data.rate}
                                        onChange={(e) => form.setData('rate', e.target.value)}
                                        placeholder="e.g. 3800.5"
                                    />
                                    <InputError message={form.errors.rate} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="effective_date" className="text-sm font-semibold">
                                        Effective Date
                                    </Label>
                                    <Input
                                        id="effective_date"
                                        type="date"
                                        value={form.data.effective_date}
                                        onChange={(e) => form.setData('effective_date', e.target.value)}
                                    />
                                    <InputError message={form.errors.effective_date} />
                                </div>

                                <div className="grid gap-2 sm:col-span-2 lg:col-span-2">
                                    <Label htmlFor="notes" className="text-sm font-semibold">
                                        Notes <span className="font-normal text-muted-foreground">(optional)</span>
                                    </Label>
                                    <Input
                                        id="notes"
                                        value={form.data.notes}
                                        onChange={(e) => form.setData('notes', e.target.value)}
                                        placeholder="e.g. Bank of Uganda mid-rate"
                                    />
                                    <InputError message={form.errors.notes} />
                                </div>
                            </div>

                            <div className="mt-4">
                                <Button onClick={handleSubmit} disabled={form.processing}>
                                    {form.processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <Plus className="mr-2 h-4 w-4" />
                                    )}
                                    Add Rate
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ) : null}

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle className="text-base">Configured Rates</CardTitle>
                        <CardDescription>
                            {rates.length === 0
                                ? 'No exchange rates configured yet.'
                                : `${rates.length} rate${rates.length !== 1 ? 's' : ''} on record, most recent first.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {rates.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-xs font-semibold uppercase tracking-wider">
                                            From
                                        </TableHead>
                                        <TableHead className="w-8" />
                                        <TableHead className="text-xs font-semibold uppercase tracking-wider">
                                            To
                                        </TableHead>
                                        <TableHead className="text-xs font-semibold uppercase tracking-wider">
                                            Rate
                                        </TableHead>
                                        <TableHead className="text-xs font-semibold uppercase tracking-wider">
                                            Effective Date
                                        </TableHead>
                                        <TableHead className="text-xs font-semibold uppercase tracking-wider">
                                            Notes
                                        </TableHead>
                                        {hasPermission('currency_exchange_rates.delete') ? (
                                            <TableHead className="w-[80px] text-right text-xs font-semibold uppercase tracking-wider">
                                                Actions
                                            </TableHead>
                                        ) : null}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rates.map((rate) => (
                                        <TableRow key={rate.id} className="group">
                                            <TableCell>
                                                <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                    {rate.from_currency.code}
                                                </span>
                                                <span className="ml-1 text-xs text-muted-foreground">
                                                    {rate.from_currency.symbol}
                                                </span>
                                            </TableCell>
                                            <TableCell className="px-0 text-muted-foreground">
                                                <ArrowRight className="h-4 w-4" />
                                            </TableCell>
                                            <TableCell>
                                                <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                    {rate.to_currency.code}
                                                </span>
                                                <span className="ml-1 text-xs text-muted-foreground">
                                                    {rate.to_currency.symbol}
                                                </span>
                                            </TableCell>
                                            <TableCell className="font-mono text-sm text-indigo-600 dark:text-indigo-400">
                                                {rate.rate.toLocaleString(undefined, {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 6,
                                                })}
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {new Date(rate.effective_date).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell className="max-w-[200px] truncate text-sm text-muted-foreground">
                                                {rate.notes ?? '—'}
                                            </TableCell>
                                            {hasPermission('currency_exchange_rates.delete') ? (
                                                <TableCell className="text-right">
                                                    <DeleteConfirmationModal
                                                        title="Remove Exchange Rate"
                                                        description={`Remove the ${rate.from_currency.code} → ${rate.to_currency.code} rate effective ${new Date(rate.effective_date).toLocaleDateString()}?`}
                                                        action={{
                                                            url: `/currency-exchange-rates/${rate.id}`,
                                                            method: 'delete',
                                                        }}
                                                        onSuccess={() => toast.success('Exchange rate removed.')}
                                                        trigger={
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                className="h-7 px-2 text-xs"
                                                            >
                                                                Remove
                                                            </Button>
                                                        }
                                                    />
                                                </TableCell>
                                            ) : null}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="py-12 text-center text-sm text-zinc-500 italic">
                                No exchange rates have been configured yet.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
