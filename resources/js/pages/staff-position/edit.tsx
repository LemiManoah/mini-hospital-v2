import StaffPositionController from '@/actions/App/Http/Controllers/StaffPositionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type StaffPositionEditPageProps } from '@/types/staff-position';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Save, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function StaffPositionEdit({
    staff_position,
}: StaffPositionEditPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Staff Positions', href: StaffPositionController.index.url() },
        {
            title: `Edit ${staff_position.name}`,
            href: StaffPositionController.edit.url({
                staff_position: staff_position.id,
            }),
        },
    ];

    const [isActive, setIsActive] = useState(staff_position.is_active);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Staff Position: ${staff_position.name}`} />

            <div className="mt-4 mb-4 flex flex-col items-start justify-between gap-4 px-4 sm:flex-row sm:items-center">
                <div className="flex w-full flex-col gap-1">
                    <h2 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <ShieldCheck className="h-6 w-6 text-indigo-500" />
                        Edit Staff Position
                    </h2>
                    <p className="text-muted-foreground">
                        Modify the staff position details.
                    </p>
                </div>
            </div>

            <div className="m-2 overflow-hidden rounded border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <Form
                    {...StaffPositionController.update.form({
                        staff_position: staff_position.id,
                    })}
                    onSuccess={() =>
                        toast.success('Staff position updated successfully.')
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
                                        defaultValue={staff_position.name}
                                        placeholder="e.g. Senior Medical Officer"
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
                                        defaultValue={
                                            staff_position.description ?? ''
                                        }
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
                                        <Save className="mr-2 h-4 w-4" />
                                    )}
                                    Save Changes
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
