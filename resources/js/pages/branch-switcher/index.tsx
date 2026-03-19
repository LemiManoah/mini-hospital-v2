import { Head, router } from '@inertiajs/react';
import { usePermissions } from '@/lib/permissions';
import { Building2, Check, ChevronRight, GitBranch } from 'lucide-react';

interface Branch {
    id: string;
    name: string;
    branch_code: string;
    is_main_branch: boolean;
    status: string;
}

interface Props {
    branches: Branch[];
    activeBranchId: string | null;
}

export default function BranchSwitcher({ branches, activeBranchId }: Props) {
    const { hasPermission } = usePermissions();
    const canSwitchBranch = hasPermission('facility_branches.update');

    return (
        <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-950">
            <Head title="Branch Switcher" />

            <header className="border-b border-neutral-200 bg-white px-4 py-6 dark:border-neutral-800 dark:bg-neutral-900">
                <div className="mx-auto flex max-w-4xl items-center gap-3">
                    <div className="text-primary-600 rounded-lg bg-neutral-100 p-2 dark:bg-neutral-800">
                        <GitBranch className="h-6 w-6" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">
                            Select Branch
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Choose the branch context for this session
                        </p>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-4xl flex-1 p-4 py-12">
                <div className="grid gap-4 md:grid-cols-2">
                    {branches.map((branch) => (
                        <div
                            key={branch.id}
                            className="group relative rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div className="mb-4 flex items-start justify-between">
                                <div className="rounded-xl bg-neutral-100 p-3 transition-colors dark:bg-neutral-800">
                                    <Building2 className="h-6 w-6 text-neutral-600 dark:text-neutral-400" />
                                </div>
                                <div className="flex items-center gap-2">
                                    {branch.is_main_branch && (
                                        <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            Main
                                        </span>
                                    )}
                                    {activeBranchId === branch.id && (
                                        <span className="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            Active
                                        </span>
                                    )}
                                </div>
                            </div>

                            <h3 className="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-50">
                                {branch.name}
                            </h3>

                            <div className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                                Code: {branch.branch_code}
                            </div>

                            <div className="text-primary-600 mt-6 flex items-center justify-between border-t border-neutral-100 pt-6 font-semibold transition-transform group-hover:translate-x-1 dark:border-neutral-800">
                                <span>
                                    {canSwitchBranch
                                        ? 'Switch to Branch'
                                        : 'View Branch'}
                                </span>
                                {activeBranchId === branch.id ? (
                                    <Check className="h-5 w-5" />
                                ) : canSwitchBranch ? (
                                    <ChevronRight className="h-5 w-5" />
                                ) : null}
                            </div>

                            {canSwitchBranch ? (
                                <button
                                    onClick={() =>
                                        router.post(`/branch-switcher/${branch.id}`)
                                    }
                                    className="absolute inset-0 z-10"
                                    type="button"
                                >
                                    <span className="sr-only">
                                        Access {branch.name}
                                    </span>
                                </button>
                            ) : null}
                        </div>
                    ))}
                </div>

                {branches.length === 0 && (
                    <div className="rounded-3xl border-2 border-dashed border-neutral-200 bg-white py-24 text-center dark:border-neutral-800 dark:bg-neutral-900">
                        <Building2 className="mx-auto mb-4 h-12 w-12 text-neutral-300" />
                        <h3 className="text-lg font-medium text-neutral-600 dark:text-neutral-400">
                            No accessible branches found
                        </h3>
                    </div>
                )}
            </main>
        </div>
    );
}
