import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    formatOnboardingAddress,
    type OnboardingAddressOption,
    type OnboardingCountryOption,
    type OnboardingSelectOption,
    type OnboardingTenant,
} from '@/types/onboarding';
import { Form } from '@inertiajs/react';
import { Building2, LoaderCircle } from 'lucide-react';

type ProfileStepProps = {
    tenant: OnboardingTenant;
    facilityLevels: OnboardingSelectOption[];
    countries: OnboardingCountryOption[];
    filteredAddresses: OnboardingAddressOption[];
    selectedCountryId: string;
    selectedAddressId: string;
    selectedFacilityLevel: string;
    selectedAddress?: OnboardingAddressOption;
    onCountryChange: (value: string) => void;
    onAddressChange: (value: string) => void;
    onFacilityLevelChange: (value: string) => void;
};

export function ProfileStep({
    tenant,
    facilityLevels,
    countries,
    filteredAddresses,
    selectedCountryId,
    selectedAddressId,
    selectedFacilityLevel,
    selectedAddress,
    onCountryChange,
    onAddressChange,
    onFacilityLevelChange,
}: ProfileStepProps) {
    return (
        <Card className="rounded-3xl border-zinc-200 shadow-sm">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Building2 className="h-5 w-5" />
                    Hospital profile
                </CardTitle>
                <CardDescription>
                    Keep this simple for now. You can refine more details after
                    onboarding.
                </CardDescription>
            </CardHeader>
            <Form
                method="patch"
                action="/onboarding/profile"
                className="space-y-0"
            >
                {({ processing, errors }) => (
                    <>
                        <CardContent className="space-y-6">
                            <input
                                type="hidden"
                                name="facility_level"
                                value={selectedFacilityLevel}
                            />
                            <input
                                type="hidden"
                                name="address_id"
                                value={selectedAddressId}
                            />
                            <input
                                type="hidden"
                                name="country_id"
                                value={selectedCountryId}
                            />

                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="tenant_name">
                                        Hospital or workspace name
                                    </Label>
                                    <Input
                                        id="tenant_name"
                                        name="name"
                                        defaultValue={tenant.name}
                                        placeholder="Mini Hospital Kampala"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="domain">
                                        Workspace domain slug
                                    </Label>
                                    <Input
                                        id="domain"
                                        name="domain"
                                        defaultValue={tenant.domain ?? ''}
                                        placeholder="mini-hospital-kla"
                                    />
                                    <InputError message={errors.domain} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Facility level</Label>
                                    <Select
                                        value={selectedFacilityLevel}
                                        onValueChange={onFacilityLevelChange}
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
                                        value={selectedCountryId || 'none'}
                                        onValueChange={(value) =>
                                            onCountryChange(
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

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="address_id">Address</Label>
                                    <Select
                                        value={selectedAddressId || 'none'}
                                        onValueChange={(value) =>
                                            onAddressChange(
                                                value === 'none' ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="address_id">
                                            <SelectValue placeholder="Select address" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Select later
                                            </SelectItem>
                                            {filteredAddresses.map(
                                                (address) => (
                                                    <SelectItem
                                                        key={address.id}
                                                        value={address.id}
                                                    >
                                                        {formatOnboardingAddress(
                                                            address,
                                                        )}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            errors.address_id ??
                                            errors.country_id
                                        }
                                    />
                                </div>

                                <div className="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 md:col-span-2">
                                    <p className="text-sm font-medium text-zinc-950">
                                        Selected location
                                    </p>
                                    <p className="mt-1 text-sm text-zinc-600">
                                        {formatOnboardingAddress(
                                            selectedAddress,
                                        )}
                                    </p>
                                </div>
                            </div>
                        </CardContent>

                        <CardFooter className="flex flex-col items-stretch gap-3 border-t bg-zinc-50/70 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-zinc-600">
                                Save this profile to unlock branch setup.
                            </p>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                ) : null}
                                Save and continue
                            </Button>
                        </CardFooter>
                    </>
                )}
            </Form>
        </Card>
    );
}
