import CurrencyController from '@/actions/App/Http/Controllers/CurrencyController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Currency } from '@/types/currency';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Coins, Save, Lock } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface CurrencyEditProps {
    currency: Currency;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Currencies', href: CurrencyController.index.url() },
    { title: 'Edit Currency', href: '#' },
];

export default function CurrencyEdit({ currency }: CurrencyEditProps) {
    const [modifiable, setModifiable] = useState(currency.modifiable);

    if (!currency.modifiable) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Currency Locked" />
                <div className="flex flex-col items-center justify-center h-[60vh] gap-4">
                    <Lock className="h-16 w-16 text-red-500" />
                    <h2 className="text-2xl font-bold">This Currency is Locked</h2>
                    <p className="text-muted-foreground text-center max-w-md">
                        The system-critical currency "{currency.name}" ({currency.code}) cannot be modified for data integrity reasons.
                    </p>
                    <Button asChild variant="outline">
                        <Link href={CurrencyController.index.url()}>Back to List</Link>
                    </Button>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Currency: ${currency.name}`} />

            <div className="mt-4 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-4">
                <div className="flex flex-col gap-1 w-full">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <Coins className="h-6 w-6 text-indigo-500" />
                        Edit Currency: {currency.name}
                    </h2>
                    <p className="text-muted-foreground">
                        Update currency symbols or configuration.
                    </p>
                </div>
            </div>

            <div className="m-2 rounded border bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <Form
                    {...CurrencyController.update.form({ currency })}
                    onSuccess={() => toast.success('Currency updated successfully.')}
                    className="p-6 space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="code" className="text-sm font-semibold">
                                            Currency Code (ISO)
                                        </Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            defaultValue={currency.code}
                                            placeholder="e.g. USD, UGX"
                                            required
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="symbol" className="text-sm font-semibold">
                                            Symbol
                                        </Label>
                                        <Input
                                            id="symbol"
                                            name="symbol"
                                            defaultValue={currency.symbol}
                                            placeholder="e.g. $, USh"
                                            required
                                        />
                                        <InputError message={errors.symbol} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name" className="text-sm font-semibold">
                                        Currency Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={currency.name}
                                        placeholder="e.g. US Dollar"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="flex items-center gap-2 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                    <Checkbox
                                        id="modifiable"
                                        checked={modifiable}
                                        onCheckedChange={(checked) => setModifiable(checked === true)}
                                    />
                                    <div className="grid gap-1.5 leading-none">
                                        <Label htmlFor="modifiable" className="text-sm font-medium leading-none cursor-pointer">
                                            Modifiable
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Allow this currency to be edited or deleted in the future.
                                        </p>
                                    </div>
                                </div>
                                <input type="hidden" name="modifiable" value={modifiable ? '1' : '0'} />
                                <InputError message={errors.modifiable} />
                            </div>

                            <div className="flex items-center justify-start gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                                <Button type="submit" disabled={processing} className="min-w-[140px]">
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin mr-2" />
                                    ) : (
                                        <Save className="h-4 w-4 mr-2" />
                                    )}
                                    Save Changes
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={CurrencyController.index.url()}>Cancel</Link>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
