import { type SolutionCard } from './types';

export function WelcomeSolutionsSection({
    solutionCards,
}: {
    solutionCards: SolutionCard[];
}) {
    return (
        <section id="solutions" className="mx-auto max-w-7xl px-6 py-24 md:px-10 md:py-32">
            <div className="mb-20 flex flex-col gap-10 md:flex-row md:items-start md:justify-between">
                <div className="max-w-3xl">
                    <span className="mb-6 block font-[Manrope] text-[10px] font-bold tracking-[0.38em] text-[#7ea2d6] uppercase">
                        Services and Product Direction
                    </span>
                    <h2 className="font-[Manrope] text-4xl leading-none font-extrabold tracking-[-0.07em] text-stone-100 md:text-6xl">
                        Architecture for
                        <br />
                        clinical movement.
                    </h2>
                </div>
                <p className="max-w-sm text-sm leading-7 text-stone-400">
                    This surface mirrors the actual product direction: patient flow, orders, laboratory operations, pharmacy, and control layers for administrators.
                </p>
            </div>

            <div className="grid gap-px border border-white/8 bg-white/8 md:grid-cols-2">
                {solutionCards.map((card) => (
                    <div
                        key={card.title}
                        className="group bg-[#0e0e0e] p-10 transition-colors duration-500 hover:bg-[#171818] md:p-12"
                    >
                        <div className="mb-14 flex items-start justify-between">
                            <span className="font-[Manrope] text-3xl font-bold text-[#7ea2d6]">
                                {card.number}
                            </span>
                            <card.icon className="h-8 w-8 text-stone-600 transition-colors group-hover:text-[#7ea2d6]" />
                        </div>
                        <h3 className="font-[Manrope] text-2xl font-bold tracking-[-0.05em] text-stone-100 uppercase">
                            {card.title}
                        </h3>
                        <p className="mt-4 max-w-md text-sm leading-7 text-stone-400">
                            {card.description}
                        </p>
                        <div className="mt-10 inline-flex items-center gap-2 border-b border-[#4A6FA5]/25 pb-1 font-[Manrope] text-[10px] font-bold tracking-[0.3em] text-[#7ea2d6] uppercase transition-all group-hover:border-[#7ea2d6]">
                            {card.cta}
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
