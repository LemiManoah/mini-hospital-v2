import { Link, Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface Role {
    id: string;
    name: string;
    permissions: any[];
}

interface Props {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: '/roles' },
];

export default function RoleIndex({ roles }: Props) {
    const handleDelete = (id: string) => {
        if (confirm('Are you sure you want to delete this role?')) {
            router.delete(`/roles/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles & Permissions" />
            <div className="p-4 sm:p-8 max-w-7xl mx-auto">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Roles & Permissions</h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage system roles and their permissions.</p>
                    </div>
                    <Button asChild>
                        <Link href="/roles/create">Create Role</Link>
                    </Button>
                </div>

                <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 text-gray-900 dark:text-gray-100 overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-3">Role Name</th>
                                    <th className="px-6 py-3">Permissions Count</th>
                                    <th className="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {roles.map((role) => (
                                    <tr key={role.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td className="px-6 py-4 font-medium">{role.name}</td>
                                        <td className="px-6 py-4">{role.permissions.length}</td>
                                        <td className="px-6 py-4 flex gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/roles/${role.id}/edit`}>Edit</Link>
                                            </Button>
                                            {role.name !== 'super_admin' && (
                                                <Button variant="destructive" size="sm" onClick={() => handleDelete(role.id)}>
                                                    Delete
                                                </Button>
                                            )}
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
