import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard } from '@/routes';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    FlaskConical,
    LayoutGrid,
    Pill,
    Settings,
    Users,
} from 'lucide-react';

interface Module {
    name: string;
    description: string;
    icon: any;
    permission: string;
    href: string;
}

const modules: Module[] = [
    {
        name: 'Dashboard',
        description: 'Hospital overview, statistics and real-time activities.',
        icon: LayoutGrid,
        permission: 'dashboard.view',
        href: dashboard().url,
    },
    {
        name: 'OutPatient (OPD)',
        description:
            'Patient registration, triage, and outpatient visit management.',
        icon: Users,
        permission: 'visits.view',
        href: dashboard().url, // Leading to dashboard as placeholder
    },
    {
        name: 'Doctors Module',
        description:
            'Clinician workbench for consultations, prescriptions and orders.',
        icon: Activity,
        permission: 'visits.view',
        href: '/doctors/consultations',
    },
    {
        name: 'Pharmacy',
        description: 'Inventory management and medication dispensing.',
        icon: Pill,
        permission: 'dashboard.view', // Placeholder permission
        href: dashboard().url,
    },
    {
        name: 'Laboratory',
        description: 'Lab test requests, specimen tracking and results.',
        icon: FlaskConical,
        permission: 'dashboard.view',
        href: dashboard().url,
    },
    {
        name: 'Administration',
        description: 'Configure hospital systems, staff, and roles.',
        icon: Settings,
        permission: 'users.view',
        href: dashboard().url,
    },
];

export default function Modules() {
    const { auth } = usePage<SharedData>().props;

    const hasPermission = (permission: string) => {
        if (!auth.user) return false;
        // Super admin has all permissions
        if (
            auth.user.roles?.includes('super_admin') ||
            auth.user.roles?.includes('admin')
        ) {
            return true;
        }
        return !!auth.user.can?.[permission];
    };

    return (
        <AuthLayout
            title="Hospital Modules"
            description="Welcome back. Please select a module to continue."
        >
            <Head title="Select Module" />

            <style>{`
                /* Override the max-w-sm in the default AuthLayout for the switcher */
                div.flex.min-h-svh > div.w-full.max-w-sm {
                   max-width: 64rem !important;
                }
            `}</style>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-3 lg:grid-cols-3">
                {modules.map((module) => {
                    const canAccess = hasPermission(module.permission);

                    return (
                        <Card key={module.name} className="flex flex-col">
                            <CardHeader>
                                <div className="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <module.icon className="h-6 w-6" />
                                </div>
                                <CardTitle>{module.name}</CardTitle>
                                <CardDescription>
                                    {module.description}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="mt-auto">
                                {canAccess ? (
                                    <Button asChild className="w-full">
                                        <Link href={module.href}>
                                            Launch {module.name}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        disabled
                                        className="variant-ghost w-full"
                                    >
                                        Access Denied
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </AuthLayout>
    );
}
