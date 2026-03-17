import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
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
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

type PackageOption = {
    id: string;
    name: string;
    users: number;
    price: string;
};

export default function WorkspaceRegister({
    facilityLevels,
    subscriptionPackages,
    countries,
}: {
    facilityLevels: { value: string; label: string }[];
    subscriptionPackages: PackageOption[];
    countries: { id: string; name: string }[];
}) {
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
        <AuthLayout
            title="Create your hospital workspace"
            description="Start your tenant, choose a package, and enter onboarding in one flow."
        >
            <Head title="Create Workspace" />

            <Form
                method="post"
                action="/create-workspace"
                className="space-y-6"
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

                        <div className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900">
                            <div className="flex items-start gap-3">
                                <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0" />
                                <div>
                                    <p className="font-medium">
                                        Slice 1 now provisions a real workspace
                                    </p>
                                    <p className="mt-1 text-emerald-800/80">
                                        We will create your tenant, first admin,
                                        and onboarding entry point immediately
                                        after signup.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6">
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
                                <InputError message={errors.owner_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="workspace_name">
                                    Hospital or workspace name
                                </Label>
                                <Input
                                    id="workspace_name"
                                    name="workspace_name"
                                    required
                                    placeholder="e.g. Mini Hospital Kampala"
                                />
                                <InputError message={errors.workspace_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Work email</Label>
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

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label>Password</Label>
                                    <Input
                                        type="password"
                                        name="password"
                                        required
                                        autoComplete="new-password"
                                        placeholder="Create a password"
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Confirm password</Label>
                                    <Input
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        autoComplete="new-password"
                                        placeholder="Confirm password"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
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
                                            {facilityLevels.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
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
                                                value === 'none' ? '' : value,
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
                                    <InputError message={errors.country_id} />
                                </div>
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
                                <p className="text-xs text-muted-foreground">
                                    Optional for now. This can become part of a
                                    future custom workspace URL.
                                </p>
                                <InputError message={errors.domain} />
                            </div>

                            <div className="space-y-3 rounded-2xl border p-4">
                                <div>
                                    <p className="font-medium">
                                        Choose a starting package
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Package activation and billing
                                        automation will be completed in later
                                        Phase 0 milestones.
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
                                                        {item.users} included
                                                        users
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
                                        Selected package: {selectedPackage.name}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing ? (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            ) : null}
                            Create workspace
                        </Button>
                    </>
                )}
            </Form>

            <p className="text-center text-sm text-muted-foreground">
                Already have a workspace?{' '}
                <TextLink href="/login">Log in instead</TextLink>
            </p>
        </AuthLayout>
    );
}
