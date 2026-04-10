import { type OperationalStat, type Testimonial } from './types';

export function WelcomeEvidenceSection({
    operationalStats,
    testimonials,
}: {
    operationalStats: OperationalStat[];
    testimonials: Testimonial[];
}) {
    return (
        <section
            id="evidence"
            className="border-y border-stone-200 bg-stone-100 py-24 md:py-32 dark:border-white/6 dark:bg-[#080808]"
        >
            <div className="mx-auto max-w-7xl px-6 md:px-10">
                <div className="mb-20 flex flex-col gap-10 md:flex-row md:items-end md:justify-between">
                    <div className="max-w-2xl">
                        <span className="mb-6 block font-[Manrope] text-[10px] font-bold tracking-[0.38em] text-[#4A6FA5] uppercase dark:text-[#7ea2d6]">
                            Why teams stay with it
                        </span>
                        <h2 className="font-[Manrope] text-4xl font-extrabold tracking-[-0.07em] text-stone-950 md:text-6xl dark:text-stone-100">
                            Interfaces that
                            <br />
                            reduce operational noise.
                        </h2>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        {operationalStats.map((stat) => (
                            <div
                                key={stat.label}
                                className="border border-stone-200 bg-white px-5 py-4 dark:border-white/8 dark:bg-[#111212]"
                            >
                                <p className="text-[10px] font-bold tracking-[0.32em] text-stone-500 uppercase dark:text-stone-600">
                                    {stat.label}
                                </p>
                                <p className="mt-3 font-[Manrope] text-lg font-semibold text-stone-950 dark:text-stone-100">
                                    {stat.value}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="grid gap-12 md:grid-cols-2">
                    {testimonials.map((item) => (
                        <div key={item.name} className="flex flex-col gap-8">
                            <span className="font-[Manrope] text-6xl leading-none text-[#4A6FA5]/45 dark:text-[#4A6FA5]/45">
                                "
                            </span>
                            <p className="max-w-xl text-2xl leading-tight tracking-[-0.04em] text-stone-900 italic dark:text-stone-100">
                                {item.quote}
                            </p>
                            <div>
                                <h4 className="font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-stone-950 uppercase dark:text-stone-100">
                                    {item.name}
                                </h4>
                                <p className="mt-1 text-[9px] tracking-[0.28em] text-stone-500 uppercase dark:text-stone-600">
                                    {item.role}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
