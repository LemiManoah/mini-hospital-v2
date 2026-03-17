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
import { Checkbox } from '@/components/ui/checkbox';
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
    type OnboardingBranch,
    type OnboardingCountryOption,
    type OnboardingCurrencyOption,
    type OnboardingTenant,
} from '@/types/onboarding';
import { Form } from '@inertiajs/react';
import { LoaderCircle, MapPinned } from 'lucide-react';

type BranchStepProps = {
    tenant: OnboardingTenant;
    branch: OnboardingBranch;
    countries: OnboardingCountryOption[];
    currencies: OnboardingCurrencyOption[];
    filteredAddresses: OnboardingAddressOption[];
    selectedCountryId: string;
    selectedAddressId: string;
    selectedCurrencyId: string;
    hasStore: boolean;
    selectedAddress?: OnboardingAddressOption;
    selectedCurrency?: OnboardingCurrencyOption;
    onCountryChange: (value: string) => void;
    onAddressChange: (value: string) => void;
    onCurrencyChange: (value: string) => void;
    onHasStoreChange: (value: boolean) => void;
};

export function BranchStep({
    tenant,
    branch,
    countries,
    currencies,
    filteredAddresses,
    selectedCountryId,
    selectedAddressId,
    selectedCurrencyId,
    hasStore,
    selectedAddress,
    selectedCurrency,
    onCountryChange,
    onAddressChange,
    onCurrencyChange,
    onHasStoreChange,
}: BranchStepProps) {
    return (
        <Card className="rounded-3xl border-zinc-200 shadow-sm">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <MapPinned className="h-5 w-5" />
                    Primary branch
                </CardTitle>
                <CardDescription>
                    This is the first operational branch for the workspace. Keep
                    it focused and practical.
                </CardDescription>
            </CardHeader>
            <Form
                method="post"
                action="/onboarding/branch"
                className="space-y-0"
            >
                {({ processing, errors }) => (
                    <>
                        <CardContent className="space-y-6">
                            <input
                                type="hidden"
                                name="currency_id"
                                value={selectedCurrencyId}
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
                            <input
                                type="hidden"
                                name="has_store"
                                value={hasStore ? '1' : '0'}
                            />

                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="branch_name">
                                        Branch name
                                    </Label>
                                    <Input
                                        id="branch_name"
                                        name="name"
                                        defaultValue={
                                            branch?.name ??
                                            `${tenant.name} Main Branch`
                                        }
                                        placeholder="Mini Hospital Main Branch"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="branch_code">
                                        Branch code
                                    </Label>
                                    <Input
                                        id="branch_code"
                                        name="branch_code"
                                        defaultValue={
                                            branch?.branch_code ?? 'MAIN'
                                        }
                                        placeholder="MAIN"
                                    />
                                    <InputError message={errors.branch_code} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Billing currency</Label>
                                    <Select
                                        value={selectedCurrencyId}
                                        onValueChange={onCurrencyChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((currency) => (
                                                <SelectItem
                                                    key={currency.id}
                                                    value={currency.id}
                                                >
                                                    {currency.name} (
                                                    {currency.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency_id} />
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

                                <div className="grid gap-2">
                                    <Label htmlFor="branch_email">
                                        Branch email
                                    </Label>
                                    <Input
                                        id="branch_email"
                                        name="email"
                                        type="email"
                                        defaultValue={branch?.email ?? ''}
                                        placeholder="branch@hospital.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="main_contact">
                                        Primary phone
                                    </Label>
                                    <Input
                                        id="main_contact"
                                        name="main_contact"
                                        defaultValue={
                                            branch?.main_contact ?? ''
                                        }
                                        placeholder="+256700000000"
                                    />
                                    <InputError message={errors.main_contact} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="other_contact">
                                        Alternate phone
                                    </Label>
                                    <Input
                                        id="other_contact"
                                        name="other_contact"
                                        defaultValue={
                                            branch?.other_contact ?? ''
                                        }
                                        placeholder="+256701111111"
                                    />
                                    <InputError
                                        message={errors.other_contact}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="branch_address_id">
                                        Branch address
                                    </Label>
                                    <Select
                                        value={selectedAddressId || 'none'}
                                        onValueChange={(value) =>
                                            onAddressChange(
                                                value === 'none' ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="branch_address_id">
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
                            </div>

                            <div className="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                                <p className="text-sm font-medium text-zinc-950">
                                    Branch location
                                </p>
                                <p className="mt-1 text-sm text-zinc-600">
                                    {formatOnboardingAddress(selectedAddress)}
                                </p>
                            </div>

                            <div className="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        id="has_store"
                                        checked={hasStore}
                                        onCheckedChange={(checked) =>
                                            onHasStoreChange(checked === true)
                                        }
                                    />
                                    <div className="space-y-1">
                                        <Label htmlFor="has_store">
                                            This branch has a store or pharmacy
                                            location
                                        </Label>
                                        <p className="text-sm text-zinc-600">
                                            Useful now for branch readiness and
                                            later for inventory rollout.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {selectedCurrency ? (
                                <div className="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                                    Transactions will use{' '}
                                    <span className="font-medium">
                                        {selectedCurrency.name} (
                                        {selectedCurrency.code})
                                    </span>
                                    {selectedCurrency.symbol
                                        ? ` with symbol ${selectedCurrency.symbol}.`
                                        : '.'}
                                </div>
                            ) : null}
                        </CardContent>

                        <CardFooter className="flex flex-col items-stretch gap-3 border-t bg-zinc-50/70 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-zinc-600">
                                Save the branch to unlock department bootstrap.
                            </p>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                ) : null}
                                Save branch
                            </Button>
                        </CardFooter>
                    </>
                )}
            </Form>
        </Card>
    );
}
