import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, PlusCircle } from 'lucide-react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointment Modes', href: '/appointment-modes' },
    { title: 'Create Appointment Mode', href: '/appointment-modes/create' },
];

export default function AppointmentModeCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Appointment Mode" />
            <div className="m-4 max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Appointment Mode
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Define how an appointment is delivered.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/appointment-modes">Back</Link>
                    </Button>
                </div>

                <div className="rounded border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        action="/appointment-modes"
                        method="post"
                        onSuccess={() =>
                            toast.success(
                                'Appointment mode created successfully.',
                            )
                        }
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="flex items-center gap-2 pt-8">
                                        <input
                                            id="is_virtual"
                                            name="is_virtual"
                                            type="checkbox"
                                            value="1"
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_virtual"
                                            className="font-normal"
                                        >
                                            Virtual mode
                                        </Label>
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            rows={4}
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="flex items-center gap-2 md:col-span-2">
                                        <input
                                            id="is_active"
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            defaultChecked
                                            className="h-4 w-4"
                                        />
                                        <Label
                                            htmlFor="is_active"
                                            className="font-normal"
                                        >
                                            Active for booking
                                        </Label>
                                    </div>
                                </div>
                                <div className="flex gap-3 border-t pt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <PlusCircle className="mr-2 h-4 w-4" />
                                        )}
                                        Create Appointment Mode
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href="/appointment-modes">
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
