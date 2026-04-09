export function WelcomeProofStrip({ logos }: { logos: string[] }) {
    return (
        <section className="border-b border-stone-200 py-16 dark:border-white/6">
            <div className="mx-auto max-w-7xl px-6 md:px-10">
                <p className="mb-10 text-center font-[Manrope] text-[9px] font-bold tracking-[0.42em] text-stone-500 uppercase dark:text-stone-600">
                    Built around the hospital teams that move care forward
                </p>
                <div className="grid grid-cols-2 gap-8 opacity-45 grayscale md:grid-cols-5">
                    {logos.map((logo) => (
                        <div
                            key={logo}
                            className="flex justify-center font-[Manrope] text-lg font-bold tracking-[-0.04em] text-stone-700 dark:text-stone-200"
                        >
                            {logo}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
