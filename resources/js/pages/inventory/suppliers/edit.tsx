import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type SupplierEditPageProps } from '@/types/supplier';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { toast } from 'sonner';

export default function SupplierEdit({ supplier }: SupplierEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventory', href: '/inventory/dashboard' },
        { title: 'Suppliers', href: '/suppliers' },
        {
            title: `Edit: ${supplier.name}`,
            href: `/suppliers/${supplier.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Supplier: ${supplier.name}`} />

            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Supplier
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Update supplier information.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/suppliers">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action={`/suppliers/${supplier.id}`}
                        method="put"
                        onSuccess={() =>
                            toast.success('Supplier updated successfully.')
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Supplier Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={supplier.name}
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="contact_person">
                                            Contact Person
                                        </Label>
                                        <Input
                                            id="contact_person"
                                            name="contact_person"
                                            defaultValue={
                                                supplier.contact_person ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.contact_person}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={supplier.email ?? ''}
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            defaultValue={supplier.phone ?? ''}
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tax_id">Tax ID</Label>
                                        <Input
                                            id="tax_id"
                                            name="tax_id"
                                            defaultValue={supplier.tax_id ?? ''}
                                        />
                                        <InputError message={errors.tax_id} />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="address">Address</Label>
                                        <Textarea
                                            id="address"
                                            name="address"
                                            rows={2}
                                            defaultValue={
                                                supplier.address ?? ''
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
                                            defaultValue={supplier.notes ?? ''}
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked={supplier.is_active}
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active supplier
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
                                        <Link href="/suppliers">Cancel</Link>
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
