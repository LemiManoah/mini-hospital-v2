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
            className="border-y border-white/6 bg-[#080808] py-24 md:py-32"
        >
            <div className="mx-auto max-w-7xl px-6 md:px-10">
                <div className="mb-20 flex flex-col gap-10 md:flex-row md:items-end md:justify-between">
                    <div className="max-w-2xl">
                        <span className="mb-6 block font-[Manrope] text-[10px] font-bold tracking-[0.38em] text-[#7ea2d6] uppercase">
                            Why teams stay with it
                        </span>
                        <h2 className="font-[Manrope] text-4xl font-extrabold tracking-[-0.07em] text-stone-100 md:text-6xl">
                            Interfaces that
                            <br />
                            reduce operational noise.
                        </h2>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        {operationalStats.map((stat) => (
                            <div key={stat.label} className="border border-white/8 bg-[#111212] px-5 py-4">
                                <p className="text-[10px] font-bold tracking-[0.32em] text-stone-600 uppercase">
                                    {stat.label}
                                </p>
                                <p className="mt-3 font-[Manrope] text-lg font-semibold text-stone-100">
                                    {stat.value}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="grid gap-12 md:grid-cols-2">
                    {testimonials.map((item) => (
                        <div key={item.name} className="flex flex-col gap-8">
                            <span className="font-[Manrope] text-6xl leading-none text-[#4A6FA5]/45">
                                "
                            </span>
                            <p className="max-w-xl text-2xl leading-tight tracking-[-0.04em] text-stone-100 italic">
                                {item.quote}
                            </p>
                            <div>
                                <h4 className="font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-stone-100 uppercase">
                                    {item.name}
                                </h4>
                                <p className="mt-1 text-[9px] tracking-[0.28em] text-stone-600 uppercase">
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
