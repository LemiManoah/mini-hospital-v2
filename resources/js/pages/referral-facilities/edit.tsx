import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReferralFacilityEditPageProps } from '@/types/referral-facility';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { toast } from 'sonner';

export default function ReferralFacilityEdit({
    referralFacility,
}: ReferralFacilityEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administration', href: '/administration/master-data' },
        { title: 'Referral Facilities', href: '/referral-facilities' },
        {
            title: `Edit: ${referralFacility.name}`,
            href: `/referral-facilities/${referralFacility.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Referral Facility: ${referralFacility.name}`} />

            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Referral Facility
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update referral destination details.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/referral-facilities">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/referral-facilities/${referralFacility.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success(
                                'Referral facility updated successfully.',
                            )
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Facility Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={referralFacility.name}
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="facility_type">
                                            Facility Type
                                        </Label>
                                        <Input
                                            id="facility_type"
                                            name="facility_type"
                                            defaultValue={
                                                referralFacility.facility_type ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={errors.facility_type}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="contact_person">
                                            Contact Person
                                        </Label>
                                        <Input
                                            id="contact_person"
                                            name="contact_person"
                                            defaultValue={
                                                referralFacility.contact_person ??
                                                ''
                                            }
                                        />
                                        <InputError
                                            message={errors.contact_person}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            defaultValue={
                                                referralFacility.phone ?? ''
                                            }
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={
                                                referralFacility.email ?? ''
                                            }
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="address">Address</Label>
                                        <Textarea
                                            id="address"
                                            name="address"
                                            rows={2}
                                            defaultValue={
                                                referralFacility.address ?? ''
                                            }
                                        />
                                        <InputError message={errors.address} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            rows={3}
                                            defaultValue={
                                                referralFacility.notes ?? ''
                                            }
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={
                                                referralFacility.is_active
                                            }
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active referral facility
                                        </Label>
                                    </div>
                                </div>

                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <Save className="mr-2 h-4 w-4" />
                                        )}
                                        Save Changes
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/referral-facilities">
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
