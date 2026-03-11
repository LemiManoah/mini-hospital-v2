import UnitController from '@/actions/App/Http/Controllers/UnitController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Units', href: UnitController.index.url() },
    { title: 'Create Unit', href: UnitController.create.url() },
];

export default function UnitCreate() {
    const [type, setType] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Unit" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create New Unit
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="h-8"
                        >
                            <Link href={UnitController.index.url()}>Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Define a new unit of measurement.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...UnitController.store.form()}
                    onSuccess={() =>
                        toast.success('Unit created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="name"
                                            className="text-sm font-semibold"
                                        >
                                            Unit Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="e.g. Milligram, Liter"
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
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
                                            placeholder="e.g. mg, L, °C"
                                            required
                                        />
                                        <InputError message={errors.symbol} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="type"
                                        className="text-sm font-semibold"
                                    >
                                        Type
                                    </Label>
                                    <Select
                                        value={type}
                                        onValueChange={setType}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="mass">
                                                Mass
                                            </SelectItem>
                                            <SelectItem value="volume">
                                                Volume
                                            </SelectItem>
                                            <SelectItem value="length">
                                                Length
                                            </SelectItem>
                                            <SelectItem value="temperature">
                                                Temperature
                                            </SelectItem>
                                            <SelectItem value="time">
                                                Time
                                            </SelectItem>
                                            <SelectItem value="count">
                                                Count
                                            </SelectItem>
                                            <SelectItem value="other">
                                                Other
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="type"
                                        value={type}
                                    />
                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="description"
                                        className="text-sm font-semibold"
                                    >
                                        Description (Optional)
                                    </Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        placeholder="Additional details about this unit..."
                                        className="min-h-[100px]"
                                    />
                                    <InputError message={errors.description} />
                                </div>
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
                                    Create Unit
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={UnitController.index.url()}>
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
