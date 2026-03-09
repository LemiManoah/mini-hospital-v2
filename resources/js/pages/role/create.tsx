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
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);

    const handlePermissionToggle = (permissionName: string, checked: boolean) => {
        if (checked) {
            setSelectedPermissions(prev => [...prev, permissionName]);
        } else {
            setSelectedPermissions(prev => prev.filter(p => p !== permissionName));
        }
    };

    const handleGroupToggle = (groupPerms: any[], checked: boolean) => {
        const groupPermNames = groupPerms.map(p => p.name);
        if (checked) {
            setSelectedPermissions(prev => [...new Set([...prev, ...groupPermNames])]);
        } else {
            setSelectedPermissions(prev => prev.filter(p => !groupPermNames.includes(p)));
        }
    };

    const isGroupFullySelected = (groupPerms: any[]) => {
        return groupPerms.length > 0 && groupPerms.every(p => selectedPermissions.includes(p.name));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Role" />
            <div className="p-4 sm:p-8 max-w-5xl mx-auto space-y-6">
                <div className="flex flex-col gap-1">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 italic">
                        Create New Role
                    </h2>
                    <p className="text-muted-foreground">
                        Define a new role and assign specific permissions to it.
                    </p>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                    <Form
                        {...RoleController.store.form()}
                        onSuccess={() => toast.success('Role created successfully.')}
                        className="p-6 space-y-8"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-6">
                                    <div className="grid gap-2 max-w-md">
                                        <Label htmlFor="name" className="text-sm font-semibold">
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
                                        <div className="flex items-center gap-2 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                                            <Shield className="h-5 w-5 text-indigo-500" />
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                Permissions
                                            </h3>
                                        </div>
                                        
                                        {errors.permissions && (
                                            <InputError message={errors.permissions} />
                                        )}

                                        {/* Hidden inputs to send permissions array */}
                                        {selectedPermissions.map(permission => (
                                            <input 
                                                key={permission} 
                                                type="hidden" 
                                                name="permissions[]" 
                                                value={permission} 
                                            />
                                        ))}

                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 auto-rows-fr">
                                            {Object.entries(permissionGroups).map(([group, perms]) => (
                                                <div 
                                                    key={group} 
                                                    className="flex flex-col border border-zinc-200 dark:border-zinc-800 rounded-lg bg-zinc-50/30 dark:bg-zinc-900/30"
                                                >
                                                    <div className="p-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                                                        <Label 
                                                            htmlFor={`group-${group}`} 
                                                            className="font-bold capitalize text-sm cursor-pointer flex items-center gap-2"
                                                        >
                                                            {formatIdentifierLabel(group)}
                                                        </Label>
                                                        <Checkbox 
                                                            id={`group-${group}`}
                                                            checked={isGroupFullySelected(perms)}
                                                            onCheckedChange={(c) => handleGroupToggle(perms, c as boolean)}
                                                        />
                                                    </div>
                                                    <div className="p-3 space-y-2 flex-grow">
                                                        {perms.map(permission => (
                                                            <div key={permission.id} className="flex items-center justify-between gap-2 px-1">
                                                                <Label 
                                                                    htmlFor={`perm-${permission.id}`} 
                                                                    className="font-normal cursor-pointer text-xs text-zinc-600 dark:text-zinc-400"
                                                                >
                                                                    {formatPermissionLabel(permission.name)}
                                                                </Label>
                                                                <Checkbox 
                                                                    id={`perm-${permission.id}`}
                                                                    checked={selectedPermissions.includes(permission.name)}
                                                                    onCheckedChange={(c) => handlePermissionToggle(permission.name, c as boolean)}
                                                                />
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-end gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                                    <Button variant="ghost" type="button" asChild>
                                        <Link href={RoleController.index.url()}>Cancel</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing} className="min-w-[120px]">
                                        {processing ? (
                                            <LoaderCircle className="h-4 w-4 animate-spin mr-2" />
                                        ) : (
                                            <ShieldCheck className="h-4 w-4 mr-2" />
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
