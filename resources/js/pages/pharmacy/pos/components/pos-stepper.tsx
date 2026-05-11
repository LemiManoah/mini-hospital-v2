import { saleSteps } from './types';

export function PosStepper({ currentStep }: { currentStep: number }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white px-4 py-5 shadow-sm md:px-6 dark:border-slate-800 dark:bg-slate-950/40">
            <ol className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                {saleSteps.map((step, index) => {
                    const isComplete = index < currentStep;
                    const isCurrent = index === currentStep;

                    return (
                        <li
                            key={step.title}
                            className="flex min-w-0 flex-1 items-center gap-3"
                        >
                            <div
                                className={[
                                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-semibold',
                                    isComplete
                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                        : isCurrent
                                          ? 'border-sky-600 bg-sky-600 text-white'
                                          : 'border-slate-300 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-400',
                                ].join(' ')}
                            >
                                <step.icon className="h-4 w-4" />
                            </div>

                            <div className="min-w-0">
                                <p
                                    className={[
                                        'text-sm font-medium',
                                        isComplete || isCurrent
                                            ? 'text-slate-900 dark:text-slate-100'
                                            : 'text-slate-500 dark:text-slate-400',
                                    ].join(' ')}
                                >
                                    {step.title}
                                </p>
                            </div>

                            {index < saleSteps.length - 1 && (
                                <div
                                    className={[
                                        'hidden h-px flex-1 md:block',
                                        index < currentStep
                                            ? 'bg-emerald-500'
                                            : 'bg-slate-200 dark:bg-slate-800',
                                    ].join(' ')}
                                />
                            )}
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
