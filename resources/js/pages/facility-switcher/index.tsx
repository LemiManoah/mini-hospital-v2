import { Head, router } from '@inertiajs/react';
import { Building2, ChevronRight, Globe, ShieldCheck } from 'lucide-react';

interface Tenant {
    id: string;
    name: string;
    domain: string;
    country?: {
        name: string;
        emoji: string;
    };
    subscription_package?: {
        name: string;
    };
}

interface Props {
    tenants: Tenant[];
}

export default function FacilitySwitcher({ tenants }: Props) {
    return (
        <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-950">
            <Head title="Facility Switcher" />

            <header className="border-b border-neutral-200 bg-white px-4 py-6 dark:border-neutral-800 dark:bg-neutral-900">
                <div className="mx-auto flex max-w-4xl items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary-100 dark:bg-primary-900/30 text-primary-600 rounded-lg p-2">
                            <ShieldCheck className="h-6 w-6" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">
                                Support Dashboard
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Select a facility to manage
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-4xl flex-1 p-4 py-12">
                <div className="grid gap-4 md:grid-cols-2">
                    {tenants.map((tenant) => (
                        <div
                            key={tenant.id}
                            className="group hover:border-primary-500 dark:hover:border-primary-500 relative rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div className="mb-4 flex items-start justify-between">
                                <div className="group-hover:bg-primary-50 dark:group-hover:bg-primary-900/20 rounded-xl bg-neutral-100 p-3 transition-colors dark:bg-neutral-800">
                                    <Building2 className="group-hover:text-primary-600 h-6 w-6 text-neutral-600 dark:text-neutral-400" />
                                </div>
                                <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                    {tenant.subscription_package?.name ||
                                        'Standard'}
                                </span>
                            </div>

                            <h3 className="group-hover:text-primary-600 mb-1 text-lg font-bold text-neutral-900 transition-colors dark:text-neutral-50">
                                {tenant.name}
                            </h3>

                            <div className="mt-4 flex flex-col gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                <div className="flex items-center gap-2">
                                    <Globe className="h-4 w-4" />
                                    <span>
                                        {tenant.domain}.mini-hospital.com
                                    </span>
                                </div>
                                {tenant.country && (
                                    <div className="flex items-center gap-2">
                                        <span>{tenant.country.emoji}</span>
                                        <span>{tenant.country.name}</span>
                                    </div>
                                )}
                            </div>

                            <div className="text-primary-600 mt-6 flex items-center justify-between border-t border-neutral-100 pt-6 font-semibold transition-transform group-hover:translate-x-1 dark:border-neutral-800">
                                <span>Switch to Facility</span>
                                <ChevronRight className="h-5 w-5" />
                            </div>

                            <button
                                onClick={() =>
                                    router.post(
                                        `/facility-switcher/${tenant.id}`,
                                    )
                                }
                                className="absolute inset-0 z-10"
                            >
                                <span className="sr-only">
                                    Access {tenant.name}
                                </span>
                            </button>
                        </div>
                    ))}
                </div>

                {tenants.length === 0 && (
                    <div className="rounded-3xl border-2 border-dashed border-neutral-200 bg-white py-24 text-center dark:border-neutral-800 dark:bg-neutral-900">
                        <Building2 className="mx-auto mb-4 h-12 w-12 text-neutral-300" />
                        <h3 className="text-lg font-medium text-neutral-600 dark:text-neutral-400">
                            No facilities found
                        </h3>
                    </div>
                )}
            </main>
        </div>
    );
}
