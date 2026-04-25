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
import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';

interface PackageOption {
    id: string;
    name: string;
    users: number;
    price: string | number;
}

interface FacilityManagerCreateProps {
    facilityLevels: { value: string; label: string }[];
    subscriptionPackages: PackageOption[];
    countries: { id: string; name: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Facility Manager', href: '/facility-manager/dashboard' },
    { title: 'Facilities', href: '/facility-manager/facilities' },
    { title: 'Create Facility', href: '/facility-manager/facilities/create' },
];

export default function FacilityManagerCreate({
    facilityLevels,
    subscriptionPackages,
    countries,
}: FacilityManagerCreateProps) {
    const [facilityLevel, setFacilityLevel] = useState(
        facilityLevels.find((option) => option.value === 'hospital')?.value ??
            facilityLevels[0]?.value ??
            '',
    );
    const [subscriptionPackageId, setSubscriptionPackageId] = useState(
        subscriptionPackages[0]?.id ?? '',
    );
    const [countryId, setCountryId] = useState('');

    const selectedPackage = subscriptionPackages.find(
        (item) => item.id === subscriptionPackageId,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Facility" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Create Facility
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Provision a new workspace from Facility Manager,
                            then continue onboarding through the tenant flow.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/facility-manager/facilities">
                            Back to Facilities
                        </Link>
                    </Button>
                </div>

                <div className="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100">
                    Facility Manager will stay signed in as support. After the
                    workspace is created, use impersonation to continue
                    onboarding when needed.
                </div>

                <Form
                    method="post"
                    action="/facility-manager/facilities"
                    className="space-y-6 rounded-2xl border bg-background p-6 shadow-sm"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="facility_level"
                                value={facilityLevel}
                            />
                            <input
                                type="hidden"
                                name="subscription_package_id"
                                value={subscriptionPackageId}
                            />
                            <input
                                type="hidden"
                                name="country_id"
                                value={countryId}
                            />

                            <div className="grid gap-6">
                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="owner_name">
                                            Owner or administrator name
                                        </Label>
                                        <Input
                                            id="owner_name"
                                            name="owner_name"
                                            required
                                            autoFocus
                                            placeholder="e.g. Manoah Ssenyonga"
                                        />
                                        <InputError
                                            message={errors.owner_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            Work email
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autoComplete="email"
                                            placeholder="admin@hospital.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                </div>

                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="workspace_name">
                                            Facility or workspace name
                                        </Label>
                                        <Input
                                            id="workspace_name"
                                            name="workspace_name"
                                            required
                                            placeholder="e.g. Mini Hospital Kampala"
                                        />
                                        <InputError
                                            message={errors.workspace_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="domain">
                                            Workspace domain slug
                                        </Label>
                                        <Input
                                            id="domain"
                                            name="domain"
                                            placeholder="optional-hospital-domain"
                                        />
                                        <InputError message={errors.domain} />
                                    </div>
                                </div>

                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Facility level</Label>
                                        <Select
                                            value={facilityLevel}
                                            onValueChange={setFacilityLevel}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select facility level" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {facilityLevels.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.facility_level}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Country</Label>
                                        <Select
                                            value={countryId || 'none'}
                                            onValueChange={(value) =>
                                                setCountryId(
                                                    value === 'none'
                                                        ? ''
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select country" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">
                                                    Select later
                                                </SelectItem>
                                                {countries.map((country) => (
                                                    <SelectItem
                                                        key={country.id}
                                                        value={country.id}
                                                    >
                                                        {country.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.country_id}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Temporary password
                                        </Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Create a temporary password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Confirm temporary password
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Confirm temporary password"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-3 rounded-2xl border p-4">
                                    <div>
                                        <p className="font-medium">
                                            Choose a starting package
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            The selected package will be used to
                                            create the first subscription record
                                            for this facility.
                                        </p>
                                    </div>

                                    <div className="grid gap-3">
                                        {subscriptionPackages.map((item) => (
                                            <button
                                                key={item.id}
                                                type="button"
                                                onClick={() =>
                                                    setSubscriptionPackageId(
                                                        item.id,
                                                    )
                                                }
                                                className={`rounded-xl border p-4 text-left transition ${
                                                    subscriptionPackageId ===
                                                    item.id
                                                        ? 'border-zinc-900 bg-zinc-900 text-zinc-50'
                                                        : 'border-border bg-background hover:border-zinc-400'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between gap-4">
                                                    <div>
                                                        <p className="font-medium">
                                                            {item.name}
                                                        </p>
                                                        <p
                                                            className={`text-sm ${
                                                                subscriptionPackageId ===
                                                                item.id
                                                                    ? 'text-zinc-300'
                                                                    : 'text-muted-foreground'
                                                            }`}
                                                        >
                                                            {item.users}{' '}
                                                            included users
                                                        </p>
                                                    </div>
                                                    <p className="text-sm font-medium">
                                                        {item.price}
                                                    </p>
                                                </div>
                                            </button>
                                        ))}
                                    </div>

                                    <InputError
                                        message={errors.subscription_package_id}
                                    />

                                    {selectedPackage ? (
                                        <p className="text-xs text-muted-foreground">
                                            Selected package:{' '}
                                            {selectedPackage.name}
                                        </p>
                                    ) : null}
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Creating Facility...'
                                        : 'Create Facility'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
