import { FormEvent } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/input-error';

interface Role {
    id: string;
    name: string;
    permissions: any[];
}

interface Props {
    role: Role;
    permissionGroups: Record<string, any[]>;
}

export default function RoleEdit({ role, permissionGroups }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roles', href: '/roles' },
        { title: `Edit ${role.name}`, href: `/roles/${role.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: role.name,
        permissions: role.permissions.map(p => p.name) as string[],
    });

    const handlePermissionToggle = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
        } else {
            setData('permissions', data.permissions.filter(p => p !== permissionName));
        }
    };

    const handleGroupToggle = (groupPerms: any[], checked: boolean) => {
        const groupPermNames = groupPerms.map(p => p.name);
        if (checked) {
            const newPerms = [...new Set([...data.permissions, ...groupPermNames])];
            setData('permissions', newPerms);
        } else {
            setData('permissions', data.permissions.filter(p => !groupPermNames.includes(p)));
        }
    };

    const isGroupFullySelected = (groupPerms: any[]) => {
        return groupPerms.length > 0 && groupPerms.every(p => data.permissions.includes(p.name));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/roles/${role.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Role: ${role.name}`} />
            <div className="p-4 sm:p-8 max-w-5xl mx-auto">
                <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 mb-6">
                        Edit Role: {role.name}
                    </h2>
                    
                    <form onSubmit={submit} className="space-y-8">
                        <div>
                            <Label htmlFor="name">Role Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                className="mt-1 block w-full md:w-1/2"
                                required
                                disabled={role.name === 'super_admin'}
                            />
                            {role.name === 'super_admin' && (
                                <p className="mt-1 text-xs text-amber-600">The super_admin role name cannot be changed.</p>
                            )}
                            <InputError message={errors.name} className="mt-2" />
                            {errors.permissions && <InputError message={errors.permissions} className="mt-2 text-sm text-red-600 dark:text-red-400" />}
                        </div>

                        <div>
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Permissions</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {Object.entries(permissionGroups).map(([group, perms]) => (
                                    <div key={group} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div className="flex items-center space-x-2 mb-4 pb-2 border-b border-gray-100 dark:border-gray-800">
                                            <Checkbox 
                                                id={`group-${group}`}
                                                checked={isGroupFullySelected(perms)}
                                                onCheckedChange={(c) => handleGroupToggle(perms, c as boolean)}
                                            />
                                            <Label htmlFor={`group-${group}`} className="font-bold capitalize text-base cursor-pointer">
                                                {group.replace('_', ' ')}
                                            </Label>
                                        </div>
                                        <div className="space-y-3">
                                            {perms.map(permission => (
                                                <div key={permission.id} className="flex items-center space-x-2 px-2">
                                                    <Checkbox 
                                                        id={`perm-${permission.id}`}
                                                        checked={data.permissions.includes(permission.name)}
                                                        onCheckedChange={(c) => handlePermissionToggle(permission.name, c as boolean)}
                                                    />
                                                    <Label htmlFor={`perm-${permission.id}`} className="font-normal cursor-pointer text-sm">
                                                        {permission.name}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <Button variant="outline" type="button" asChild>
                                <Link href="/roles">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                Update Role
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
