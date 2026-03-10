import { Head, router } from '@inertiajs/react';
import { Building2, ChevronRight, Globe, ShieldCheck } from 'lucide-react';

interface Tenant {
    id: string;
    name: string;
    domain: string;
    country?: {
        country_name?: string;
        emoji?: string;
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
        <div className="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex flex-col">
            <Head title="Facility Switcher" />

            <header className="bg-white dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-800 py-6 px-4">
                <div className="max-w-4xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg text-primary-600">
                            <ShieldCheck className="w-6 h-6" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">Support Dashboard</h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">Select a facility to manage</p>
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1 max-w-4xl mx-auto w-full p-4 py-12">
                <div className="grid gap-4 md:grid-cols-2">
                    {tenants.map((tenant) => (
                        <div 
                            key={tenant.id}
                            className="group relative bg-white dark:bg-neutral-900 p-6 rounded-2xl border border-neutral-200 dark:border-neutral-800 hover:border-primary-500 dark:hover:border-primary-500 transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1"
                        >
                            <div className="flex items-start justify-between mb-4">
                                <div className="p-3 bg-neutral-100 dark:bg-neutral-800 rounded-xl group-hover:bg-primary-50 dark:group-hover:bg-primary-900/20 transition-colors">
                                    <Building2 className="w-6 h-6 text-neutral-600 dark:text-neutral-400 group-hover:text-primary-600" />
                                </div>
                                <span className="text-xs font-medium px-2.5 py-1 rounded-full bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                    {tenant.subscription_package?.name || 'Standard'}
                                </span>
                            </div>

                            <h3 className="text-lg font-bold text-neutral-900 dark:text-neutral-50 mb-1 group-hover:text-primary-600 transition-colors">
                                {tenant.name}
                            </h3>
                            
                            <div className="flex flex-col gap-2 mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                                <div className="flex items-center gap-2">
                                    <Globe className="w-4 h-4" />
                                    <span>{tenant.domain}.mini-hospital.com</span>
                                </div>
                                {tenant.country && (
                                    <div className="flex items-center gap-2">
                                        {tenant.country.emoji && <span>{tenant.country.emoji}</span>}
                                        <span>{tenant.country.country_name ?? 'Unknown Country'}</span>
                                    </div>
                                )}
                            </div>

                            <div className="mt-6 pt-6 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between text-primary-600 font-semibold group-hover:translate-x-1 transition-transform">
                                <span>Switch to Facility</span>
                                <ChevronRight className="w-5 h-5" />
                            </div>

                            <button
                                type="button"
                                onClick={() => router.post(`/facility-switcher/${tenant.id}`)}
                                className="absolute inset-0 z-10"
                            >
                                <span className="sr-only">Access {tenant.name}</span>
                            </button>
                        </div>
                    ))}
                </div>

                {tenants.length === 0 && (
                    <div className="text-center py-24 bg-white dark:bg-neutral-900 rounded-3xl border-2 border-dashed border-neutral-200 dark:border-neutral-800">
                        <Building2 className="w-12 h-12 text-neutral-300 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-neutral-600 dark:text-neutral-400">No facilities found</h3>
                    </div>
                )}
            </main>
        </div>
    );
}
