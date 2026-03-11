import StaffPositionController from '@/actions/App/Http/Controllers/StaffPositionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff Positions', href: StaffPositionController.index.url() },
    { title: 'Create Position', href: StaffPositionController.create.url() },
];

export default function StaffPositionCreate() {
    const [isActive, setIsActive] = useState(true);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Staff Position" />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Create New Position
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="h-8"
                        >
                            <Link href={StaffPositionController.index.url()}>
                                Back
                            </Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Define a new staff position for the hospital.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...StaffPositionController.store.form()}
                    onSuccess={() =>
                        toast.success('Staff position created successfully.')
                    }
                    className="space-y-6 p-6"
                >
                    {({ processing, errors }) => (
                        <div className="max-w-2xl space-y-6">
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? '1' : '0'}
                            />

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="name"
                                        className="text-sm font-semibold"
                                    >
                                        Position Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="e.g. Senior Medical Officer"
                                        autoFocus
                                        required
                                    />
                                    <InputError message={errors.name} />
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
                                        placeholder="Brief details about the role..."
                                        className="min-h-[100px]"
                                    />
                                    <InputError message={errors.description} />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                                    <div className="space-y-0.5">
                                        <Label
                                            htmlFor="is_active_toggle"
                                            className="text-sm font-semibold"
                                        >
                                            Active Status
                                        </Label>
                                        <p className="text-sm text-zinc-500">
                                            Enable or disable this position
                                            across the system.
                                        </p>
                                    </div>
                                    <Switch
                                        id="is_active_toggle"
                                        checked={isActive}
                                        onCheckedChange={setIsActive}
                                    />
                                    <InputError message={errors.is_active} />
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
                                    Create Position
                                </Button>
                                <Button variant="ghost" type="button" asChild>
                                    <Link
                                        href={StaffPositionController.index.url()}
                                    >
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
