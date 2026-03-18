import { Badge } from '@/components/ui/badge';
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
    ArrowRight,
    FlaskConical,
    LayoutGrid,
    Pill,
    Settings,
    Users,
    type LucideIcon,
} from 'lucide-react';

interface Module {
    name: string;
    description: string;
    icon: LucideIcon;
    permission: string;
    href: string;
    category: 'Clinical' | 'Operations' | 'Administration';
}

const modules: Module[] = [
    {
        name: 'OutPatient (OPD)',
        description:
            'Patient registration, triage, and outpatient visit management.',
        icon: Users,
        permission: 'visits.view',
        href: dashboard().url,
        category: 'Clinical',
    },
    {
        name: 'Doctors Module',
        description:
            'Clinician workbench for consultations, prescriptions and orders.',
        icon: Activity,
        permission: 'visits.view',
        href: '/doctors/consultations',
        category: 'Clinical',
    },
    {
        name: 'Laboratory',
        description: 'Lab test requests, specimen tracking and results.',
        icon: FlaskConical,
        permission: 'dashboard.view',
        href: dashboard().url,
        category: 'Clinical',
    },
    {
        name: 'Dashboard',
        description: 'Hospital overview, statistics and real-time activities.',
        icon: LayoutGrid,
        permission: 'dashboard.view',
        href: dashboard().url,
        category: 'Operations',
    },
    {
        name: 'Pharmacy',
        description: 'Inventory management and medication dispensing.',
        icon: Pill,
        permission: 'dashboard.view',
        href: dashboard().url,
        category: 'Operations',
    },
    {
        name: 'Administration',
        description: 'Configure hospital systems, staff, and roles.',
        icon: Settings,
        permission: 'users.view',
        href: dashboard().url,
        category: 'Administration',
    },
];

const categories: Array<Module['category']> = [
    'Clinical',
    'Operations',
    'Administration',
];

export default function Modules() {
    const { auth } = usePage<SharedData>().props;
    const currentSubscription = auth.user?.tenant?.current_subscription as
        | {
              status: string;
              subscription_package?: {
                  name: string;
                  price: string;
              } | null;
              trial_ends_at?: string | null;
          }
        | undefined;

    const hasPermission = (permission: string) => {
        if (!auth.user) return false;

        if (
            auth.user.roles?.includes('super_admin') ||
            auth.user.roles?.includes('admin')
        ) {
            return true;
        }

        return !!auth.user.can?.[permission];
    };

    const formatDate = (value?: string | null) =>
        value
            ? new Date(value).toLocaleDateString('en-UG', {
                  year: 'numeric',
                  month: 'short',
                  day: 'numeric',
              })
            : 'Not set';

    const categoryDescription = (category: Module['category']) => {
        if (category === 'Clinical') {
            return 'Patient-facing workflows for care delivery and clinical decisions.';
        }

        if (category === 'Operations') {
            return 'Tools that help the workspace run day to day.';
        }

        return 'Setup, permissions, and administrative controls.';
    };

    return (
        <AuthLayout
            title="Hospital Modules"
            description="Choose where you want to continue working."
            contentClassName="max-w-6xl"
        >
            <Head title="Select Module" />

            <div className="space-y-6">
                {currentSubscription ? (
                    <Card className="rounded-3xl border-zinc-200">
                        <CardHeader>
                            <CardTitle>Subscription status</CardTitle>
                            <CardDescription>
                                {currentSubscription.subscription_package
                                    ?.name ?? 'Current package'}{' '}
                                is currently{' '}
                                {currentSubscription.status.replaceAll(
                                    '_',
                                    ' ',
                                )}
                                .
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-2xl bg-muted/50 p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Package
                                    </p>
                                    <p className="mt-2 font-medium">
                                        {currentSubscription
                                            .subscription_package?.name ??
                                            'Not set'}
                                    </p>
                                </div>
                                <div className="rounded-2xl bg-muted/50 p-4">
                                    <p className="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                        Trial ends
                                    </p>
                                    <p className="mt-2 font-medium">
                                        {formatDate(
                                            currentSubscription.trial_ends_at,
                                        )}
                                    </p>
                                </div>
                            </div>
                            <Button asChild variant="outline">
                                <Link href="/subscription/activate">
                                    Open subscription activation
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : null}

                {categories.map((category) => {
                    const categoryModules = modules.filter(
                        (module) => module.category === category,
                    );

                    return (
                        <section key={category} className="space-y-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight">
                                        {category}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {categoryDescription(category)}
                                    </p>
                                </div>
                                <Badge variant="outline">
                                    {categoryModules.length} modules
                                </Badge>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {categoryModules.map((module) => {
                                    const canAccess = hasPermission(
                                        module.permission,
                                    );

                                    return (
                                        <Card
                                            key={module.name}
                                            className="flex min-h-56 flex-col rounded-3xl"
                                        >
                                            <CardHeader className="space-y-4">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                        <module.icon className="h-6 w-6" />
                                                    </div>
                                                    <Badge
                                                        variant={
                                                            canAccess
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {canAccess
                                                            ? 'Ready'
                                                            : 'No access'}
                                                    </Badge>
                                                </div>
                                                <div className="space-y-2">
                                                    <CardTitle className="text-xl">
                                                        {module.name}
                                                    </CardTitle>
                                                    <CardDescription className="text-sm leading-6">
                                                        {module.description}
                                                    </CardDescription>
                                                </div>
                                            </CardHeader>
                                            <CardContent className="mt-auto pt-0">
                                                {canAccess ? (
                                                    <Button
                                                        asChild
                                                        className="w-full justify-between rounded-xl"
                                                    >
                                                        <Link
                                                            href={module.href}
                                                        >
                                                            Launch module
                                                            <ArrowRight className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        disabled
                                                        variant="outline"
                                                        className="w-full rounded-xl"
                                                    >
                                                        Access denied
                                                    </Button>
                                                )}
                                            </CardContent>
                                        </Card>
                                    );
                                })}
                            </div>
                        </section>
                    );
                })}
            </div>
        </AuthLayout>
    );
}
