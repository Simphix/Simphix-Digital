<section id="support" class="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
    <div class="max-w-2xl">
        <h2 class="text-3xl font-bold tracking-tight text-[var(--lp-text)] sm:text-4xl">Dedicated support</h2>
        <p class="mt-4 text-lg leading-8 text-[var(--lp-text-soft)]">
            Questions about activation, wallets, or wholesale orders? Our team is here daily.
        </p>
    </div>

    <div class="mt-12 grid gap-8 lg:grid-cols-2 lg:items-start">
        <!-- Contact info cards -->
        <div class="space-y-4">
            <div class="flex gap-4 rounded-xl border p-6" style="border-color: var(--lp-border); background: var(--lp-surface);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-vibrant/10 text-vibrant">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.824-1.806-5.122-4.11-6.928-6.93l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-[var(--lp-text)]">WhatsApp support line</h3>
                    <p class="mt-1 text-sm text-[var(--lp-text-soft)]">Instant chats, rapid activation support, and general troubleshooting.</p>
                    <a href="https://wa.me/2347048932365" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-sm font-semibold text-vibrant hover:underline">Chat on WhatsApp</a>
                </div>
            </div>

            <div class="flex gap-4 rounded-xl border p-6" style="border-color: var(--lp-border); background: var(--lp-surface);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-[var(--lp-text)]">Wholesale &amp; support email</h3>
                    <p class="mt-1 text-sm text-[var(--lp-text-soft)]">Send us formal distribution proposals, bulk requests, and general questions.</p>
                    <a href="mailto:Support@smartsimsub.com" class="mt-2 inline-block text-sm font-semibold text-primary hover:underline">Support@smartsimsub.com</a>
                </div>
            </div>

            <div class="flex gap-4 rounded-xl border p-6" style="border-color: var(--lp-border); background: var(--lp-surface);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-400/10 text-amber-500 dark:text-amber-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-[var(--lp-text)]">Headquarters address</h3>
                    <p class="mt-1 text-sm text-[var(--lp-text-soft)]">Walk-in customer support and business operations.</p>
                    <p class="mt-2 text-sm font-medium text-[var(--lp-text)]">90 IBB Way PZ Sabon Gari. Zaria, Kaduna State, Nigeria</p>
                </div>
            </div>
        </div>

        <!-- Contact form -->
        <div
            x-data="{ submitted: false }"
            class="rounded-xl border p-8"
            style="border-color: var(--lp-border); background: var(--lp-surface);"
        >
            <template x-if="!submitted">
                <div>
                    <h3 class="text-xl font-semibold text-[var(--lp-text)]">Send a direct message</h3>
                    <p class="mt-2 text-sm text-[var(--lp-text-soft)]">Fill in the short form below and a representative will reach out in under 2 hours.</p>

                    <form class="mt-6 space-y-4" @submit.prevent="submitted = true">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1.5 block text-sm font-medium text-[var(--lp-text-soft)]">Full name</label>
                                <input type="text" id="name" name="name" required placeholder="Enter name"
                                    class="w-full rounded-lg border px-4 py-2.5 text-sm text-[var(--lp-text)] placeholder:text-[var(--lp-text-faint)] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    style="border-color: var(--lp-border-strong); background: var(--lp-surface-alt);">
                            </div>
                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-medium text-[var(--lp-text-soft)]">Email address</label>
                                <input type="email" id="email" name="email" required placeholder="Enter email"
                                    class="w-full rounded-lg border px-4 py-2.5 text-sm text-[var(--lp-text)] placeholder:text-[var(--lp-text-faint)] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    style="border-color: var(--lp-border-strong); background: var(--lp-surface-alt);">
                            </div>
                        </div>

                        <div>
                            <label for="role" class="mb-1.5 block text-sm font-medium text-[var(--lp-text-soft)]">Account / partnership type</label>
                            <select id="role" name="role" required
                                class="w-full rounded-lg border px-4 py-2.5 text-sm text-[var(--lp-text)] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                style="border-color: var(--lp-border-strong); background: var(--lp-surface-alt);">
                                <option value="" disabled selected>Select option</option>
                                <option value="public">Retail customer (standard user)</option>
                                <option value="wholesale">Wholesale partner / agent</option>
                                <option value="other">Other inquiry</option>
                            </select>
                        </div>

                        <div>
                            <label for="message" class="mb-1.5 block text-sm font-medium text-[var(--lp-text-soft)]">Inquiry message</label>
                            <textarea id="message" name="message" required rows="4" placeholder="Describe your request..."
                                class="w-full rounded-lg border px-4 py-2.5 text-sm text-[var(--lp-text)] placeholder:text-[var(--lp-text-faint)] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                style="border-color: var(--lp-border-strong); background: var(--lp-surface-alt);"></textarea>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#0049b8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Submit message
                        </button>
                    </form>
                </div>
            </template>

            <template x-if="submitted">
                <div role="status" class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-vibrant/10 text-vibrant">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-[var(--lp-text)]">Message sent</h3>
                    <p class="mt-1 max-w-xs text-sm text-[var(--lp-text-soft)]">Thanks for reaching out — a representative will contact you shortly.</p>
                </div>
            </template>
        </div>
    </div>
</section>
