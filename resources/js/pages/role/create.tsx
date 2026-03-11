import RoleController from '@/actions/App/Http/Controllers/RoleController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatIdentifierLabel, formatPermissionLabel } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { type RoleCreatePageProps } from '@/types/role';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle, Shield, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: RoleController.index.url() },
    { title: 'Create Role', href: RoleController.create.url() },
];

export default function RoleCreate({ permissionGroups }: RoleCreatePageProps) {
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>(
        [],
    );

    const handlePermissionToggle = (
        permissionName: string,
        checked: boolean,
    ) => {
        if (checked) {
            setSelectedPermissions((prev) => [...prev, permissionName]);
        } else {
            setSelectedPermissions((prev) =>
                prev.filter((p) => p !== permissionName),
            );
        }
    };

    const handleGroupToggle = (groupPerms: any[], checked: boolean) => {
        const groupPermNames = groupPerms.map((p) => p.name);
        if (checked) {
            setSelectedPermissions((prev) => [
                ...new Set([...prev, ...groupPermNames]),
            ]);
        } else {
            setSelectedPermissions((prev) =>
                prev.filter((p) => !groupPermNames.includes(p)),
            );
        }
    };

    const isGroupFullySelected = (groupPerms: any[]) => {
        return (
            groupPerms.length > 0 &&
            groupPerms.every((p) => selectedPermissions.includes(p.name))
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Role" />
            <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-8">
                <div className="flex flex-col gap-1">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 italic dark:text-gray-100">
                            Create New Role
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="h-8"
                        >
                            <Link href={RoleController.index.url()}>Back</Link>
                        </Button>
                    </div>
                    <p className="text-muted-foreground">
                        Define a new role and assign specific permissions to it.
                    </p>
                </div>

                <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <Form
                        {...RoleController.store.form()}
                        onSuccess={() =>
                            toast.success('Role created successfully.')
                        }
                        className="space-y-8 p-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-6">
                                    <div className="grid max-w-md gap-2">
                                        <Label
                                            htmlFor="name"
                                            className="text-sm font-semibold"
                                        >
                                            Role Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="e.g. Manager, Editor"
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="space-y-4">
                                        <div className="flex items-center gap-2 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                                            <Shield className="h-5 w-5 text-indigo-500" />
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                Permissions
                                            </h3>
                                        </div>

                                        {errors.permissions && (
                                            <InputError
                                                message={errors.permissions}
                                            />
                                        )}

                                        {/* Hidden inputs to send permissions array */}
                                        {selectedPermissions.map(
                                            (permission) => (
                                                <input
                                                    key={permission}
                                                    type="hidden"
                                                    name="permissions[]"
                                                    value={permission}
                                                />
                                            ),
                                        )}

                                        <div className="grid auto-rows-fr grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                            {Object.entries(
                                                permissionGroups,
                                            ).map(([group, perms]) => (
                                                <div
                                                    key={group}
                                                    className="flex flex-col rounded-lg border border-zinc-200 bg-zinc-50/30 dark:border-zinc-800 dark:bg-zinc-900/30"
                                                >
                                                    <div className="flex items-center justify-between border-b border-zinc-200 p-3 dark:border-zinc-800">
                                                        <Label
                                                            htmlFor={`group-${group}`}
                                                            className="flex cursor-pointer items-center gap-2 text-sm font-bold capitalize"
                                                        >
                                                            {formatIdentifierLabel(
                                                                group,
                                                            )}
                                                        </Label>
                                                        <Checkbox
                                                            id={`group-${group}`}
                                                            checked={isGroupFullySelected(
                                                                perms,
                                                            )}
                                                            onCheckedChange={(
                                                                c,
                                                            ) =>
                                                                handleGroupToggle(
                                                                    perms,
                                                                    c as boolean,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                    <div className="flex-grow space-y-2 p-3">
                                                        {perms.map(
                                                            (permission) => (
                                                                <div
                                                                    key={
                                                                        permission.id
                                                                    }
                                                                    className="flex items-center justify-between gap-2 px-1"
                                                                >
                                                                    <Label
                                                                        htmlFor={`perm-${permission.id}`}
                                                                        className="cursor-pointer text-xs font-normal text-zinc-600 dark:text-zinc-400"
                                                                    >
                                                                        {formatPermissionLabel(
                                                                            permission.name,
                                                                        )}
                                                                    </Label>
                                                                    <Checkbox
                                                                        id={`perm-${permission.id}`}
                                                                        checked={selectedPermissions.includes(
                                                                            permission.name,
                                                                        )}
                                                                        onCheckedChange={(
                                                                            c,
                                                                        ) =>
                                                                            handlePermissionToggle(
                                                                                permission.name,
                                                                                c as boolean,
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-end gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                    <Button
                                        variant="ghost"
                                        type="button"
                                        asChild
                                    >
                                        <Link href={RoleController.index.url()}>
                                            Cancel
                                        </Link>
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="min-w-[120px]"
                                    >
                                        {processing ? (
                                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                        ) : (
                                            <ShieldCheck className="mr-2 h-4 w-4" />
                                        )}
                                        Create Role
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
