import { type ShowcaseShot } from './types';

export function WelcomeShowcaseSection({
    showcaseShots,
}: {
    showcaseShots: ShowcaseShot[];
}) {
    const [featuredShot, ...secondaryShots] = showcaseShots;

    if (!featuredShot) {
        return null;
    }

    return (
        <section className="border-b border-stone-200 py-20 dark:border-white/6 md:py-24">
            <div className="mx-auto max-w-7xl px-6 md:px-10">
                <div className="mb-16 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div className="max-w-3xl">
                        <span className="mb-5 block font-[Manrope] text-[10px] font-bold tracking-[0.38em] text-[#4A6FA5] uppercase dark:text-[#7ea2d6]">
                            Product Views
                        </span>
                        <h2 className="font-[Manrope] text-4xl leading-none font-extrabold tracking-[-0.07em] text-stone-950 dark:text-stone-100 md:text-6xl">
                            Real screens from the
                            <br />
                            working product.
                        </h2>
                    </div>
                    <p className="max-w-md text-sm leading-7 text-stone-600 dark:text-stone-400">
                        The landing page now shows actual application surfaces so visitors can see the tone of the dashboard, doctor queue, and laboratory workflow before they enter the product.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <article className="overflow-hidden border border-stone-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] dark:border-white/8 dark:bg-[#131313] dark:shadow-none">
                        <div className="border-b border-stone-200 px-5 py-4 dark:border-white/8">
                            <p className="text-[10px] font-bold tracking-[0.32em] text-stone-500 uppercase dark:text-stone-500">
                                {featuredShot.eyebrow}
                            </p>
                            <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 className="font-[Manrope] text-2xl font-bold tracking-[-0.05em] text-stone-950 dark:text-stone-100">
                                        {featuredShot.title}
                                    </h3>
                                    <p className="mt-2 max-w-2xl text-sm leading-7 text-stone-600 dark:text-stone-400">
                                        {featuredShot.description}
                                    </p>
                                </div>
                                <span className="inline-flex w-fit items-center rounded-full border border-[#4A6FA5]/25 bg-[#4A6FA5]/8 px-3 py-1 text-[10px] font-bold tracking-[0.24em] text-[#4A6FA5] uppercase dark:border-[#7ea2d6]/35 dark:bg-[#7ea2d6]/10 dark:text-[#9bb5da]">
                                    {featuredShot.accent}
                                </span>
                            </div>
                        </div>

                        <div className="bg-stone-100 p-3 dark:bg-[#0b0b0b] sm:p-4">
                            <img
                                src={featuredShot.image}
                                alt={featuredShot.title}
                                className="h-full w-full rounded-sm border border-stone-200 object-cover object-top shadow-[0_16px_40px_rgba(15,23,42,0.08)] dark:border-white/8 dark:shadow-none"
                                loading="lazy"
                            />
                        </div>
                    </article>

                    <div className="grid gap-6">
                        {secondaryShots.map((shot) => (
                            <article
                                key={shot.title}
                                className="overflow-hidden border border-stone-200 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.08)] dark:border-white/8 dark:bg-[#131313] dark:shadow-none"
                            >
                                <div className="border-b border-stone-200 px-5 py-4 dark:border-white/8">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-[10px] font-bold tracking-[0.32em] text-stone-500 uppercase dark:text-stone-500">
                                                {shot.eyebrow}
                                            </p>
                                            <h3 className="mt-3 font-[Manrope] text-xl font-bold tracking-[-0.05em] text-stone-950 dark:text-stone-100">
                                                {shot.title}
                                            </h3>
                                        </div>
                                        <span className="inline-flex shrink-0 items-center rounded-full border border-[#4A6FA5]/25 bg-[#4A6FA5]/8 px-3 py-1 text-[10px] font-bold tracking-[0.24em] text-[#4A6FA5] uppercase dark:border-[#7ea2d6]/35 dark:bg-[#7ea2d6]/10 dark:text-[#9bb5da]">
                                            {shot.accent}
                                        </span>
                                    </div>
                                    <p className="mt-3 text-sm leading-7 text-stone-600 dark:text-stone-400">
                                        {shot.description}
                                    </p>
                                </div>

                                <div className="bg-stone-100 p-3 dark:bg-[#0b0b0b]">
                                    <img
                                        src={shot.image}
                                        alt={shot.title}
                                        className="h-full w-full rounded-sm border border-stone-200 object-cover object-top shadow-[0_14px_35px_rgba(15,23,42,0.08)] dark:border-white/8 dark:shadow-none"
                                        loading="lazy"
                                    />
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
