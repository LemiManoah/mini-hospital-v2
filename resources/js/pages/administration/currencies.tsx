import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
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
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { usePermissions } from '@/lib/permissions';
import { type BreadcrumbItem } from '@/types';
import { type Currency, type CurrencyExchangeRate } from '@/types/currency';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight, LoaderCircle, Plus, Trash2 } from 'lucide-react';

type BranchCurrencyPageProps = {
    branch: {
        id: string;
        name: string;
        multi_currency_enabled: boolean;
    };
    defaultCurrency: Currency | null;
    selectedCurrencies: Currency[];
    availableCurrencies: Currency[];
    rates: CurrencyExchangeRate[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/administration/general-settings' },
    { title: 'Currencies', href: '/administration/currencies' },
];

export default function AdministrationCurrencies({
    branch,
    defaultCurrency,
    selectedCurrencies,
    availableCurrencies,
    rates,
}: BranchCurrencyPageProps) {
    const { hasPermission } = usePermissions();
    const canUpdateCurrencies = hasPermission('currencies.update');
    const canCreateRates = hasPermission('currency_exchange_rates.create');
    const canDeleteRates = hasPermission('currency_exchange_rates.delete');
    const toggleForm = useForm({
        multi_currency_enabled: branch.multi_currency_enabled,
    });
    const currencyForm = useForm({
        currency_id: '',
    });
    const rateForm = useForm({
        from_currency_id: '',
        to_currency_id: defaultCurrency?.id ?? '',
        rate: '',
        effective_date: new Date().toISOString().slice(0, 10),
        notes: '',
    });

    const selectedIds = new Set(
        selectedCurrencies.map((currency) => currency.id),
    );
    const currencyOptions = availableCurrencies
        .filter((currency) => !selectedIds.has(currency.id))
        .map((currency) => ({
            value: currency.id,
            label: `${currency.name} (${currency.code}${currency.symbol ? ` - ${currency.symbol}` : ''})`,
        }));
    const selectedCurrencyOptions = selectedCurrencies.map((currency) => ({
        value: currency.id,
        label: `${currency.name} (${currency.code}${currency.symbol ? ` - ${currency.symbol}` : ''})`,
    }));

    function updateMultiCurrency(enabled: boolean) {
        toggleForm.setData('multi_currency_enabled', enabled);
        toggleForm.patch('/administration/currencies/multi-currency', {
            preserveScroll: true,
        });
    }

    function addCurrency() {
        currencyForm.post('/administration/currencies/selected', {
            preserveScroll: true,
            onSuccess: () => currencyForm.reset(),
        });
    }

    function removeCurrency(currency: Currency) {
        router.delete(`/administration/currencies/selected/${currency.id}`, {
            preserveScroll: true,
        });
    }

    function addRate() {
        rateForm.post('/currency-exchange-rates', {
            preserveScroll: true,
            onSuccess: () =>
                rateForm.reset('from_currency_id', 'rate', 'notes'),
        });
    }

    function removeRate(rate: CurrencyExchangeRate) {
        router.delete(`/currency-exchange-rates/${rate.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Currencies" />

            <div className="flex flex-col gap-6 p-6">
                <div className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Currencies
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Manage accepted currencies and exchange rates for{' '}
                        {branch.name}.
                    </p>
                </div>

                <Card className="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader>
                        <CardTitle>Multi-Currency</CardTitle>
                        <CardDescription>
                            Base currency:{' '}
                            {defaultCurrency
                                ? `${defaultCurrency.name} (${defaultCurrency.code})`
                                : 'Not set'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <div className="space-y-1">
                                <Label
                                    htmlFor="multi_currency_enabled"
                                    className="text-sm font-semibold"
                                >
                                    Enable multi-currency for this branch
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    When enabled, payment screens can collect in
                                    selected currencies using branch exchange
                                    rates.
                                </p>
                                <InputError
                                    message={
                                        toggleForm.errors.multi_currency_enabled
                                    }
                                />
                            </div>
                            <Switch
                                id="multi_currency_enabled"
                                checked={toggleForm.data.multi_currency_enabled}
                                onCheckedChange={updateMultiCurrency}
                                disabled={
                                    toggleForm.processing ||
                                    !canUpdateCurrencies
                                }
                            />
                        </div>
                    </CardContent>
                </Card>

                {branch.multi_currency_enabled ? (
                    <>
                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Accepted Currencies</CardTitle>
                                <CardDescription>
                                    Add the currencies this branch can receive
                                    and report against.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {canUpdateCurrencies ? (
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                                        <div className="grid flex-1 gap-2">
                                            <Label htmlFor="currency_id">
                                                Currency
                                            </Label>
                                            <SearchableSelect
                                                options={currencyOptions}
                                                value={
                                                    currencyForm.data
                                                        .currency_id
                                                }
                                                onValueChange={(value) =>
                                                    currencyForm.setData(
                                                        'currency_id',
                                                        value,
                                                    )
                                                }
                                                inputId="currency_id"
                                                placeholder="Choose currency"
                                                emptyMessage="No more currencies available."
                                                allowClear
                                            />
                                            <InputError
                                                message={
                                                    currencyForm.errors
                                                        .currency_id
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            onClick={addCurrency}
                                            disabled={
                                                currencyForm.processing ||
                                                currencyForm.data
                                                    .currency_id === ''
                                            }
                                        >
                                            {currencyForm.processing ? (
                                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                            ) : (
                                                <Plus className="mr-2 h-4 w-4" />
                                            )}
                                            Add Currency
                                        </Button>
                                    </div>
                                ) : null}

                                <div className="rounded-lg border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Code</TableHead>
                                                <TableHead>Name</TableHead>
                                                <TableHead>Symbol</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead className="text-right">
                                                    Actions
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {selectedCurrencies.map(
                                                (currency) => (
                                                    <TableRow key={currency.id}>
                                                        <TableCell className="font-semibold">
                                                            {currency.code}
                                                        </TableCell>
                                                        <TableCell>
                                                            {currency.name}
                                                        </TableCell>
                                                        <TableCell>
                                                            {currency.symbol}
                                                        </TableCell>
                                                        <TableCell>
                                                            {currency.id ===
                                                            defaultCurrency?.id
                                                                ? 'Base'
                                                                : 'Enabled'}
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {currency.id !==
                                                                defaultCurrency?.id &&
                                                            canUpdateCurrencies ? (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        removeCurrency(
                                                                            currency,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            ) : null}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-none shadow-sm ring-1 ring-border/50">
                            <CardHeader>
                                <CardTitle>Exchange Rates</CardTitle>
                                <CardDescription>
                                    Rates convert payment tender amounts into
                                    the branch base currency.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {canCreateRates ? (
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                        <div className="grid gap-2">
                                            <Label htmlFor="from_currency_id">
                                                From
                                            </Label>
                                            <SearchableSelect
                                                options={
                                                    selectedCurrencyOptions
                                                }
                                                value={
                                                    rateForm.data
                                                        .from_currency_id
                                                }
                                                onValueChange={(value) =>
                                                    rateForm.setData(
                                                        'from_currency_id',
                                                        value,
                                                    )
                                                }
                                                inputId="from_currency_id"
                                                placeholder="Source"
                                                emptyMessage="No currencies selected."
                                                allowClear
                                            />
                                            <InputError
                                                message={
                                                    rateForm.errors
                                                        .from_currency_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="to_currency_id">
                                                To
                                            </Label>
                                            <SearchableSelect
                                                options={
                                                    selectedCurrencyOptions
                                                }
                                                value={
                                                    rateForm.data.to_currency_id
                                                }
                                                onValueChange={(value) =>
                                                    rateForm.setData(
                                                        'to_currency_id',
                                                        value,
                                                    )
                                                }
                                                inputId="to_currency_id"
                                                placeholder="Target"
                                                emptyMessage="No currencies selected."
                                                allowClear
                                            />
                                            <InputError
                                                message={
                                                    rateForm.errors
                                                        .to_currency_id
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="rate">Rate</Label>
                                            <Input
                                                id="rate"
                                                type="number"
                                                min="0.000001"
                                                step="0.000001"
                                                value={rateForm.data.rate}
                                                onChange={(event) =>
                                                    rateForm.setData(
                                                        'rate',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={rateForm.errors.rate}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="effective_date">
                                                Effective Date
                                            </Label>
                                            <Input
                                                id="effective_date"
                                                type="date"
                                                value={
                                                    rateForm.data.effective_date
                                                }
                                                onChange={(event) =>
                                                    rateForm.setData(
                                                        'effective_date',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    rateForm.errors
                                                        .effective_date
                                                }
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                type="button"
                                                onClick={addRate}
                                                disabled={rateForm.processing}
                                            >
                                                {rateForm.processing ? (
                                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                ) : (
                                                    <Plus className="mr-2 h-4 w-4" />
                                                )}
                                                Add Rate
                                            </Button>
                                        </div>
                                    </div>
                                ) : null}

                                <div className="rounded-lg border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>From</TableHead>
                                                <TableHead />
                                                <TableHead>To</TableHead>
                                                <TableHead>Rate</TableHead>
                                                <TableHead>
                                                    Effective Date
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Actions
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {rates.length > 0 ? (
                                                rates.map((rate) => (
                                                    <TableRow key={rate.id}>
                                                        <TableCell className="font-semibold">
                                                            {
                                                                rate
                                                                    .from_currency
                                                                    .code
                                                            }
                                                        </TableCell>
                                                        <TableCell>
                                                            <ArrowRight className="h-4 w-4 text-muted-foreground" />
                                                        </TableCell>
                                                        <TableCell className="font-semibold">
                                                            {
                                                                rate.to_currency
                                                                    .code
                                                            }
                                                        </TableCell>
                                                        <TableCell>
                                                            {Number(
                                                                rate.rate,
                                                            ).toLocaleString(
                                                                undefined,
                                                                {
                                                                    minimumFractionDigits: 2,
                                                                    maximumFractionDigits: 6,
                                                                },
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {
                                                                rate.effective_date
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            {canDeleteRates ? (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        removeRate(
                                                                            rate,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            ) : null}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={6}
                                                        className="py-8 text-center text-sm text-muted-foreground"
                                                    >
                                                        No exchange rates have
                                                        been configured yet.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </>
                ) : null}
            </div>
        </AppLayout>
    );
}
