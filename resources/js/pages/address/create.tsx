import AddressController from '@/actions/App/Http/Controllers/AddressController';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Country } from '@/types/country';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, MapPin } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface AddressCreateProps {
    countries: Country[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Addresses', href: AddressController.index.url() },
    { title: 'Create Address', href: AddressController.create.url() },
];

export default function AddressCreate({ countries }: AddressCreateProps) {
    const [countryId, setCountryId] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Address" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create New Address
                        </h2>
                        <Button variant="outline" size="sm" asChild className="h-8">
                            <Link href={AddressController.index.url()}>Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Register a new location address.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...AddressController.store.form()}
                    onSuccess={() =>
                        toast.success('Address created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="city"
                                        className="text-sm font-semibold"
                                    >
                                        City
                                    </Label>
                                    <Input
                                        id="city"
                                        name="city"
                                        placeholder="e.g. Kampala"
                                        autoFocus
                                        required
                                    />
                                    <InputError message={errors.city} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="district"
                                            className="text-sm font-semibold"
                                        >
                                            District
                                        </Label>
                                        <Input
                                            id="district"
                                            name="district"
                                            placeholder="e.g. Central"
                                        />
                                        <InputError message={errors.district} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="state"
                                            className="text-sm font-semibold"
                                        >
                                            State / Province
                                        </Label>
                                        <Input
                                            id="state"
                                            name="state"
                                            placeholder="e.g. Buganda"
                                        />
                                        <InputError message={errors.state} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="country_id"
                                        className="text-sm font-semibold"
                                    >
                                        Country
                                    </Label>
                                    <Select
                                        value={countryId}
                                        onValueChange={setCountryId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select country" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {countries.map((country) => (
                                                <SelectItem
                                                    key={country.id}
                                                    value={country.id}
                                                >
                                                    {country.country_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="country_id"
                                        value={countryId}
                                    />
                                    <InputError message={errors.country_id} />
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
                                    Create Address
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link href={AddressController.index.url()}>
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
