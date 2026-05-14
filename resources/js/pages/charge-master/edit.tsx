import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type ChargeMasterEditPageProps } from '@/types/charge-master';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

const dateValue = (value: string | null): string =>
    value === null ? '' : value.slice(0, 10);

export default function ChargeMasterEdit({
    chargeMaster,
}: ChargeMasterEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Charge Master', href: '/charge-masters' },
        {
            title: 'Edit Price',
            href: `/charge-masters/${chargeMaster.id}/edit`,
        },
    ];

    const form = useForm({
        unit_price: String(chargeMaster.unit_price ?? ''),
        is_active: chargeMaster.is_active,
        effective_from: dateValue(chargeMaster.effective_from),
        effective_to: dateValue(chargeMaster.effective_to),
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.put(`/charge-masters/${chargeMaster.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Charge Master Price" />

            <div className="m-4 max-w-3xl space-y-6">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold">
                        Edit Charge Master Price
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {chargeMaster.item_code} - {chargeMaster.description}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Current Price</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-6" onSubmit={submit}>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="unit_price">
                                        Unit Price
                                    </Label>
                                    <Input
                                        id="unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.data.unit_price}
                                        onChange={(event) =>
                                            form.setData(
                                                'unit_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.unit_price}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="effective_from">
                                        Effective From
                                    </Label>
                                    <Input
                                        id="effective_from"
                                        type="date"
                                        value={form.data.effective_from}
                                        onChange={(event) =>
                                            form.setData(
                                                'effective_from',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.effective_from}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="effective_to">
                                        Effective To
                                    </Label>
                                    <Input
                                        id="effective_to"
                                        type="date"
                                        value={form.data.effective_to}
                                        onChange={(event) =>
                                            form.setData(
                                                'effective_to',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.effective_to}
                                    />
                                </div>
                            </div>

                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={form.data.is_active}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_active',
                                            checked === true,
                                        )
                                    }
                                />
                                Keep this charge active
                            </label>

                            <div className="flex justify-end gap-2">
                                <Button variant="outline" asChild>
                                    <Link href="/charge-masters">Cancel</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    Save Price
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
