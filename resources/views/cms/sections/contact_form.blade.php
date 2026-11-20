<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.card class="space-y-6">
            <x-ui.section-heading
                :eyebrow="$config['eyebrow'] ?? 'Contact Precious Pakistan'"
                :title="$config['heading'] ?? 'Request a Custom Pakistan Itinerary'"
                :subtitle="$config['subtitle'] ?? null"
            />

            @if (session('contact_status'))
                <x-ui.alert type="success">{{ session('contact_status') }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('cms.contact.submit') }}" class="space-y-4">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="website_url">Leave blank</label>
                    <input id="website_url" name="website_url" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input label="Your Name" name="name" required autocomplete="name" />
                    <x-ui.input label="Email Address" name="email" type="email" required autocomplete="email" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($config['show_phone_field'] ?? true)
                        <x-ui.input label="Phone / WhatsApp (Optional)" name="phone" type="tel" autocomplete="tel" />
                    @endif
                    @if ($config['show_trip_dates_field'] ?? true)
                        <x-ui.input label="Preferred Travel Dates (Optional)" name="preferred_dates" placeholder="e.g. June 2027, 8 days" />
                    @endif
                </div>

                <div class="space-y-1.5">
                    <label for="contact-message" class="block text-sm font-semibold text-slate-900">
                        Trip Details or Questions <span class="text-red-600">*</span>
                    </label>
                    <textarea
                        id="contact-message"
                        name="message"
                        rows="4"
                        required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-200"
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer">
                        <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-800">
                        <span>{{ $config['consent_text'] ?? 'I consent to Precious Pakistan contacting me regarding my inquiry.' }}</span>
                    </label>
                    @error('consent')
                        <p class="text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.button type="submit" variant="primary" size="lg">Send Inquiry</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</section>
