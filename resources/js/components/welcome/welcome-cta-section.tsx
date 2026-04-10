export function WelcomeCtaSection() {
    return (
        <section className="px-6 py-24 md:px-10 md:py-32">
            <div className="relative mx-auto max-w-7xl overflow-hidden border border-stone-200 bg-white p-10 shadow-[0_24px_80px_rgba(15,23,42,0.08)] md:p-16 dark:border-white/8 dark:bg-[#161717] dark:shadow-none">
                <div className="pointer-events-none absolute top-0 right-0 h-80 w-80 bg-[#4A6FA5]/10 blur-[120px]" />
                <div className="relative grid gap-12 md:grid-cols-[1.15fr_0.85fr] md:items-center">
                    <div>
                        <h2 className="font-[Manrope] text-4xl font-extrabold tracking-[-0.07em] text-stone-950 md:text-6xl dark:text-stone-100">
                            Ready to modernize
                            <br />
                            the hospital floor?
                        </h2>
                        <p className="mt-6 max-w-md text-sm leading-7 text-stone-600 dark:text-stone-400">
                            Start with QrooEMR, then extend into the exact
                            workflows your facility needs without losing
                            coherence across the system.
                        </p>
                    </div>

                    <div className="w-full max-w-md">
                        <div className="flex flex-col gap-6">
                            <div>
                                <label className="mb-2 block text-[9px] font-bold tracking-[0.3em] text-stone-500 uppercase dark:text-stone-600">
                                    Identification
                                </label>
                                <input
                                    type="email"
                                    placeholder="Your work email address"
                                    className="w-full border-b border-stone-300 bg-transparent px-0 py-3 text-sm text-stone-950 transition outline-none placeholder:text-stone-400 focus:border-[#4A6FA5] dark:border-white/12 dark:text-stone-100 dark:placeholder:text-stone-600"
                                />
                            </div>
                            <a
                                href="mailto:sales@qroo.rw?subject=QrooEMR%20Implementation%20Inquiry"
                                className="inline-flex items-center justify-center bg-[#4A6FA5] px-6 py-4 font-[Manrope] text-[10px] font-bold tracking-[0.32em] text-white uppercase transition hover:bg-[#5f84bb]"
                            >
                                Begin Implementation
                            </a>
                            <p className="text-center text-[8px] font-semibold tracking-[0.38em] text-stone-500 uppercase dark:text-stone-600">
                                Structured rollout | Hosted or managed
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
