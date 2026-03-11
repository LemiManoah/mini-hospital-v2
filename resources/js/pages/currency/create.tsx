import CurrencyController from '@/actions/App/Http/Controllers/CurrencyController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, Coins, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Currencies', href: CurrencyController.index.url() },
    { title: 'Create Currency', href: CurrencyController.create.url() },
];

export default function CurrencyCreate() {
    const [modifiable, setModifiable] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Currency" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create New Currency
                        </h2>
                        <Button variant="outline" size="sm" asChild className="h-8">
                            <Link href={CurrencyController.index.url()}>Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Add a new currency for financial transactions.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...CurrencyController.store.form()}
                    onSuccess={() =>
                        toast.success('Currency created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="code"
                                            className="text-sm font-semibold"
                                        >
                                            Currency Code (ISO)
                                        </Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            placeholder="e.g. USD, UGX"
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="symbol"
                                            className="text-sm font-semibold"
                                        >
                                            Symbol
                                        </Label>
                                        <Input
                                            id="symbol"
                                            name="symbol"
                                            placeholder="e.g. $, USh"
                                            required
                                        />
                                        <InputError message={errors.symbol} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="name"
                                        className="text-sm font-semibold"
                                    >
                                        Currency Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="e.g. US Dollar"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    <Checkbox
                                        id="modifiable"
                                        checked={modifiable}
                                        onCheckedChange={(checked) =>
                                            setModifiable(checked === true)
                                        }
                                    />
                                    <div className="grid gap-1.5 leading-none">
                                        <Label
                                            htmlFor="modifiable"
                                            className="cursor-pointer text-sm leading-none font-medium"
                                        >
                                            Modifiable
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Allow this currency to be edited or
                                            deleted in the future.
                                        </p>
                                    </div>
                                </div>
                                <input
                                    type="hidden"
                                    name="modifiable"
                                    value={modifiable ? '1' : '0'}
                                />
                                <InputError message={errors.modifiable} />
                            </div>

                            <div className="flex items-center justify-start gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-w-[140px]"
                                >
                                    {processing ? (
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle2 className="mr-2 h-4 w-4" />
                                    )}
                                    Create Currency
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={CurrencyController.index.url()}>
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
