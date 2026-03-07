import RoleController from '@/actions/App/Http/Controllers/RoleController';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

interface Role {
    id: string;
    name: string;
    permissions: any[];
}

interface Props {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: RoleController.index.url() },
];

export default function RoleIndex({ roles }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles & Permissions" />
            <div className="mt-4 mb-4 flex items-center justify-between gap-2 px-4">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 italic">
                            Roles & Permissions
                        </h2>
                        <p className="text-muted-foreground">
                            Manage system roles and their assigned permissions.
                        </p>
                    </div>
                    <Button asChild className="shadow-sm border border-zinc-200 dark:border-zinc-800">
                        <Link href={RoleController.create.url()} className="gap-2">
                            <Plus className="h-4 w-4" />
                            <span>Create Role</span>
                        </Link>
                    </Button>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead>
                                <tr className="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                    <th className="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider text-xs">Role Name</th>
                                    <th className="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-100 text-center uppercase tracking-wider text-xs">Permissions</th>
                                    <th className="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-100 text-right uppercase tracking-wider text-xs">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100 dark:divide-zinc-800">
                                {roles.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-12 text-center text-zinc-500 italic">
                                            No roles found.
                                        </td>
                                    </tr>
                                )}
                                {roles.map((role) => (
                                    <tr key={role.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors group">
                                        <td className="px-6 py-4 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {role.name}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 uppercase tracking-tight">
                                                {role.permissions.length} permissions
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    asChild 
                                                    className="h-8 px-3 text-xs cursor-pointer"
                                                >
                                                    <Link href={RoleController.edit.url({ role })}>
                                                        Edit
                                                    </Link>
                                                </Button>
                                                
                                                {role.name !== 'super_admin' && (
                                                    <DeleteConfirmationModal
                                                        title="Delete Role"
                                                        description={`Are you sure you want to delete the role "${role.name}"? This action cannot be undone.`}
                                                        action={RoleController.destroy.form({ role })}
                                                        onSuccess={() => toast.success(`Role "${role.name}" deleted successfully.`)}
                                                        trigger={
                                                            <Button 
                                                                variant="destructive" 
                                                                size="sm" 
                                                                className="h-8 px-3 text-xs cursor-pointer"
                                                            >
                                                                Delete
                                                            </Button>
                                                        }
                                                    />
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
